<?php
require_once __DIR__ . '/auth.php';

$user      = auth_required(['superadmin']);
$thread_id = isset($_POST['thread_id']) ? (int)$_POST['thread_id'] : 0;
$action    = $_POST['action'] ?? 'close';

if (!$thread_id) json_out(['error' => 'thread_id required']);

$status = $action === 'reopen' ? 'open' : 'closed';
$stmt   = $con->prepare("UPDATE support_threads SET status=? WHERE id=?");
$stmt->bind_param('si', $status, $thread_id);
$stmt->execute();

json_out(['success' => true, 'status' => $status]);
