<?php
/**
 * System Configuration Helpers
 * Include this file wherever module-access enforcement is needed.
 */

if (!function_exists('isModuleEnabled')) {
    /**
     * Check if a module is enabled in system_modules.
     * Exits with 403 JSON or HTML error if disabled.
     *
     * @param string $module_key
     * @param bool   $json  true = return JSON error (for AJAX), false = HTML page
     */
    function isModuleEnabled(string $module_key, bool $json = false): void {
        global $con;
        $stmt = $con->prepare("SELECT is_enabled FROM system_modules WHERE module_key = ? LIMIT 1");
        $stmt->bind_param('s', $module_key);
        $stmt->execute();
        $stmt->bind_result($is_enabled);
        $found = $stmt->fetch();
        $stmt->close();

        // If module doesn't exist in DB, treat as enabled (graceful degradation)
        if (!$found) return;

        if (!(int)$is_enabled) {
            http_response_code(403);
            if ($json) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'This module is currently disabled by the administrator.']);
            } else {
                echo '<!DOCTYPE html><html><head>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                    </head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
                    <div class="text-center">
                        <div class="display-1 text-danger"><i class="bi bi-slash-circle"></i></div>
                        <h4 class="mt-3">Module Disabled</h4>
                        <p class="text-muted">This module is currently disabled by the administrator.</p>
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm">Go Back</a>
                    </div>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
                    </body></html>';
            }
            exit;
        }
    }
}

if (!function_exists('logSystemAudit')) {
    /**
     * Write a record to system_audit_logs.
     */
    function logSystemAudit(int $account_id, string $action, ?string $module, string $description): void {
        global $con;
        $ip         = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $con->prepare(
            "INSERT INTO system_audit_logs (account_id, action, module, description, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('isssss', $account_id, $action, $module, $description, $ip, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}
