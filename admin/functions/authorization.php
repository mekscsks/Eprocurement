<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../../functions/presence.php';

if (
    !isset($_SESSION['auth'])      ||
    $_SESSION['auth'] !== true     ||
    !isset($_SESSION['auth_user']) ||
    !isset($_SESSION['role'])      ||
    $_SESSION['role'] !== 'admin'
) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Unauthorized access. Admins only.'];
    header('Location: ../index.php');
    exit();
}

// Session timeout — read from system_settings if DB is available, fallback 30 min
$timeout = 1800;
if (isset($con)) {
    try {
        $r = $con->query("SELECT setting_value FROM system_settings WHERE setting_key='session_timeout' LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) $timeout = max(60, (int)$row['setting_value'] * 60);
    } catch (Exception $e) { /* table not yet created, use default */ }
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
