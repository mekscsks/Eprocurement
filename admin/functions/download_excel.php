<?php
require_once __DIR__ . '/authorization.php';
include __DIR__ . '/../../config/localdb.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo 'No file specified.';
    exit;
}

$stmt = $con->prepare("SELECT file_path FROM tool_sub WHERE id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo 'Database error.';
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$filePathDb = null;
$stmt->bind_result($filePathDb);
$stmt->fetch();
$stmt->close();

if (!$filePathDb) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$baseName = basename((string)$filePathDb);
$root = dirname(__DIR__, 2);
$candidates = [
    $root . '/uploads/ppmp/' . $baseName,
    $root . '/uploads/app/' . $baseName,
    $root . '/uploads/' . $baseName,
];

$filePath = null;
foreach ($candidates as $p) {
    if (is_file($p)) {
        $filePath = $p;
        break;
    }
}

if (!$filePath) {
    http_response_code(404);
    echo 'File does not exist.';
    exit;
}

$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $baseName);
$mime = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = finfo_file($finfo, $filePath);
        if (is_string($detected) && $detected !== '') {
            $mime = $detected;
        }
        finfo_close($finfo);
    }
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;
