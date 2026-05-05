<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';

use PhpOffice\PhpWord\TemplateProcessor;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('Missing PR ID.'); }

// ── Fetch PR + linked tool_sub ───────────────────────────────
$stmt = $con->prepare(
    "SELECT pr.*, ts.procurement_mode, ts.budget,
            ts.start_date AS ts_start_date
     FROM purchase_requests pr
     LEFT JOIN tool_sub ts ON ts.id = pr.tool_sub_id
     WHERE pr.id = ? AND pr.deleted = 0
     LIMIT 1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$pr = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pr) { http_response_code(404); exit('Purchase Request not found.'); }

$hasPpmp = !empty($pr['ppmp_id']) || (isset($pr['has_ppmp']) && (int)$pr['has_ppmp'] === 1);

$ts = [
    'procurement_mode' => $pr['procurement_mode'] ?? '',
    'budget'           => $pr['budget']           ?? 0,
    'notes'            => '',
    'start_date'       => $pr['ts_start_date']    ?? '',
];

if ($hasPpmp) {
    // PPMP exists: fall back to latest tool_sub if mode still empty
    if (empty($ts['procurement_mode'])) {
        $fb = $con->prepare("SELECT procurement_mode, budget, start_date FROM tool_sub ORDER BY created_at DESC LIMIT 1");
        $fb->execute();
        $fbRow = $fb->get_result()->fetch_assoc();
        $fb->close();
        if ($fbRow) {
            $ts['procurement_mode'] = $fbRow['procurement_mode'] ?? '';
            $ts['budget']           = $fbRow['budget']           ?? 0;

            $ts['start_date']       = $fbRow['start_date']       ?? '';
        }
    }
} else {
    // No PPMP: derive procurement method from PR total_amount
    $prTotal = (float)($pr['total_amount'] ?? 0);
    $ts['procurement_mode'] = $prTotal <= 1000000 ? 'Small Value Procurement' : 'Public Bidding';
    $ts['budget']           = $prTotal;
}

// ── Fetch PR items ────────────────────────────────────────────
$iStmt = $con->prepare("SELECT * FROM purchase_requests_items WHERE pr_id = ? ORDER BY id ASC");
$iStmt->bind_param('i', $id);
$iStmt->execute();
$items = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$iStmt->close();

// ── Template ──────────────────────────────────────────────────
$templatePath = __DIR__ . '/../../template/BACRESO.docx';
if (!file_exists($templatePath)) { http_response_code(500); exit('BACRESO template missing.'); }

$template = new TemplateProcessor($templatePath);

// ── PR header fields ──────────────────────────────────────────
$template->setValue('pr.no', $pr['pr_number'] ?? '');

$startDate = !empty($ts['start_date']) ? $ts['start_date'] : ($pr['created_at'] ?? date('Y-m-d'));
$template->setValue('month', date('F', strtotime($startDate)));
$template->setValue('day',   date('d', strtotime($startDate)));

// ── Procurement mode ──────────────────────────────────────────
$mode = trim($ts['procurement_mode'] ?? '');

// Section numbers match the BACRESO.docx template (RA 12009 IRR)
$checkboxMap = [
    'checkbox_27' => ['Competitive Bidding', 'Public Bidding'],
    'checkbox_28' => ['Limited Source Bidding', 'Limited Source'],
    'checkbox_29' => ['Competitive Dialogue'],
    'checkbox_30' => ['Unsolicited Offer with Bid Matching', 'Unsolicited Offer'],
    'checkbox_31' => ['Direct Contracting'],
    'checkbox_32' => ['Direct Acquisition'],
    'checkbox_33' => ['Repeat Order'],
    'checkbox_34' => ['Small Value Procurement', 'Small Value'],
    'checkbox_35' => ['Negotiated Procurement', 'Negotiated'],
    'checkbox_36' => ['Direct Sales'],
    'checkbox_37' => ['Direct Procurement for Science, Technology and Innovation', 'Scientific', 'STI'],
];

$sectionMap = [
    'Competitive Bidding'                                      => '27',
    'Public Bidding'                                           => '27',
    'Limited Source Bidding'                                   => '28',
    'Limited Source'                                           => '28',
    'Competitive Dialogue'                                     => '29',
    'Unsolicited Offer with Bid Matching'                      => '30',
    'Unsolicited Offer'                                        => '30',
    'Direct Contracting'                                       => '31',
    'Direct Acquisition'                                       => '32',
    'Repeat Order'                                             => '33',
    'Small Value Procurement'                                  => '34',
    'Small Value'                                              => '34',
    'Negotiated Procurement'                                   => '35',
    'Negotiated'                                               => '35',
    'Direct Sales'                                             => '36',
    'Direct Procurement for Science, Technology and Innovation'=> '37',
    'Scientific'                                               => '37',
    'STI'                                                      => '37',
];

$sectionNo = '';
foreach ($sectionMap as $label => $sec) {
    if (strtolower($label) === strtolower($mode)) { $sectionNo = $sec; break; }
}

try { $template->setValue('procurement_mode', $mode ?: '___________'); } catch (Throwable $e) {}
try { $template->setValue('section_no', $sectionNo ?: '___'); }          catch (Throwable $e) {}
try { $template->setValue('section',    $sectionNo ?: '___'); }          catch (Throwable $e) {}

$modeNorm = strtolower($mode);
foreach ($checkboxMap as $tag => $matchValues) {
    $checked = '☐';
    foreach ($matchValues as $v) {
        if (strtolower($v) === $modeNorm) { $checked = '☑'; break; }
    }
    try { $template->setValue($tag, $checked); } catch (Throwable $e) {}
}

// ── Items from purchase_requests_items ──────────────────────
$firstItem = $items[0] ?? [];
try { $template->setValue('item_number', !empty($firstItem['stock_property_no']) ? $firstItem['stock_property_no'] : '1'); } catch (Throwable $e) {}
try { $template->setValue('quantity',    $firstItem['quantity']         ?? '—'); } catch (Throwable $e) {}
try { $template->setValue('unit',        $firstItem['unit']             ?? '—'); } catch (Throwable $e) {}
try { $template->setValue('description', $firstItem['item_description'] ?? '—'); } catch (Throwable $e) {}
try { $template->setValue('notes',       $ts['notes']                   ?? '');  } catch (Throwable $e) {}

$grandTotal = (float)($ts['budget'] ?: ($pr['total_amount'] ?? 0));
$underlinedTotal = '<w:r><w:rPr><w:u w:val="single"/></w:rPr><w:t xml:space="preserve">' . number_format($grandTotal, 2) . '</w:t></w:r>';
try { $template->setValue('totalcostphp ', $underlinedTotal); } catch (Throwable $e) {}
try { $template->setValue('totalcostphp',  $underlinedTotal); } catch (Throwable $e) {}

// ── Output ────────────────────────────────────────────────────
$safe     = preg_replace('/[^A-Za-z0-9._-]+/', '_', $pr['pr_number'] ?? (string)$id);
$filename = 'BACRESO_' . $safe . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$template->saveAs('php://output');
exit;
