<?php
// support/typing.php
require_once __DIR__ . '/auth.php';

$user      = auth_required(['user','admin','superadmin']);
$thread_id = isset($_POST['thread_id']) ? (int)$_POST['thread_id'] : 0;
$is_typing = isset($_POST['is_typing']) ? (int)(bool)$_POST['is_typing'] : 0;

if (!$thread_id) json_out(['error' => 'thread_id required']);

// GET: return who is typing (other than current user)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $thread_id = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : 0;
    $stmt = $con->prepare(
        "SELECT a.name FROM support_typing st
         JOIN accounts a ON a.account_id = st.account_id
         WHERE st.thread_id=? AND st.account_id != ? AND st.is_typing=1
           AND st.updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)"
    );
    $stmt->bind_param('ii', $thread_id, $user['id']);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    json_out(['typing' => array_column($rows, 'name')]);
}

// POST: update typing status
$stmt = $con->prepare(
    "INSERT INTO support_typing (thread_id, account_id, is_typing)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE is_typing=VALUES(is_typing), updated_at=NOW()"
);
$stmt->bind_param('iii', $thread_id, $user['id'], $is_typing);
$stmt->execute();

json_out(['success' => true]);
