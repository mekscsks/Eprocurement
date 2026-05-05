<?php
require_once __DIR__ . '/auth.php';

$user      = auth_required(['user','admin','superadmin']);
$thread_id = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : 0;
$after_id  = isset($_GET['after_id'])  ? (int)$_GET['after_id']  : 0;

if (!$thread_id) json_out(['error' => 'thread_id required']);

if ($user['role'] !== 'superadmin') {
    $chk = $con->prepare(
        "SELECT id FROM support_threads WHERE id=? AND account_id=?"
    );
    $chk->bind_param('ii', $thread_id, $user['id']);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) json_out(['error' => 'Forbidden']);
}

$stmt = $con->prepare(
    "SELECT m.id, m.sender_id, m.sender_role, m.message, m.attachment,
            m.is_read, m.created_at, a.name AS sender_name
     FROM support_messages m
     JOIN accounts a ON a.account_id = m.sender_id
     WHERE m.thread_id = ? AND m.id > ?
     ORDER BY m.id ASC"
);
$stmt->bind_param('ii', $thread_id, $after_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$ts = $con->prepare("SELECT status FROM support_threads WHERE id=?");
$ts->bind_param('i', $thread_id);
$ts->execute();
$thread = $ts->get_result()->fetch_assoc();

json_out(['messages' => $rows, 'thread_status' => $thread['status'] ?? 'open']);
