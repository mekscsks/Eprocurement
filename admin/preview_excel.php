<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = $_GET['file'] ?? '';
if (!$file) die("No file specified.");

// Remove leading slash
$file = ltrim($file, '/');

// Correct server path
$filepath = __DIR__ . '/../' . $file;

if (!file_exists($filepath)) {
    die("File not found. Tried: $filepath");
}

// Load Excel
$spreadsheet = IOFactory::load($filepath);
$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Excel Preview</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">

<h6 class="mb-3">Excel File Preview</h6>

<div class="table-responsive">
<table class="table table-bordered table-sm">
<?php foreach ($data as $rowIndex => $row): ?>
<tr>
<?php foreach ($row as $cell): ?>
  <?= $rowIndex === 0
      ? "<th>" . htmlspecialchars($cell) . "</th>"
      : "<td>" . htmlspecialchars($cell) . "</td>" ?>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
</div>

</body>
</html>
