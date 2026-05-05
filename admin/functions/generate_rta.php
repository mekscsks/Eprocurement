<?php
session_start();
require '../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';
include 'purchasefunctions.php';

use PhpOffice\PhpWord\TemplateProcessor;

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['pr_id']) ? (int)$_GET['pr_id'] : 0);
if ($id <= 0) { http_response_code(400); echo 'PR ID missing'; exit; }

$prData = getPRWithItems($con, $id);
if (!$prData) { http_response_code(404); echo 'PR not found'; exit; }

// Suppliers — fetch all with quoted amount for repeating rows
$suppStmt = $con->prepare("SELECT s.name, ps.quoted_amount FROM pr_suppliers ps JOIN suppliers s ON s.id = ps.supplier_id WHERE ps.pr_id = ? AND s.deleted = 0 ORDER BY ps.id ASC");
$suppStmt->bind_param('i', $id);
$suppStmt->execute();
$suppRows = $suppStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$suppStmt->close();

// Fallback to single supplier if pr_suppliers is empty
if (empty($suppRows) && !empty($prData['supplier_id'])) {
    $s = $con->query("SELECT name FROM suppliers WHERE id = " . (int)$prData['supplier_id'] . " AND deleted = 0 LIMIT 1");
    if ($s) {
        $row = $s->fetch_assoc();
        if ($row) $suppRows = [['name' => $row['name'], 'quoted_amount' => $prData['total_amount'] ?? 0]];
    }
}
if (empty($suppRows)) {
    $suppRows = [['name' => trim((string)($prData['supplier'] ?? '')), 'quoted_amount' => $prData['total_amount'] ?? 0]];
}

// For single placeholder ${name_of_the_bidder} — joined newline
$bidderName = implode("\n", array_column($suppRows, 'name'));

// Procurement project from tool_sub
$tsData = [];
if (!empty($prData['tool_sub_id'])) { 
    $ts = $con->query("SELECT * FROM tool_sub WHERE id = " . (int)$prData['tool_sub_id'] . " LIMIT 1");
    if ($ts) $tsData = $ts->fetch_assoc() ?: [];
}

$prNumber           = trim((string)($prData['pr_number']   ?? ''));
$procurementProject = trim((string)($tsData['description'] ?? ''));
$quotedAmount       = number_format((float)($prData['total_amount'] ?? 0), 2);
$date               = date('F d, Y');

$templatePath = __DIR__ . '/../../template/RTA.docx';
if (!file_exists($templatePath)) { http_response_code(500); echo 'RTA template missing'; exit; }

$template = new TemplateProcessor($templatePath);

$map = [
    'pr_number'           => $prNumber,
    'procurement_project' => $procurementProject,
    'name_of_the_bidder'  => $bidderName,
    'date'                => $date,
    'office'              => trim((string)($prData['office'] ?? '')),
    'requested_by'        => trim((string)($prData['requested_by'] ?? '')),
];

foreach ($map as $key => $value) {
    try { $template->setValue($key, $value); } catch (Throwable $e) {}
}

// Repeating rows per supplier
try {
    $template->cloneRow('name', count($suppRows));
    foreach ($suppRows as $i => $sup) {
        $rowNum = $i + 1;
        $template->setValue('name#' . $rowNum, htmlspecialchars($sup['name']));
        $template->setValue('quotedamount#' . $rowNum, number_format((float)($sup['quoted_amount'] ?? 0), 2));
    }
} catch (Throwable $e) {}

$safeBase = preg_replace('/[^A-Za-z0-9._-]+/', '_', $prNumber ?: (string)$id);
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="RTA_' . $safeBase . '.docx"');
header('Cache-Control: max-age=0');
$template->saveAs('php://output');
exit;
