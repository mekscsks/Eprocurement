<?php
session_start();
require '../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';

function amountToWords(float $amount): string {
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
             'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
             'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $convert = function(int $n) use (&$convert, $ones, $tens): string {
        if ($n < 20)            return $ones[$n];
        if ($n < 100)           return $tens[(int)($n/10)] . ($n%10 ? ' '.$ones[$n%10] : '');
        if ($n < 1000)          return $ones[(int)($n/100)] . ' Hundred' . ($n%100 ? ' '.$convert($n%100) : '');
        if ($n < 1_000_000)     return $convert((int)($n/1000)) . ' Thousand' . ($n%1000 ? ' '.$convert($n%1000) : '');
        if ($n < 1_000_000_000) return $convert((int)($n/1_000_000)) . ' Million' . ($n%1_000_000 ? ' '.$convert($n%1_000_000) : '');
        return $convert((int)($n/1_000_000_000)) . ' Billion' . ($n%1_000_000_000 ? ' '.$convert($n%1_000_000_000) : '');
    };

    $pesos    = (int) floor($amount);
    $centavos = (int) round(($amount - $pesos) * 100);
    $words    = ($pesos === 0 ? 'Zero' : $convert($pesos)) . ' Pesos';
    if ($centavos > 0) $words .= ' and ' . $convert($centavos) . ' Centavos';
    $words .= ' Only';

    return $words . ' (PHP' . number_format($amount, 2) . ')';
}

use PhpOffice\PhpWord\TemplateProcessor;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo 'NTA ID missing'; exit; }

$stmt = $con->prepare("SELECT * FROM notice_to_award WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$nta = $stmt->get_result()->fetch_assoc();
if (!$nta) { http_response_code(404); echo 'NTA not found'; exit; }

$templatePath = __DIR__ . '/../../template/NTA.docx';
if (!file_exists($templatePath)) { http_response_code(500); echo 'Template missing'; exit; }

$template = new TemplateProcessor($templatePath);

$company_address = trim(($nta['company_location'] ?? '') . ' ' . ($nta['company_city'] ?? ''));

$template->setValue('date',           date('F d, Y', strtotime($nta['created_at'])));
$template->setValue('name',           $nta['contact_name']     ?? '');
$template->setValue('position',       $nta['contact_position'] ?? '');
$template->setValue('company_name',   $nta['company_name']     ?: ($nta['supplier'] ?? ''));
$template->setValue('company_address',$company_address);
$template->setValue('salutation',     $nta['salutation']       ?? 'Mr.');
$template->setValue('description',    $nta['project']          ?? '');
$template->setValue('total_amount',   amountToWords((float)$nta['amount']));

$safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nta['nta_number']);
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="NTA_' . $safe . '.docx"');
header('Cache-Control: max-age=0');
$template->saveAs('php://output');
exit;
