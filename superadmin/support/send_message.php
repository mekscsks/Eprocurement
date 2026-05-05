<?php
require_once __DIR__ . '/auth.php';

$user = auth_required(['user','admin','superadmin']);

$thread_id  = isset($_POST['thread_id'])  ? (int)$_POST['thread_id']  : 0;
$message    = trim($_POST['message'] ?? '');
$attachment = null;

if ($user['role'] === 'superadmin') {
    if (!$thread_id) json_out(['error' => 'thread_id required']);
    $chk = $con->prepare("SELECT id FROM support_threads WHERE id=?");
    $chk->bind_param('i', $thread_id);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) json_out(['error' => 'Thread not found']);
} else {
    $thread_id = get_or_create_thread($con, $user['id'], $user['role']);
}

if ($message === '' && empty($_FILES['attachment']['name'])) {
    json_out(['error' => 'Empty message']);
}

if (!empty($_FILES['attachment']['name'])) {
    $file = $_FILES['attachment'];
    if ($file['error'] !== UPLOAD_ERR_OK)          json_out(['error' => 'Upload error']);
    if ($file['size'] > MAX_UPLOAD)                json_out(['error' => 'File too large (max 5 MB)']);

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MIME, true))      json_out(['error' => 'Invalid file type']);

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('att_', true) . '.' . strtolower($ext);
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        json_out(['error' => 'Could not save file']);
    }
    $attachment = $filename;
}

$stmt = $con->prepare(
    "INSERT INTO support_messages (thread_id, sender_id, sender_role, message, attachment)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param('iisss', $thread_id, $user['id'], $user['role'], $message, $attachment);
$stmt->execute();

if ($user['role'] === 'superadmin') {
    $upd = $con->prepare("UPDATE support_threads SET status='open' WHERE id=?");
    $upd->bind_param('i', $thread_id);
    $upd->execute();
}

json_out(['success' => true, 'thread_id' => $thread_id]);
