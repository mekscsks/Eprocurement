<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || !isset($_SESSION['auth_user'])) {
    header('Location: ../index.php');
    exit();
}

if (($_SESSION['auth_user']['role'] ?? '') !== 'user') {
    header('Location: ../index.php');
    exit();
}

// Session timeout — read from system_settings if available, fallback to 30 min
$timeout = 1800;
if (isset($con)) {
    try {
        $r = $con->query("SELECT setting_value FROM system_settings WHERE setting_key='session_timeout' LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) $timeout = max(60, (int)$row['setting_value'] * 60);
    } catch (Exception $e) { /* table not yet created, use default */ }
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset(); session_destroy(); session_start();
    $_SESSION['alert'] = ['type' => 'warning', 'message' => 'Session expired. Please log in again.'];
    header('Location: ../index.php');
    exit();
}
$_SESSION['last_activity'] = time();
