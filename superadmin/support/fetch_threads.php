<?php
require_once __DIR__ . '/auth.php';

auth_required(['superadmin']);

$search = trim($_GET['search'] ?? '');

$sql = "SELECT t.id, t.status, t.created_at,
               a.name AS account_name, a.role AS account_role,
               (SELECT message FROM support_messages
                WHERE thread_id = t.id ORDER BY id DESC LIMIT 1) AS last_message,
               (SELECT created_at FROM support_messages
                WHERE thread_id = t.id ORDER BY id DESC LIMIT 1) AS last_at,
               (SELECT COUNT(*) FROM support_messages
                WHERE thread_id = t.id AND is_read = 0
                  AND sender_role != 'superadmin') AS unread_count
        FROM support_threads t
        JOIN accounts a ON a.account_id = t.account_id";

if ($search !== '') {
    $like = '%' . $con->real_escape_string($search) . '%';
    $sql .= " WHERE a.name LIKE '$like'
               OR EXISTS (
                   SELECT 1 FROM support_messages sm
                   WHERE sm.thread_id = t.id AND sm.message LIKE '$like'
               )";
}

$sql .= " ORDER BY last_at DESC, t.created_at DESC";

$result  = $con->query($sql);
$threads = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

json_out(['threads' => $threads]);
