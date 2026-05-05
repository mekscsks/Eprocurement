<?php
/**
 * Module Access Guard
 * Usage: require_once ROOT . '/functions/module_guard.php';
 *        checkModuleAccess('purchase_request');
 *
 * Include this at the top of any module page to enforce system_modules.is_enabled.
 */

if (!function_exists('checkModuleAccess')) {
    function checkModuleAccess(string $module_key, bool $json = false): void {
        global $con;

        // Ensure DB connection exists
        if (!isset($con) || !$con) {
            $cfg = dirname(__DIR__) . '/config/localdb.php';
            if (file_exists($cfg)) require_once $cfg;
        }

        $stmt = $con->prepare("SELECT is_enabled FROM system_modules WHERE module_key = ? LIMIT 1");
        $stmt->bind_param('s', $module_key);
        $stmt->execute();
        $stmt->bind_result($is_enabled);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found || (int)$is_enabled) return; // enabled or not registered → allow

        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'This module is currently disabled by the administrator.']);
        } else {
            ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Module Disabled</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
<div class="text-center p-4">
    <i class="bi bi-slash-circle display-1 text-danger"></i>
    <h4 class="mt-3 fw-bold">Module Disabled</h4>
    <p class="text-muted">This module is currently disabled by the administrator.<br>Please contact your system administrator for assistance.</p>
    <a href="javascript:history.back()" class="btn btn-secondary btn-sm mt-2">
        <i class="bi bi-arrow-left me-1"></i> Go Back
    </a>
</div>
</body>
</html>
            <?php
        }
        exit;
    }
}
