<?php
require_once __DIR__ . '/authorization.php';
include __DIR__ . '/../../config/localdb.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$unit   = isset($_GET['unit'])   ? trim($_GET['unit'])   : '';
$fiscal = isset($_GET['fiscal']) ? trim($_GET['fiscal']) : '';

if ($unit === '') {
    http_response_code(400);
    exit('Unit not specified.');
}

$sql = "SELECT * FROM tool_sub
        WHERE ppmp_type NOT IN ('APP','Annual Procurement Plan')
          AND status = 'Approved'
          AND unit = ?
        " . ($fiscal !== '' ? "AND fiscal_year = ?" : "") . "
        ORDER BY id ASC";

$stmt = $con->prepare($sql);
if ($fiscal !== '') {
    $stmt->bind_param('ss', $unit, $fiscal);
} else {
    $stmt->bind_param('s', $unit);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($rows)) {
    http_response_code(404);
    exit('No approved records found for this unit.');
}

$templatePath = __DIR__ . '/../../template/APP.xlsx';
if (!file_exists($templatePath)) {
    http_response_code(500);
    exit('APP template not found.');
}

$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getSheet(0); // "APP" sheet

// Fill fiscal year in header (Row 3: "ANNUAL PROCUREMENT PLAN FOR FY ______")
$fy = $fiscal !== '' ? $fiscal : ($rows[0]['fiscal_year'] ?? '');
$sheet->setCellValue('C3', 'ANNUAL PROCUREMENT PLAN FOR FY ' . $fy);

// APP sheet layout (from template inspection):
// Row 7  = column headers
// Row 9  = column numbers
// Row 10 = section label "General Requirements"
// Row 11 = first data row
//
// Column map:
// A = PAP Code (blank)
// B = Object Code (blank)
// C = Project Title       → ppmp_type
// D = End-User/Unit       → unit
// E = General Description → description
// F = General Desc of Procurement → description (same)
// G = Mode of Procurement → procurement_mode
// H = Early Procurement   → preproc
// I = Criteria for Bid    → project_type
// J = Start of Procurement→ start_date
// K = End of Procurement  → end_date
// L = Source of Fund      → source_funds
// M = Estimated Budget    → budget
// N = Procurement Strategy→ delivery_period
// O = Remarks             → remarks

$startRow = 11;

$colMap = [
    'C' => 'ppmp_type',
    'D' => 'unit',
    'E' => 'description',
    'F' => 'description',
    'G' => 'procurement_mode',
    'H' => 'preproc',
    'I' => 'project_type',
    'J' => 'start_date',
    'K' => 'end_date',
    'L' => 'source_funds',
    'M' => 'budget',
    'N' => 'delivery_period',
    'O' => 'remarks',
];

foreach ($rows as $i => $row) {
    $r = $startRow + $i;
    if ($i > 0) {
        foreach (array_keys($colMap) as $col) {
            $sheet->duplicateStyle($sheet->getStyle($col . '11'), $col . $r);
        }
    }
    foreach ($colMap as $col => $field) {
        $sheet->setCellValue($col . $r, $row[$field] ?? '');
        $sheet->getStyle($col . $r)
              ->getAlignment()
              ->setWrapText(true)
              ->setVertical(Alignment::VERTICAL_TOP);
    }
}

// Stream download
$safeName = 'APP_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $unit)
    . ($fiscal !== '' ? '_' . $fiscal : '')
    . '.xlsx';

while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
