<?php
require_once __DIR__ . '/authorization.php';
include __DIR__ . '/../../config/localdb.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$office = isset($_GET['office']) ? trim($_GET['office']) : '';
$fiscal = isset($_GET['fiscal']) ? trim($_GET['fiscal']) : '';

if ($office === '') {
    http_response_code(400);
    exit('Office not specified.');
}

// Fetch rows for this office
$stmt = $con->prepare(
    "SELECT * FROM tool_sub
     WHERE ppmp_type NOT IN ('APP','Annual Procurement Plan')
       AND office = ?
     " . ($fiscal !== '' ? "AND fiscal_year = ?" : "") . "
     ORDER BY id ASC"
);
if ($fiscal !== '') {
    $stmt->bind_param('ss', $office, $fiscal);
} else {
    $stmt->bind_param('s', $office);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($rows)) {
    http_response_code(404);
    exit('No records found for this office.');
}

$templatePath = __DIR__ . '/../../template/PPMP.xlsx';
if (!file_exists($templatePath)) {
    http_response_code(500);
    exit('PPMP template not found.');
}

$spreadsheet = IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// Find the first empty data row by scanning for a placeholder or just use row 2
// We'll write starting at row 2 (assuming row 1 is header)
// Detect header row — find the last row that looks like a header
$startRow = 2;
// Try to find a row with "Project Title" or similar to determine header row
$highestRow = $sheet->getHighestRow();
for ($r = 1; $r <= min($highestRow, 10); $r++) {
    $val = strtolower(trim((string)$sheet->getCell('A' . $r)->getValue()));
    if (in_array($val, ['#', 'no', 'no.', 'item', 'item no', 'item no.'])) {
        $startRow = $r + 1;
        break;
    }
    // Also check B column for "project title"
    $valB = strtolower(trim((string)$sheet->getCell('B' . $r)->getValue()));
    if (str_contains($valB, 'project') || str_contains($valB, 'title') || str_contains($valB, 'description')) {
        $startRow = $r + 1;
        break;
    }
}

// Template layout (from PPMP.xlsx):
// Row 15 = column headers, Row 16 = column numbers, Row 17 = instructions, Row 18+ = data
// A=description, B=project_type, C=quantity, D=procurement_mode, E=preproc,
// F=start_date, G=end_date, H=delivery_period, I=source_funds, J=budget,
// K=documents, L=remarks
$startRow = 18;

$colMap = [
    'A' => 'description',
    'B' => 'project_type',
    'C' => 'quantity',
    'D' => 'procurement_mode',
    'E' => 'preproc',
    'F' => 'start_date',
    'G' => 'end_date',
    'H' => 'delivery_period',
    'I' => 'source_funds',
    'J' => 'budget',
    'K' => 'documents',
    'L' => 'remarks',
];

// Fill fiscal year and unit from first row
if (!empty($rows)) {
    $first = $rows[0];
    // Update header cells
    $sheet->setCellValue('A10', 'Fiscal Year : ' . ($first['fiscal_year'] ?? ''));
    $sheet->setCellValue('A11', 'End-User or Implementing Unit: ' . ($first['unit'] ?? $first['office'] ?? ''));
}

foreach ($rows as $i => $row) {
    $r = $startRow + $i;
    // Copy style from row 18 (sample row) if it exists
    if ($i > 0) {
        foreach (array_keys($colMap) as $col) {
            $sheet->duplicateStyle($sheet->getStyle($col . '18'), $col . $r);
        }
    }
    foreach ($colMap as $col => $field) {
        $sheet->setCellValue($col . $r, $row[$field] ?? '');
        $sheet->getStyle($col . $r)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    }
}

// Stream download
$safeName = 'PPMP_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $office)
    . ($fiscal !== '' ? '_' . $fiscal : '')
    . '.xlsx';

while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
