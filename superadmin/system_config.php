<?php
require_once '../config/localdb.php';
require_once 'includes/auth.php';
require_once 'functions/config_helpers.php';

// Load all modules
$modules = $con->query("SELECT * FROM system_modules ORDER BY module_name ASC");

// Load all settings into a key→value map
$settings = [];
$sr = $con->query("SELECT setting_key, setting_value FROM system_settings");
while ($row = $sr->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

function sv(array $s, string $k, string $default = ''): string {
    return htmlspecialchars($s[$k] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Configuration – Superadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f4f6fb; }
.main-content { margin-left:260px; padding:28px 24px; min-height:100vh; }
.page-header { background:#fff; border-radius:12px; padding:18px 24px; margin-bottom:22px; box-shadow:0 1px 4px rgba(0,0,0,.07); }
.config-card { background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.07); }
.nav-tabs .nav-link { color:#495057; font-weight:500; }
.nav-tabs .nav-link.active { color:#0d6efd; font-weight:600; }
.form-switch .form-check-input { width:2.5em; height:1.3em; cursor:pointer; }
.table thead th { background:#212529; color:#fff; font-size:.82rem; text-transform:uppercase; letter-spacing:.04em; border:none; }
.table td { vertical-align:middle; font-size:.88rem; }
.section-title { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6c757d; margin-bottom:12px; }
.toast-container { z-index:9999; }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="position-fixed top-0 start-0 h-100" style="width:260px;">
    <?php include 'includes/sidebar.php'; ?>
</div>

<div class="main-content">

    <!-- Header -->
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>System Configuration</h4>
            <small class="text-muted">Manage modules, settings, templates, and security</small>
        </div>
    </div>

    <!-- Tabs -->
    <div class="config-card p-0">
        <ul class="nav nav-tabs px-3 pt-3" id="configTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabModules">
                    <i class="bi bi-toggles me-1"></i> Modules
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabFiscal">
                    <i class="bi bi-calendar3 me-1"></i> Fiscal Year
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabNotifications">
                    <i class="bi bi-bell me-1"></i> Notifications
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTemplates">
                    <i class="bi bi-file-earmark-text me-1"></i> Templates
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAuth">
                    <i class="bi bi-shield-lock me-1"></i> Authentication
                </button>
            </li>
        </ul>

        <div class="tab-content p-4">

            <!-- ── TAB: MODULES ─────────────────────────────────────────── -->
            <div class="tab-pane fade show active" id="tabModules">
                <p class="section-title">Module Enable / Disable Controls</p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Module Name</th>
                                <th>Module Key</th>
                                <th>Description</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($modules && $modules->num_rows > 0):
                            while ($mod = $modules->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($mod['module_name']) ?></td>
                            <td><code><?= htmlspecialchars($mod['module_key']) ?></code></td>
                            <td class="text-muted"><?= htmlspecialchars($mod['description'] ?? '') ?></td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block mb-0">
                                    <input class="form-check-input module-toggle"
                                           type="checkbox"
                                           role="switch"
                                           data-id="<?= $mod['id'] ?>"
                                           data-name="<?= htmlspecialchars($mod['module_name']) ?>"
                                           <?= $mod['is_enabled'] ? 'checked' : '' ?>>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No modules found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── TAB: FISCAL YEAR ─────────────────────────────────────── -->
            <div class="tab-pane fade" id="tabFiscal">
                <p class="section-title">Fiscal Year Configuration</p>
                <form id="fiscalForm" class="row g-3" style="max-width:520px;">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Fiscal Year</label>
                        <input type="number" name="fiscal_year" class="form-control"
                               value="<?= sv($settings,'fiscal_year','2025') ?>" min="2000" max="2100" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" name="fiscal_start_date" class="form-control"
                               value="<?= sv($settings,'fiscal_start_date') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" name="fiscal_end_date" class="form-control"
                               value="<?= sv($settings,'fiscal_end_date') ?>" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Fiscal Settings
                        </button>
                    </div>
                    <input type="hidden" name="section" value="fiscal">
                </form>
            </div>

            <!-- ── TAB: NOTIFICATIONS ───────────────────────────────────── -->
            <div class="tab-pane fade" id="tabNotifications">
                <p class="section-title">Notification Settings</p>
                <div class="d-flex flex-column gap-3" style="max-width:400px;">

                    <div class="d-flex align-items-center justify-content-between border rounded p-3">
                        <div>
                            <div class="fw-semibold">Email Notifications</div>
                            <small class="text-muted">Send email alerts for system events</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input setting-toggle" type="checkbox" role="switch"
                                   data-key="email_notifications"
                                   <?= ($settings['email_notifications'] ?? '1') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between border rounded p-3">
                        <div>
                            <div class="fw-semibold">System Notifications</div>
                            <small class="text-muted">In-app notification banners and alerts</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input setting-toggle" type="checkbox" role="switch"
                                   data-key="system_notifications"
                                   <?= ($settings['system_notifications'] ?? '1') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── TAB: TEMPLATES ───────────────────────────────────────── -->
            <div class="tab-pane fade" id="tabTemplates">
                <p class="section-title">Document Template Management</p>
                <div class="row g-3">
                    <?php
                    $tpls = [
                        'pr_template'   => ['label' => 'Purchase Request (PR)', 'icon' => 'bi-file-earmark-word'],
                        'ppmp_template' => ['label' => 'PPMP Template',          'icon' => 'bi-file-earmark-spreadsheet'],
                        'po_template'   => ['label' => 'Purchase Order (PO)',    'icon' => 'bi-file-earmark-text'],
                    ];
                    foreach ($tpls as $key => $meta):
                        $current = $settings[$key] ?? '';
                    ?>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi <?= $meta['icon'] ?> fs-4 text-primary"></i>
                                <span class="fw-semibold"><?= $meta['label'] ?></span>
                            </div>
                            <?php if ($current): ?>
                            <div class="mb-2">
                                <small class="text-muted">Current: </small>
                                <a href="../template/<?= htmlspecialchars($current) ?>" target="_blank" class="small">
                                    <?= htmlspecialchars($current) ?>
                                </a>
                            </div>
                            <?php else: ?>
                            <p class="text-muted small mb-2">No template uploaded.</p>
                            <?php endif; ?>
                            <form class="template-form" data-key="<?= $key ?>">
                                <input type="file" name="template_file" class="form-control form-control-sm mb-2"
                                       accept=".docx,.xlsx,.pdf" required>
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-upload me-1"></i> Upload
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── TAB: AUTHENTICATION ──────────────────────────────────── -->
            <div class="tab-pane fade" id="tabAuth">
                <p class="section-title">Authentication & Security Settings</p>
                <form id="authForm" class="row g-3" style="max-width:520px;">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Session Timeout <small class="text-muted">(minutes)</small></label>
                        <input type="number" name="session_timeout" class="form-control"
                               value="<?= sv($settings,'session_timeout','30') ?>" min="1" max="1440" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Login Attempt Limit</label>
                        <input type="number" name="login_attempt_limit" class="form-control"
                               value="<?= sv($settings,'login_attempt_limit','5') ?>" min="1" max="20" required>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between border rounded p-3">
                            <div>
                                <div class="fw-semibold">Two-Factor Authentication</div>
                                <small class="text-muted">Require 2FA for all logins</small>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input setting-toggle" type="checkbox" role="switch"
                                       data-key="two_factor_auth"
                                       <?= ($settings['two_factor_auth'] ?? '0') === '1' ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Auth Settings
                        </button>
                    </div>
                    <input type="hidden" name="section" value="auth">
                </form>
            </div>

        </div><!-- /tab-content -->
    </div><!-- /config-card -->

</div><!-- /main-content -->

<!-- Disable Confirmation Modal -->
<div class="modal fade" id="disableModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Disable Module</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Disabling <strong id="disableModuleName"></strong> will restrict all users from accessing it.</p>
                <p class="text-danger mb-0"><strong>Continue?</strong></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" id="cancelDisable" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDisable">Disable Module</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="sysToast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMsg"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const HANDLER = 'functions/config_handler.php';
const toast   = new bootstrap.Toast(document.getElementById('sysToast'), { delay: 3000 });

function showToast(msg, ok = true) {
    const el = document.getElementById('sysToast');
    el.className = 'toast align-items-center border-0 text-bg-' + (ok ? 'success' : 'danger');
    document.getElementById('toastMsg').textContent = msg;
    toast.show();
}

function postAjax(data, onSuccess) {
    fetch(HANDLER, { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => { showToast(res.message, res.success); if (res.success && onSuccess) onSuccess(res); })
        .catch(() => showToast('Request failed.', false));
}

// ── Module Toggles ──────────────────────────────────────────────────────────
let pendingToggle = null;
const disableModal = new bootstrap.Modal(document.getElementById('disableModal'));

document.querySelectorAll('.module-toggle').forEach(chk => {
    chk.addEventListener('change', function () {
        const id      = this.dataset.id;
        const name    = this.dataset.name;
        const enabled = this.checked ? 1 : 0;

        if (!enabled) {
            // Show confirmation before disabling
            pendingToggle = { el: this, id, enabled };
            document.getElementById('disableModuleName').textContent = name;
            disableModal.show();
        } else {
            sendToggle(this, id, enabled);
        }
    });
});

document.getElementById('confirmDisable').addEventListener('click', () => {
    disableModal.hide();
    if (pendingToggle) sendToggle(pendingToggle.el, pendingToggle.id, pendingToggle.enabled);
    pendingToggle = null;
});

document.getElementById('cancelDisable').addEventListener('click', () => {
    if (pendingToggle) pendingToggle.el.checked = true; // revert
    pendingToggle = null;
});

function sendToggle(el, id, enabled) {
    const fd = new FormData();
    fd.append('action', 'toggle_module');
    fd.append('module_id', id);
    fd.append('is_enabled', enabled);
    postAjax(fd);
}

// ── Instant Setting Toggles (notifications, 2FA) ────────────────────────────
document.querySelectorAll('.setting-toggle').forEach(chk => {
    chk.addEventListener('change', function () {
        const fd = new FormData();
        fd.append('action', 'update_setting');
        fd.append('setting_key', this.dataset.key);
        fd.append('setting_value', this.checked ? '1' : '0');
        postAjax(fd);
    });
});

// ── Fiscal Year Form ─────────────────────────────────────────────────────────
document.getElementById('fiscalForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'save_settings_section');
    postAjax(fd);
});

// ── Auth Settings Form ───────────────────────────────────────────────────────
document.getElementById('authForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'save_settings_section');
    postAjax(fd);
});

// ── Template Upload Forms ────────────────────────────────────────────────────
document.querySelectorAll('.template-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'upload_template');
        fd.append('template_key', this.dataset.key);
        postAjax(fd, res => {
            // Update displayed filename
            const card = this.closest('.border');
            let info = card.querySelector('.current-file');
            if (!info) {
                info = document.createElement('div');
                info.className = 'mb-2 current-file';
                this.before(info);
            }
            info.innerHTML = `<small class="text-muted">Current: </small>
                <a href="../template/${res.filename}" target="_blank" class="small">${res.filename}</a>`;
        });
    });
});
</script>
</body>
</html>
