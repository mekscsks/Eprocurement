<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';
include 'purchasefunctions.php';

use PhpOffice\PhpWord\TemplateProcessor;

// ------------------------
// Helpers
// ------------------------
function prExtract($value, $fallback = '') {
    $s = trim((string)($value ?? ''));
    return $s === '' ? $fallback : $s;
}

function prItemStock($item) {
    return is_array($item) ? trim((string)($item['stock_property_no'] ?? '')) : '';
}

// Truncate text to fit ONE line in table cell
function truncateLine($text, $maxChars = 30) {
    $text = trim((string)$text);
    return strlen($text) > $maxChars ? substr($text, 0, $maxChars) . '�' : $text;
}

function setValueMulti($template, $keys, $value) {
    foreach ($keys as $k) $template->setValue($k, $value);
}

function setValueMultiIndexed($template, $keys, $index, $value) {
    foreach ($keys as $k) $template->setValue($k . '#' . $index, $value);
}

// Convert plain text with newlines into Word XML runs (preserves line breaks)
function toWordXml($text) {
    $text = str_replace(["\r\n", "\r"], "\n", (string)$text);
    $lines = explode("\n", $text);
    $xml = '';
    foreach ($lines as $i => $line) {
        if ($i > 0) $xml .= '<w:br/>';
        $xml .= htmlspecialchars($line, ENT_XML1, 'UTF-8');
    }
    return $xml;
}

function setValueXmlMultiIndexed($template, $keys, $index, $xmlValue) {
    $ref  = new ReflectionClass($template);
    $prop = $ref->getProperty('tempDocumentMainPart');
    $prop->setAccessible(true);
    $xml  = $prop->getValue($template);
    foreach ($keys as $k) {
        $placeholder = htmlspecialchars('${' . $k . '#' . $index . '}', ENT_XML1, 'UTF-8');
        $xml = str_replace($placeholder, $xmlValue, $xml);
    }
    $prop->setValue($template, $xml);
}

// ------------------------
// PR ID & Data
// ------------------------
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('PR ID missing'); }

$prData = getPRWithItems($con, $id);
if (!$prData || !is_array($prData)) { http_response_code(404); exit('PR not found'); }
$items = is_array($prData['items'] ?? []) ? $prData['items'] : [];

// ------------------------
// Header Info
// ------------------------
$prNumber    = prExtract($prData['pr_number'] ?? $prData['pr_no'] ?? $prData['pr'] ?? '');
$office      = prExtract($prData['office'] ?? '');
$fund        = prExtract($prData['fund_source'] ?? $prData['fund_cluster'] ?? '');
$purpose     = prExtract($prData['purpose'] ?? $prData['end_use'] ?? '');
$requestedBy = prExtract($prData['requested_by'] ?? '');
$designation = prExtract($prData['designation'] ?? $prData['position'] ?? $prData['role'] ?? '');
$dateRaw     = prExtract($prData['created_at'] ?? $prData['date'] ?? '');
$date        = $dateRaw ? date('Y-m-d', strtotime($dateRaw)) : date('Y-m-d');

// ------------------------
// Load Template
// ------------------------
$templatePath = __DIR__ . '/../../template/PR.docx';
if (!file_exists($templatePath)) { http_response_code(500); exit('PR template missing'); }

$template = new TemplateProcessor($templatePath);

// ── Repair broken placeholders (Word splits ${...} across runs) ──────────────
$ref  = new ReflectionClass($template);
$prop = $ref->getProperty('tempDocumentMainPart');
$prop->setAccessible(true);
$xml  = $prop->getValue($template);
$xml  = preg_replace_callback('/\$\{([^}]*)\}/', fn($m) => '${' . strip_tags($m[1]) . '}', $xml);

// Normalize special-char placeholder names to underscored equivalents
$xml = str_replace('${stock/propertyno.}', '${stock_property_no}', $xml);
$xml = preg_replace('/\$\{stock\/property\s*no\.?\}/i', '${stock_property_no}', $xml);

$prop->setValue($template, $xml);

// Fill header
setValueMulti($template, ['fund_cluster'], $fund);
setValueMulti($template, ['office'], $office);
setValueMulti($template, ['section'], prExtract($prData['section'] ?? ''));
setValueMulti($template, ['prno.', 'prno', 'pr_no', 'pr_number'], $prNumber);
setValueMulti($template, ['date'], $date);
setValueMulti($template, ['purpose'], $purpose);
setValueMulti($template, ['requestedby', 'requested_by'], $requestedBy);
setValueMulti($template, ['designation'], $designation);


// ------------------------
// Fill items table (one line per cell)
if (count($items) > 0) {
    $cloned = false;
    foreach (['itemdescription', 'item_description', 'description', 'quantity'] as $candidate) {
        try { 
            $template->cloneRow($candidate, count($items)); 
            $cloned = true; 
            break; 
        } catch (Throwable $e) {}
    }

    $grandTotal = 0.0;
    foreach ($items as $index => $item) {
        $i = $index + 1;

        $qty = is_numeric($item['quantity'] ?? null) ? $item['quantity'] : 0;
        $unitCost = is_numeric($item['unit_cost'] ?? null) ? $item['unit_cost'] : 0;
        $amountRaw = is_numeric($item['total_cost'] ?? null) ? $item['total_cost'] : $qty * $unitCost;
        $grandTotal += (float)$amountRaw;

        // ONE LINE truncation
        $unit = truncateLine(prExtract($item['unit'] ?? '-', 10));
        $desc = prExtract($item['description'] ?? ($item['item_description'] ?? '-'));
        $stockNo = truncateLine(prItemStock($item), 20);

        $qtyVal = $qty ?: '-';
        $unitCostVal = number_format((float)$unitCost, 2);
        $amountVal = number_format((float)$amountRaw, 2);

        if ($cloned) {
            setValueMultiIndexed($template, ['stock_property_no'], $i, $stockNo);
            setValueMultiIndexed($template, ['unit'], $i, $unit);
            setValueXmlMultiIndexed($template, ['itemdescription', 'item_description', 'description'], $i, toWordXml($desc));
            setValueMultiIndexed($template, ['quantity'], $i, $qtyVal);
            setValueMultiIndexed($template, ['unitcost', 'unit_cost'], $i, $unitCostVal);
            setValueMultiIndexed($template, ['sum', 'totalcostphp', 'total_cost', 'total_cost_php', 'totalcost'], $i, $amountVal);
        }
    }

    // Grand total (outside cloned row)
    $grandTotalVal = number_format($grandTotal, 2);
    setValueMulti($template, ['grandtotal', 'totalcostphp', 'total_cost_php', 'totalcost'], $grandTotalVal);
} else {
    // Empty placeholders
    $emptyKeys = ['stock_property_no',
                  'unit','itemdescription','quantity',
                  'unitcost','sum','grandtotal'];
    foreach ($emptyKeys as $key) $template->setValue($key, '');
}

// ------------------------
// Output Word
// ------------------------
$safeBase = preg_replace('/[^A-Za-z0-9._-]+/', '_', $prNumber ?: (string)$id);
$filename = 'PR_' . $safeBase . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$template->saveAs('php://output');
exit;
