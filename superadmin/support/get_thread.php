<?php
require_once __DIR__ . '/auth.php';

$user = auth_required(['user','admin']);

$thread_id = get_or_create_thread($con, $user['id'], $user['role']);

json_out(['thread_id' => $thread_id]);
