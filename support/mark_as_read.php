<?php
// support/mark_as_read.php
require_once __DIR__ . '/auth.php';

$user      = auth_required(['user','admin','superadmin']);
$thread_id = isset($_POST['thread_id']) ? (int)$_POST['thread_id'] : 0;

if (!$thread_id) json_out(['error' => 'thread_id required']);

// Mark messages NOT sent by current user as read
$stmt = $con->prepare(
    "UPDATE support_messages SET is_read=1
     WHERE thread_id=? AND sender_id != ? AND is_read=0"
);
$stmt->bind_param('ii', $thread_id, $user['id']);
$stmt->execute();

json_out(['success' => true]);
