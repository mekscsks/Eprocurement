<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (!defined('CHAT_WIDGET_LOADED')) { define('CHAT_WIDGET_LOADED', true); include_once __DIR__ . '/../../support/chat_widget.php'; } ?>
<!-- ── BANNER ── -->
<div class="db-banner">
    <div class="db-banner-inner">
        <div class="db-greeting">
            <div class="db-greeting-eyebrow">
                <link rel="stylesheet" href="assets/css/css.css">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </div>
            <h1>Welcome back, <span><?= htmlspecialchars($_SESSION['auth_user']['name'] ?? 'User'); ?></span></h1>
            <p>Schools Division Office of Dasmariñas &nbsp;·&nbsp; eProcurement Portal</p>
        </div>
        <div class="db-banner-meta">
            <div class="avatar"><?= strtoupper(substr($_SESSION['auth_user']['name'] ?? 'U', 0, 1)); ?></div>
            <div>
                <div class="uname"><?= htmlspecialchars($_SESSION['auth_user']['username'] ?? $_SESSION['auth_user']['name'] ?? 'User'); ?></div>
                <div class="urole"><?= htmlspecialchars($_SESSION['auth_user']['department'] ?? 'Staff'); ?></div>
            </div>
        </div>
    </div>
</div>