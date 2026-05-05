<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../../functions/presence.php';

if (!isset($_SESSION['auth_user']['account_id'])) {
    header('Location: ../index.php');
    exit();
}

if (($_SESSION['auth_user']['role'] ?? '') !== 'superadmin') {
    header('Location: ../index.php');
    exit();
}

// Session timeout
$timeout = 1800;
if (isset($con)) {
    $r = $con->query("SELECT setting_value FROM system_settings WHERE setting_key='session_timeout' LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) $timeout = max(60, (int)$row['setting_value'] * 60);
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    if (isset($con) && !empty($_SESSION['auth_user']['account_id'])) {
        markUserPresence($con, (int)$_SESSION['auth_user']['account_id'], false);
    }
    session_unset(); session_destroy(); session_start();
    $_SESSION['alert'] = ['type' => 'warning', 'message' => 'Session expired. Please log in again.'];
    header('Location: ../index.php');
    exit();
}
$_SESSION['last_activity'] = time();

if (isset($con) && !empty($_SESSION['auth_user']['account_id'])) {
    touchUserPresence($con, (int)$_SESSION['auth_user']['account_id']);
}
