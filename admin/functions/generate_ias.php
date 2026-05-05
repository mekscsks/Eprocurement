<?php
session_start();
require '../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';
include 'purchasefunctions.php';

use PhpOffice\PhpWord\TemplateProcessor;

function iasExtract($value, $fallback = '')
{
    if ($value === null) return $fallback;
    $s = trim((string)$value);
    return $s === '' ? $fallback : $s;
}

function iasExtractFromRemarks($remarks, $label)
{
    if (!is_string($remarks) || $remarks === '') return '';
    $pattern = '/\\b' . preg_quote($label, '/') . '\\s*:\\s*([^|\\r\\n]+)/i';
    if (preg_match($pattern, $remarks, $m)) {
        return trim($m[1]);
    }
    return '';
}

function iasSetValueMulti(TemplateProcessor $template, array $keys, $value): void
{
    foreach ($keys as $k) {
        $template->setValue($k, $value);
    }
}

function iasSetValueMultiIndexed(TemplateProcessor $template, array $keys, int $index, $value): void
{
    foreach ($keys as $k) {
        $template->setValue($k . '#' . $index, $value);
    }
}

function iasTryCloneRow(TemplateProcessor $template, array $candidates, int $count): ?string
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

$remarksFromPr = (string)($prData['remarks'] ?? '');

$iasNo = iasExtract($_GET['ias_no'] ?? '');
$iarNo = iasExtract($_GET['iar_no'] ?? '');
$invoiceNo = iasExtract($_GET['invoice_no'] ?? '');
$officeOverride = iasExtract($_GET['requisitioning_office'] ?? ($_GET['office'] ?? ''));
$supplierOverride = iasExtract($_GET['supplier'] ?? '');
$poOverride = iasExtract($_GET['purchase_order'] ?? ($_GET['po_number'] ?? ($_GET['po_no'] ?? '')));
$stockOverride = iasExtract($_GET['stock_propertyno'] ?? ($_GET['stock/propertyno'] ?? ''), '');

$date = iasExtract($_GET['date'] ?? '');
if ($date !== '') {
    $ts = strtotime($date);
    if ($ts) $date = date('Y-m-d', $ts);
}
if ($date === '') {
    $date = date('Y-m-d');
}

$prNumber = iasExtract($prData['pr_number'] ?? '');
$office = $officeOverride !== '' ? $officeOverride : iasExtract($prData['office'] ?? '');
$supplier = $supplierOverride !== '' ? $supplierOverride : iasExtract($prData['supplier'] ?? '');

$poNumber = $poOverride !== '' ? $poOverride : iasExtract($prData['po_number'] ?? ($prData['po_no'] ?? ($prData['purchase_order'] ?? '')));
if ($poNumber === '') {
    $poNumber = iasExtractFromRemarks($remarksFromPr, 'PO Number');
}
if ($supplier === '') {
    $supplier = iasExtractFromRemarks($remarksFromPr, 'Supplier');
}

if ($iarNo === '') {
    $iarNo = $iasNo;
}

$templatePath = __DIR__ . '/../../template/IAR.docx';
if (!file_exists($templatePath)) {
    http_response_code(500);
    echo 'IAS template missing';
    exit;
}

$template = new TemplateProcessor($templatePath);

iasSetValueMulti($template, ['ias_no', 'ias no', 'IAS No', 'IAS', 'iar_no', 'iar no', 'IAR No', 'IAR'], $iasNo);
iasSetValueMulti($template, ['date', 'Date'], $date);
iasSetValueMulti($template, ['office', 'Office'], $office);
iasSetValueMulti($template, ['supplier', 'Supplier'], $supplier);
iasSetValueMulti($template, ['pr_number', 'pr no', 'pr_no', 'PR No', 'PR'], $prNumber);
iasSetValueMulti($template, ['purchase order', 'purchase_order', 'purchaseorder', 'po_number', 'po no', 'po_no', 'PO No', 'PO', 'P.O'], $poNumber);
iasSetValueMulti($template, ['iar no.', 'iar no', 'iar_no', 'IAR No.', 'IAR No'], $iarNo);
iasSetValueMulti($template, ['invoice no.', 'invoice no', 'invoice_no', 'Invoice No.', 'Invoice No'], $invoiceNo);
iasSetValueMulti($template, ['Requisitioning Office/ Department', 'requisitioning office/ department', 'requisitioning_office', 'requisitioning office', 'department', 'office_department'], $office);
iasSetValueMulti($template, [' supplier ', ' supplier', 'supplier ', ' supplier  ', 'supplier'], $supplier);
iasSetValueMulti($template, [' purchase order ', ' purchase order', 'purchase order ', 'purchase order'], $poNumber);
iasSetValueMulti($template, [' iar no. ', ' iar no.', 'iar no. ', 'iar no.'], $iarNo);
iasSetValueMulti($template, [' invoice no. ', ' invoice no.', 'invoice no. ', 'invoice no.'], $invoiceNo);
iasSetValueMulti($template, [' Requisitioning Office/ Department ', ' Requisitioning Office/ Department', 'Requisitioning Office/ Department '], $office);

$hasItems = count($items) > 0;
if ($hasItems) {
    $rowKey = iasTryCloneRow($template, ['description', 'Description', 'item_description', 'particulars', 'item'], count($items));

    foreach ($items as $index => $item) {
        $i = $index + 1;

        $qtyRaw = $item['quantity'] ?? null;
        $qty = is_numeric($qtyRaw) ? (string)$qtyRaw : '';
        $unit = iasExtract($item['unit'] ?? '', '');
        $desc = iasExtract($item['description'] ?? ($item['item_description'] ?? ''), '');
        $stockPropertyNo = iasExtract(
            $item['stock/propertyno'] ?? ($item['stock_propertyno'] ?? ($item['stock_property_no'] ?? ($item['stock_no'] ?? ($item['property_no'] ?? ($item['propertyno'] ?? ''))))),
            ''
        );
        if ($stockPropertyNo === '' && $stockOverride !== '') {
            $stockPropertyNo = $stockOverride;
        }

        iasSetValueMultiIndexed($template, ['quantity', 'Quantity', 'qty', 'Qty'], $i, $qty);
        iasSetValueMultiIndexed($template, ['unit', 'Unit'], $i, $unit);
        iasSetValueMultiIndexed($template, ['description', 'Description', 'item_description', 'particulars', 'item'], $i, $desc);
        iasSetValueMultiIndexed($template, ['stock/propertyno', 'stock/propertyno.', 'stock_propertyno', 'stock_property_no', 'stock no', 'property no', 'stock_no', 'property_no', 'propertyno'], $i, $stockPropertyNo);

        if ($i === 1 && $rowKey === null) {
            iasSetValueMulti($template, ['quantity', 'Quantity', 'qty', 'Qty'], $qty);
            iasSetValueMulti($template, ['unit', 'Unit'], $unit);
            iasSetValueMulti($template, ['description', 'Description', 'item_description', 'particulars', 'item'], $desc);
            iasSetValueMulti($template, ['stock/propertyno', 'stock/propertyno.', 'stock_propertyno', 'stock_property_no', 'stock no', 'property no', 'stock_no', 'property_no', 'propertyno'], $stockPropertyNo);
        }
    }
} else {
    iasSetValueMulti($template, ['quantity', 'Quantity', 'qty', 'Qty'], '');
    iasSetValueMulti($template, ['unit', 'Unit'], '');
    iasSetValueMulti($template, ['description', 'Description', 'item_description', 'particulars', 'item'], '');
    iasSetValueMulti($template, ['stock/propertyno', 'stock/propertyno.', 'stock_propertyno', 'stock_property_no', 'stock no', 'property no', 'stock_no', 'property_no', 'propertyno'], '');
}

$base = $iasNo !== '' ? $iasNo : ($prNumber !== '' ? $prNumber : (string)$id);
$safeBase = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$base);
$filename = 'IAS_' . $safeBase . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$template->saveAs('php://output');
exit;
