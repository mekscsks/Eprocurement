<?php
/**
 * generate_po.php
 * Generates a downloadable PO .docx using PhpWord TemplateProcessor with cloneRow().
 * Usage: generate_po.php?po_id=<id>
 *
 * Template placeholders — PO.docx must use EXACTLY these names:
 *   Header : ${supplier}, ${po_number}, ${address}, ${date}, ${mode_of_procurement},
 *            ${tin}, ${placeofdelivery}, ${deliveryterm}, ${dateofdelivery}, ${paymentterm}
 *   Items  : ${stock_property_no}, ${quantity}, ${unit}, ${Description}, ${unitcost}, ${amount_php}
 *            (one row only in the template — cloneRow() handles duplication)
 *   Footer : ${name}, ${fund_cluster}, ${ors_burs_number}, ${fund_available},
 *            ${date_ors_burs}, ${amount}
 *
 * MAX_ROWS = 10 — rows beyond this are silently dropped to preserve one-page layout.
 */

session_start();
require '../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';
include __DIR__ . '/po_functions.php';

use PhpOffice\PhpWord\TemplateProcessor;

// ─── Validate input ───────────────────────────────────────────────────────────
$po_id = filter_input(INPUT_GET, 'po_id', FILTER_VALIDATE_INT);
if (!$po_id || $po_id <= 0) {
    http_response_code(400);
    exit('Invalid or missing PO ID.');
}

// ─── Fetch PO data ────────────────────────────────────────────────────────────
$po = getPOWithItems($con, $po_id);
if (!$po) {
    http_response_code(404);
    exit('Purchase Order not found.');
}

$templatePath = __DIR__ . '/../../template/PO.docx';
if (!file_exists($templatePath)) {
    http_response_code(500);
    exit('PO template file is missing.');
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
$str     = fn(mixed $v, string $fb = ''): string => trim((string)($v ?? '')) ?: $fb;
$fmtDate = function(string $v): string {
    if (empty($v)) return '';
    $ts = strtotime($v);
    return $ts ? date('F d, Y', $ts) : $v;
};
$peso = fn(float $v): string => '₱' . number_format($v, 2);

// ─── Compute grand total ──────────────────────────────────────────────────────
$items      = is_array($po['items'] ?? null) ? $po['items'] : [];
$grandTotal = 0.0;
foreach ($items as $item) {
    $grandTotal += is_numeric($item['total_cost'] ?? null)
        ? (float)$item['total_cost']
        : (float)($item['unit_cost'] ?? 0) * (float)($item['quantity'] ?? 0);
}

// ─── Enforce one-page row limit ──────────────────────────────────────────────
$items = array_slice($items, 0, 10);

// ─── Load template & fix broken macros via raw XML manipulation ───────────────
// We bypass cloneRow() entirely because Word splits placeholder text across
// multiple <w:r> runs (spell-check, autocorrect), making strpos() fail.
// Instead we: load → strip run-splits from placeholders → clone row in raw XML
// → replace per-row values → push back into TemplateProcessor.
$template = new TemplateProcessor($templatePath);

// ─── Expose & repair the raw document XML ────────────────────────────────────
// Access the internal XML string via Reflection so we can manipulate it directly.
$ref  = new ReflectionClass($template);
$prop = $ref->getProperty('tempDocumentMainPart');
$prop->setAccessible(true);
$xml  = $prop->getValue($template);

// Strip any XML tags that Word injected inside ${ ... } placeholder spans
// (happens when spell-check or autocorrect splits the text across <w:r> runs).
$xml = preg_replace_callback('/\$\{([^}]*)\}/', fn($m) => '${' . strip_tags($m[1]) . '}', $xml);

// ─── Locate the single item row in the XML and clone it ──────────────────────
$rowPlaceholder = '${Description}';
$rowPos = strpos($xml, $rowPlaceholder);
if ($rowPos === false) {
    // Fallback: try alternate casing / spacing variants still present in template
    foreach (['${description}', '${DESCRIPTION}', '${stock_property_no}', '${quantity}', '${unit}', '${unitcost}', '${amount_php}'] as $alt) {
        $rowPos = strpos($xml, $alt);
        if ($rowPos !== false) { $rowPlaceholder = $alt; break; }
    }
}
if ($rowPos === false) {
    http_response_code(500);
    exit('PO template is missing item row placeholders. Please update PO.docx.');
}

// Find the <w:tr> … </w:tr> that contains the placeholder.
$rowStart = strrpos(substr($xml, 0, $rowPos), '<w:tr ');
if ($rowStart === false) {
    $rowStart = strrpos(substr($xml, 0, $rowPos), '<w:tr>');
}
$rowEnd   = strpos($xml, '</w:tr>', $rowPos) + strlen('</w:tr>');
$rowTemplate = substr($xml, $rowStart, $rowEnd - $rowStart);

// Build N cloned rows, each with #N-indexed placeholders (PhpWord convention).
$clonedRows = '';
foreach ($items as $index => $item) {
    $i        = $index + 1;
    $qty      = (float)($item['quantity']  ?? 0);
    $unitCost = (float)($item['unit_cost'] ?? 0);
    $amount   = is_numeric($item['total_cost'] ?? null)
        ? (float)$item['total_cost']
        : $qty * $unitCost;

    $row = $rowTemplate;
    $row = str_replace('${stock_property_no}', htmlspecialchars($str($item['stock_property_no'] ?? ''),                        ENT_XML1), $row);
    $row = str_replace('${quantity}',          htmlspecialchars($qty > 0 ? (string)$qty : '',                                  ENT_XML1), $row);
    $row = str_replace('${unit}',              htmlspecialchars($str($item['unit'] ?? ''),                                     ENT_XML1), $row);
    $row = str_replace('${Description}',       htmlspecialchars($str($item['item_description'] ?? $item['description'] ?? ''), ENT_XML1), $row);
    $row = str_replace('${unitcost}',          $unitCost > 0 ? $peso($unitCost) : '',                                                    $row);
    $row = str_replace('${amount_php}',        $amount   > 0 ? $peso($amount)   : '',                                                    $row);
    $clonedRows .= $row;
}

// Splice: replace the single template row with all cloned rows.
$xml = substr($xml, 0, $rowStart) . $clonedRows . substr($xml, $rowEnd);

// Push the modified XML back into the TemplateProcessor.
$prop->setValue($template, $xml);

// ─── Header placeholders ─────────────────────────────────────────────────────
$template->setValue('supplier',            htmlspecialchars($str($po['supplier_name']),                                    ENT_XML1));
$template->setValue('po_number',           htmlspecialchars($str($po['po_number']),                                        ENT_XML1));
$template->setValue('address',             htmlspecialchars($str($po['supplier_address']),                                 ENT_XML1));
$template->setValue('date',                htmlspecialchars($fmtDate($str($po['po_date'])),                                ENT_XML1));
$template->setValue('mode_of_procurement', htmlspecialchars($str($po['mode_of_procurement']),                              ENT_XML1));
$template->setValue('tin',                 htmlspecialchars($str($po['tin']),                                              ENT_XML1));
$template->setValue('placeofdelivery',     htmlspecialchars($str($po['place_of_delivery']),                                ENT_XML1));
$template->setValue('deliveryterm',        htmlspecialchars($str($po['delivery_terms'] ?? $po['delivery_term'] ?? ''),     ENT_XML1));
$template->setValue('dateofdelivery',      htmlspecialchars($fmtDate($str($po['delivery_date'] ?? '')),                    ENT_XML1));
$template->setValue('paymentterm',         htmlspecialchars($str($po['payment_term'] ?? ''),                               ENT_XML1));

// ─── Footer placeholders ─────────────────────────────────────────────────────
$template->setValue('name',                htmlspecialchars($str($po['conforme_name']),                    ENT_XML1));
$template->setValue('fund_cluster',        htmlspecialchars($str($po['fund_cluster']),                     ENT_XML1));
$template->setValue('ors_burs_number',     htmlspecialchars($str($po['ors_burs_number']),                  ENT_XML1));
$template->setValue('fund_available',      htmlspecialchars($str($po['fund_available']),                   ENT_XML1));
$template->setValue('date_ors_burs',       htmlspecialchars($fmtDate($str($po['date_ors_burs'] ?? '')),    ENT_XML1));
$template->setValue('amount',              $peso($grandTotal));
$template->setValue('total_amount_words',  htmlspecialchars(number_to_words($grandTotal),                  ENT_XML1));

// ─── Stream as downloadable .docx ────────────────────────────────────────────
$filename = 'PO_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $str($po['po_number'], (string)$po_id)) . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$template->saveAs('php://output');

// ─── Cleanup temp file ────────────────────────────────────────────────────────
@unlink(__DIR__ . '/../../po_doc.xml');
exit;
