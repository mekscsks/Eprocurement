<?php
require_once '../../config/localdb.php';
require_once '../includes/auth.php';
require_once 'config_helpers.php';

header('Content-Type: application/json');

$account_id = (int)$_SESSION['auth_user']['account_id'];
$action     = $_POST['action'] ?? $_GET['action'] ?? '';

function jsonOut(bool $ok, string $msg, array $extra = []): void {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

// ── 1. TOGGLE MODULE ────────────────────────────────────────────────────────
if ($action === 'toggle_module') {
    $module_id  = (int)($_POST['module_id'] ?? 0);
    $is_enabled = (int)($_POST['is_enabled'] ?? 0); // new desired state

    if (!$module_id) jsonOut(false, 'Invalid module.');

    // Fetch current state for audit
    $r = $con->query("SELECT module_name, module_key, is_enabled FROM system_modules WHERE id = $module_id LIMIT 1");
    if (!$r || $r->num_rows === 0) jsonOut(false, 'Module not found.');
    $mod = $r->fetch_assoc();

    $old_state = (int)$mod['is_enabled'] ? 'ENABLED' : 'DISABLED';
    $new_state = $is_enabled ? 'ENABLED' : 'DISABLED';

    $stmt = $con->prepare(
        "UPDATE system_modules SET is_enabled = ?, updated_by = ?, updated_at = NOW() WHERE id = ?"
    );
    $stmt->bind_param('iii', $is_enabled, $account_id, $module_id);
    $stmt->execute();
    $stmt->close();

    $audit_action = $is_enabled ? 'ENABLE_MODULE' : 'DISABLE_MODULE';
    logSystemAudit(
        $account_id,
        $audit_action,
        $mod['module_key'],
        "{$mod['module_name']}: {$old_state} → {$new_state}"
    );

    jsonOut(true, "Module " . ($is_enabled ? 'enabled' : 'disabled') . " successfully.");
}

// ── 2. UPDATE SETTING ───────────────────────────────────────────────────────
if ($action === 'update_setting') {
    $key   = trim($_POST['setting_key'] ?? '');
    $value = trim($_POST['setting_value'] ?? '');

    $allowed_keys = [
        'fiscal_year', 'fiscal_start_date', 'fiscal_end_date',
        'email_notifications', 'system_notifications',
        'session_timeout', 'login_attempt_limit', 'two_factor_auth',
    ];

    if (!in_array($key, $allowed_keys, true)) jsonOut(false, 'Invalid setting key.');

    // Validation
    if ($key === 'session_timeout' && (!is_numeric($value) || (int)$value < 1 || (int)$value > 1440))
        jsonOut(false, 'Session timeout must be 1–1440 minutes.');
    if ($key === 'login_attempt_limit' && (!is_numeric($value) || (int)$value < 1 || (int)$value > 20))
        jsonOut(false, 'Login attempt limit must be 1–20.');

    // Fetch old value for audit
    $r = $con->query("SELECT setting_value FROM system_settings WHERE setting_key = '" . $con->real_escape_string($key) . "' LIMIT 1");
    $old_value = $r ? ($r->fetch_assoc()['setting_value'] ?? '') : '';

    $stmt = $con->prepare(
        "INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at)
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()"
    );
    $stmt->bind_param('ssi', $key, $value, $account_id);
    $stmt->execute();
    $stmt->close();

    logSystemAudit($account_id, 'UPDATE_SETTING', $key, "{$key}: \"{$old_value}\" → \"{$value}\"");

    jsonOut(true, 'Setting saved.');
}

// ── 3. BULK SAVE SETTINGS (form section) ────────────────────────────────────
if ($action === 'save_settings_section') {
    $section = $_POST['section'] ?? '';
    $fields  = [];

    if ($section === 'fiscal') {
        $fields = ['fiscal_year', 'fiscal_start_date', 'fiscal_end_date'];
    } elseif ($section === 'auth') {
        $fields = ['session_timeout', 'login_attempt_limit', 'two_factor_auth'];
    }

    if (empty($fields)) jsonOut(false, 'Unknown section.');

    foreach ($fields as $key) {
        $value = trim($_POST[$key] ?? '');
        $r     = $con->query("SELECT setting_value FROM system_settings WHERE setting_key = '" . $con->real_escape_string($key) . "' LIMIT 1");
        $old   = $r ? ($r->fetch_assoc()['setting_value'] ?? '') : '';

        $stmt = $con->prepare(
            "INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()"
        );
        $stmt->bind_param('ssi', $key, $value, $account_id);
        $stmt->execute();
        $stmt->close();

        if ($old !== $value) {
            logSystemAudit($account_id, 'UPDATE_SETTING', $key, "{$key}: \"{$old}\" → \"{$value}\"");
        }
    }

    jsonOut(true, 'Settings saved successfully.');
}

// ── 4. UPLOAD TEMPLATE ──────────────────────────────────────────────────────
if ($action === 'upload_template') {
    $template_key = trim($_POST['template_key'] ?? '');
    $allowed_tpl  = ['pr_template', 'ppmp_template', 'po_template'];

    if (!in_array($template_key, $allowed_tpl, true)) jsonOut(false, 'Invalid template key.');
    if (empty($_FILES['template_file']['name']))       jsonOut(false, 'No file uploaded.');

    $file     = $_FILES['template_file'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['docx', 'xlsx', 'pdf'];

    if (!in_array($ext, $allowed)) jsonOut(false, 'Only .docx, .xlsx, .pdf allowed.');
    if ($file['size'] > 5 * 1024 * 1024) jsonOut(false, 'File must be under 5MB.');

    $upload_dir = '../../template/';
    $filename   = $template_key . '_' . time() . '.' . $ext;
    $dest       = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) jsonOut(false, 'Upload failed.');

    // Fetch old path for audit
    $r   = $con->query("SELECT setting_value FROM system_settings WHERE setting_key = '" . $con->real_escape_string($template_key) . "' LIMIT 1");
    $old = $r ? ($r->fetch_assoc()['setting_value'] ?? '') : '';

    $stmt = $con->prepare(
        "INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at)
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()"
    );
    $stmt->bind_param('ssi', $template_key, $filename, $account_id);
    $stmt->execute();
    $stmt->close();

    logSystemAudit($account_id, 'UPDATE_SETTING', $template_key, "Template updated: \"{$old}\" → \"{$filename}\"");

    jsonOut(true, 'Template uploaded successfully.', ['filename' => $filename]);
}

jsonOut(false, 'Unknown action.');
