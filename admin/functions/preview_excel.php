<?php
include '../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger mb-0">No file specified</div>';
    exit;
}

$id = intval($_GET['id']);

$query = "SELECT file_path FROM tool_sub WHERE id='$id'";
$result = mysqli_query($con, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-danger mb-0">File not found</div>';
    exit;
}

$row = mysqli_fetch_assoc($result);
$baseName = basename($row['file_path']);
$candidates = [
    __DIR__ . '/../../uploads/ppmp/' . $baseName,
    __DIR__ . '/../../uploads/app/' . $baseName,
    __DIR__ . '/../../uploads/' . $baseName
];
$filePath = null;
foreach ($candidates as $p) {
    if (file_exists($p)) { $filePath = $p; break; }
}

if (!$filePath || !file_exists($filePath)) {
    echo '<div class="alert alert-danger mb-0">File does not exist</div>';
    exit;
}

// Only preview Excel files
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!in_array($ext, ['xls','xlsx'])) {
    echo "<div class='alert alert-info mb-0'>Preview available for Excel files only.</div>";
    exit;
}

try {
    $spreadsheet = IOFactory::load($filePath);
} catch (Exception $e) {
    echo '<div class="alert alert-danger mb-0">Error loading Excel.</div>';
    exit;
}
?>

<div class="excel-preview">
    <style>
        .excel-preview { font-family: Calibri, "Segoe UI", Arial, sans-serif; color: #111827; }
        .excel-preview input.excel-sheet-radio { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        .excel-preview-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 12px; border-bottom:1px solid #e5e7eb; background:#f9fafb; border-top-left-radius:10px; border-top-right-radius:10px; }
        .excel-preview-title { font-size: 14px; font-weight: 600; margin:0; line-height: 1.2; }
        .excel-preview-sub { font-size: 12px; color:#6b7280; }
        .excel-preview-tabs { display:flex; gap:6px; padding:10px 10px 8px; border-bottom:1px solid #e5e7eb; background:#fff; overflow:auto; }
        .excel-tab { border:1px solid #d1d5db; background:#f3f4f6; color:#374151; padding:6px 10px; border-radius:10px; font-size:12px; line-height:1; white-space:nowrap; cursor:pointer; user-select:none; }
        .excel-viewport { height: min(68vh, 560px); overflow:auto; background:#fff; border-bottom-left-radius:10px; border-bottom-right-radius:10px; }
        .excel-sheet { display:none; }
        .excel-grid { border-collapse: separate; border-spacing: 0; width:max-content; min-width:100%; }
        .excel-grid th, .excel-grid td { border-right:1px solid #e5e7eb; border-bottom:1px solid #e5e7eb; padding:4px 8px; font-size:12px; height:22px; }
        .excel-grid thead th { position: sticky; top: 0; z-index: 3; background:#f3f4f6; font-weight:600; text-align:center; }
        .excel-grid .corner { position: sticky; left: 0; z-index: 4; background:#e5e7eb; min-width:48px; width:48px; }
        .excel-grid .row-h { position: sticky; left: 0; z-index: 2; background:#f9fafb; font-weight:600; text-align:right; min-width:48px; width:48px; }
        .excel-grid td { background:#fff; white-space: nowrap; }
        .excel-grid tbody tr:first-child th.row-h { border-top:1px solid #e5e7eb; }
        .excel-grid thead th { border-top:1px solid #e5e7eb; }
        .excel-grid th:first-child, .excel-grid td:first-child { border-left:1px solid #e5e7eb; }
        <?php
            $uid = 'excelprev_' . $id;
            $sheets = $spreadsheet->getAllSheets();
            foreach ($sheets as $index => $sheet) {
                $radioId = $uid . '_sheet_' . $index;
                echo "#$radioId:checked ~ .excel-preview-tabs label[for=\"$radioId\"] { background:#2563eb; border-color:#2563eb; color:#fff; }\n";
                echo "#$radioId:checked ~ .excel-viewport .excel-sheet[data-sheet=\"$index\"] { display:block; }\n";
            }
        ?>
    </style>

    <?php foreach ($spreadsheet->getAllSheets() as $index => $sheet): ?>
        <input
            class="excel-sheet-radio"
            type="radio"
            name="<?= $uid ?>_sheet"
            id="<?= $uid ?>_sheet_<?= $index ?>"
            <?= $index === 0 ? 'checked' : '' ?>
        >
    <?php endforeach; ?>

    <div class="excel-preview-toolbar">
        <div>
            <div class="excel-preview-title">Excel Preview</div>
            <div class="excel-preview-sub"><?= htmlspecialchars(basename($filePath)) ?></div>
        </div>
    </div>

    <div class="excel-preview-tabs" role="tablist" aria-label="Sheets">
        <?php foreach ($spreadsheet->getAllSheets() as $index => $sheet): ?>
            <label class="excel-tab" for="<?= $uid ?>_sheet_<?= $index ?>">
                <?= htmlspecialchars($sheet->getTitle() ?: ('Sheet ' . ($index + 1))) ?>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="excel-viewport">
        <?php foreach ($spreadsheet->getAllSheets() as $index => $sheet): ?>
            <?php
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
                $highestRow = $sheet->getHighestRow();
            ?>
            <div class="excel-sheet" data-sheet="<?= $index ?>">
                <table class="excel-grid" aria-label="Excel sheet">
                    <thead>
                        <tr>
                            <th class="corner"></th>
                            <?php for ($col = 1; $col <= $highestColumnIndex; $col++): ?>
                                <th><?= Coordinate::stringFromColumnIndex($col) ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($r = 1; $r <= $highestRow; $r++): ?>
                            <tr>
                                <th class="row-h"><?= $r ?></th>
                                <?php for ($c = 1; $c <= $highestColumnIndex; $c++): ?>
                                    <?php
                                        $columnLetter = Coordinate::stringFromColumnIndex($c);
                                        $cell = $sheet->getCell($columnLetter . $r);
                                        $value = $cell->getFormattedValue();
                                    ?>
                                    <td><?= htmlspecialchars((string)$value) ?></td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div>
