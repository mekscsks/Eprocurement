<?php
session_start();
require '../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';
include 'purchasefunctions.php';

use PhpOffice\PhpWord\TemplateProcessor;

function rfqExtract($value, $fallback = '')
{
    if ($value === null) return $fallback;
    $s = trim((string)$value);
    return $s === '' ? $fallback : $s;
}

function rfqSetValueMulti(TemplateProcessor $template, array $keys, $value): void
{
    foreach ($keys as $k) {
        $template->setValue($k, $value);
    }
}

function rfqSetValueMultiIndexed(TemplateProcessor $template, array $keys, int $index, $value): void
{
    foreach ($keys as $k) {
        $template->setValue($k . '#' . $index, $value);
    }
}

function rfqTryCloneRow(TemplateProcessor $template, array $candidates, int $count): ?string
{
    foreach ($candidates as $key) {
        try {
            $template->cloneRow($key, $count);
            return $key;
        } catch (Throwable $e) {
        }
    }
    return null;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['pr_id']) ? (int)$_GET['pr_id'] : 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'PR ID missing';
    exit;
}

$prData = getPRWithItems($con, $id);
if (!$prData || !is_array($prData)) {
    http_response_code(404);
    echo 'PR not found';
    exit;
}

$items = is_array($prData['items'] ?? null) ? $prData['items'] : [];

// ── Fetch linked supplier ────────────────────────────────────────
$supplierData = [];
if (!empty($prData['supplier_id'])) {
    $s = $con->query("SELECT * FROM suppliers WHERE id = " . (int)$prData['supplier_id'] . " AND deleted = 0 LIMIT 1");
    if ($s) $supplierData = $s->fetch_assoc() ?: [];
}

$prNumber       = rfqExtract($prData['pr_number']   ?? '');
$office         = rfqExtract($prData['office']       ?? '');
$requestedBy    = rfqExtract($prData['requested_by'] ?? '');
$endUse         = rfqExtract($prData['end_use']      ?? ($prData['purpose'] ?? ''));
$quotedAmount   = number_format((float)($prData['total_amount'] ?? 0), 2);
$date = !empty($prData['created_at'])
    ? date('F d, Y', strtotime($prData['created_at']))
    : date('F d, Y');

$companyName    = rfqExtract($supplierData['name']     ?? '');
$address        = rfqExtract($supplierData['location'] ?? '');
$contactNumber  = rfqExtract($supplierData['phone']    ?? '');
$emailAddress   = rfqExtract($supplierData['email']    ?? '');

// Fetch linked tool_sub for procurement project + mode
$tsData = [];
if (!empty($prData['tool_sub_id'])) {
    $ts = $con->query("SELECT * FROM tool_sub WHERE id = " . (int)$prData['tool_sub_id'] . " LIMIT 1");
    if ($ts) $tsData = $ts->fetch_assoc() ?: [];
}
$procurementProject = rfqExtract($_GET['procurement_project'] ?? $tsData['description']      ?? '');
$modeOfProcurement  = rfqExtract($_GET['mode_of_procurement']  ?? $tsData['procurement_mode'] ?? '');
$approvedBudget     = number_format((float)($tsData['budget'] ?? $prData['total_amount'] ?? 0), 2);

$templatePath = __DIR__ . '/../../template/RFQ.docx';
if (!file_exists($templatePath)) {
    http_response_code(500);
    echo 'RFQ template missing';
    exit;
}

$template = new TemplateProcessor($templatePath);

// ── Repair broken/split placeholders in raw XML ──────────────────
$ref  = new ReflectionClass($template);
$prop = $ref->getProperty('tempDocumentMainPart');
$prop->setAccessible(true);
$xml  = $prop->getValue($template);
$xml  = preg_replace_callback('/\$\{([^}]*)\}/', fn($m) => '${' . strip_tags($m[1]) . '}', $xml);
// Normalize space-containing placeholder names
$xml  = str_replace('${procurement project}', '${procurement_project}', $xml);
$xml  = str_replace('${mode of procurement}', '${mode_of_procurement}', $xml);
$xml  = str_replace('${name of the bidder}',  '${name_of_the_bidder}',  $xml);
$xml  = str_replace('${quoted amount}',        '${quoted_amount}',        $xml);
$xml  = str_replace('${approve budge}',        '${approved_budget}',      $xml);
$prop->setValue($template, $xml);

rfqSetValueMulti($template, ['date', 'Date'],                                   $date);
rfqSetValueMulti($template, ['pr_number', 'pr_no', 'PR_No'],                    $prNumber);
rfqSetValueMulti($template, ['procurement_project'],                             $procurementProject);
rfqSetValueMulti($template, ['mode_of_procurement'],                             $modeOfProcurement);
rfqSetValueMulti($template, ['approved_budget', 'abc'],                         $approvedBudget);
rfqSetValueMulti($template, ['quoted_amount'],                                   $quotedAmount);
rfqSetValueMulti($template, ['company_name'],                                   $companyName);
rfqSetValueMulti($template, ['address'],                                        $address);
rfqSetValueMulti($template, ['contact_number'],                                 $contactNumber);
rfqSetValueMulti($template, ['email_address'],                                  $emailAddress);
rfqSetValueMulti($template, ['printed_name', 'name_of_the_bidder', 'name'],      $companyName);
rfqSetValueMulti($template, ['office', 'Office'],                               $office);
rfqSetValueMulti($template, ['requested_by'],                                   $requestedBy);

$hasItems = count($items) > 0;
if ($hasItems) {
    try { $template->cloneRow('item_no', count($items)); } catch (Throwable $e) {
        try { $template->cloneRow('item_description', count($items)); } catch (Throwable $e) {}
    }

    foreach ($items as $index => $item) {
        $i          = $index + 1;
        $qtyRaw     = $item['quantity']   ?? null;
        $unitCostRaw= $item['unit_cost']  ?? null;
        $amountRaw  = $item['total_cost'] ?? null;
        if (!is_numeric($amountRaw) && is_numeric($qtyRaw) && is_numeric($unitCostRaw))
            $amountRaw = (float)$qtyRaw * (float)$unitCostRaw;

        try { $template->setValue('item_no#'          . $i, $i); }                                                          catch (Throwable $e) {}
        try { $template->setValue('item_description#' . $i, rfqExtract($item['item_description'] ?? '')); }                 catch (Throwable $e) {}
        try { $template->setValue('unit_price#'       . $i, is_numeric($unitCostRaw) ? number_format((float)$unitCostRaw, 2) : ''); } catch (Throwable $e) {}
        try { $template->setValue('total#'            . $i, is_numeric($amountRaw)   ? number_format((float)$amountRaw, 2)   : ''); } catch (Throwable $e) {}
    }
} else {
    foreach (['item_no', 'item_description', 'unit_price', 'total'] as $tag)
        try { $template->setValue($tag, ''); } catch (Throwable $e) {}
}

$safeBase = preg_replace('/[^A-Za-z0-9._-]+/', '_', $prNumber ?: (string)$id);
$filename = 'RFQ_' . $safeBase . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$template->saveAs('php://output');
exit;

