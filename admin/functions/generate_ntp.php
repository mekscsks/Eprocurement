<?php
session_start();
require '../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\TemplateProcessor;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo 'NTP ID missing'; exit; }

$stmt = $con->prepare("SELECT * FROM notice_to_proceed WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$ntp = $stmt->get_result()->fetch_assoc();
if (!$ntp) { http_response_code(404); echo 'NTP not found'; exit; }

// --- Use template if it exists, otherwise build programmatically ---
$templatePath = __DIR__ . '/../../template/NTP.docx';

if (file_exists($templatePath)) {
    $template = new TemplateProcessor($templatePath);

    $location = trim(($ntp['company_location'] ?? '') . ' ' . ($ntp['company_city'] ?? ''));

    $template->setValue('date',                 date('F d, Y', strtotime($ntp['created_at'])));
    $template->setValue('supplier_name',        $ntp['supplier']          ?? '');
    $template->setValue('position/designation', $ntp['contact_position']  ?? '');
    $template->setValue('company_name',         $ntp['company_name']      ?: ($ntp['supplier'] ?? ''));
    $template->setValue('location',             $location);
    $template->setValue('salutation',           $ntp['salutation']        ?? 'Mr.');
    $template->setValue('last_name',            $ntp['contact_name']      ?? '');
    $template->setValue('procurement_name',     $ntp['project']           ?? '');
    $template->setValue('project',               $ntp['project']           ?? '');
    $template->setValue('effectivity_date',     date('F d, Y', strtotime($ntp['created_at'])));

    $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $ntp['ntp_number']);
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="NTP_' . $safe . '.docx"');
    header('Cache-Control: max-age=0');
    $template->saveAs('php://output');
    exit;
}

// --- Build document programmatically ---
$phpWord = new PhpWord();
$phpWord->setDefaultFontName('Arial');
$phpWord->setDefaultFontSize(11);

$section = $phpWord->addSection([
    'marginTop'    => 1440,
    'marginBottom' => 1440,
    'marginLeft'   => 1440,
    'marginRight'  => 1440,
]);

$boldStyle  = ['bold' => true];
$centerPara = ['alignment' => Jc::CENTER];
$date       = date('F d, Y', strtotime($ntp['created_at']));
$salutation = $ntp['salutation'] ?? 'Mr.';
$contactName     = $ntp['contact_name']     ?? '';
$contactPosition = $ntp['contact_position'] ?? '';
$companyName     = $ntp['company_name']     ?: ($ntp['supplier'] ?? '');
$companyAddress  = trim(($ntp['company_location'] ?? '') . ', ' . ($ntp['company_city'] ?? ''), ', ');
$project         = $ntp['project']          ?? '';
$amount          = '₱ ' . number_format((float)$ntp['amount'], 2);
$deliveryDays    = (int)($ntp['delivery_days'] ?? 30);
$ntpNumber       = $ntp['ntp_number']       ?? '';

// Header
$section->addText('NOTICE TO PROCEED', ['bold' => true, 'size' => 14], $centerPara);
$section->addText($ntpNumber, $boldStyle, $centerPara);
$section->addTextBreak(1);

// Date
$section->addText($date, [], ['alignment' => Jc::RIGHT]);
$section->addTextBreak(1);

// Addressee
$section->addText($salutation . ' ' . $contactName, $boldStyle);
if ($contactPosition) $section->addText($contactPosition);
if ($companyName)     $section->addText($companyName);
if ($companyAddress)  $section->addText($companyAddress);
$section->addTextBreak(1);

// Salutation line
$section->addText('Dear ' . $salutation . ' ' . $contactName . ',');
$section->addTextBreak(1);

// Body
$bodyRun = $section->addTextRun();
$bodyRun->addText('You are hereby notified to proceed with the implementation of the project ');
$bodyRun->addText('"' . $project . '"', $boldStyle);
$bodyRun->addText(' with a contract amount of ');
$bodyRun->addText($amount, $boldStyle);
$bodyRun->addText('. The project shall be completed within ');
$bodyRun->addText($deliveryDays . ' calendar days', $boldStyle);
$bodyRun->addText(' from receipt of this notice.');

$section->addTextBreak(2);

// Closing
$section->addText('Very truly yours,');
$section->addTextBreak(3);
$section->addText('AUTHORIZED SIGNATORY', $boldStyle);
$section->addText('Schools Division Superintendent');

$safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $ntpNumber);
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="NTP_' . $safe . '.docx"');
header('Cache-Control: max-age=0');

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');
exit;
