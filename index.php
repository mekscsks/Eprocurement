<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$alert = $_SESSION['alert'] ?? null;
$swal  = $_SESSION['swal']  ?? null;
unset($_SESSION['alert'], $_SESSION['swal'], $_SESSION['swal_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DepEd SDO Dasmariñas – eProcurement</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/login.css">
<link rel="icon" href="assets/logo/CSDO.png" type="image/x-icon">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="lp-navbar">
    <div class="lp-nav-inner">
        <a href="index.php" class="lp-nav-brand">
            <img src="assets/logo/CSDO.png" style="width:44px;border-radius:50%;" alt="DepEd Logo">
            <div>
                <div class="lp-nav-title">DepEd – SDO Dasmariñas</div>
                <div class="lp-nav-sub">eProcurement Portal</div>
            </div>
        </a>
    </div>
</nav>

<div class="lp-main">
    <div class="lp-card">

        <!-- LEFT -->
        <div class="lp-left">
            <div class="lp-left-top">

                <div class="lp-left-content active" id="leftLogin">
                    <div class="lp-eyebrow"><i class="bi bi-shield-check"></i> Secure Portal</div>
                    <h2>Welcome to<br>DepEd <span>eProcurement</span></h2>
                    <p>The official procurement portal of SDO Dasmariñas. Sign in to manage requests, track orders, and more.</p>
                    <div class="lp-features">
                        <div class="lp-feature"><div class="lp-feat-icon"><i class="bi bi-check2"></i></div> Centralized procurement records</div>
                        <div class="lp-feature"><div class="lp-feat-icon"><i class="bi bi-check2"></i></div> Real-time request tracking</div>
                        <div class="lp-feature"><div class="lp-feat-icon"><i class="bi bi-check2"></i></div> Secure role-based access</div>
                    </div>
                </div>

                <div class="lp-left-content" id="leftRegister">
                    <div class="lp-eyebrow"><i class="bi bi-person-plus"></i> New Account</div>
                    <h2>Join DepEd<br><span>eProcurement</span></h2>
                    <p>Create your account to access the system. Registration is subject to admin approval.</p>
                    <div class="lp-features">
                        <div class="lp-feature"><div class="lp-feat-icon"><i class="bi bi-check2"></i></div> Quick registration process</div>
                        <div class="lp-feature"><div class="lp-feat-icon"><i class="bi bi-check2"></i></div> Secure credential storage</div>
                        <div class="lp-feature"><div class="lp-feat-icon"><i class="bi bi-check2"></i></div> Instant access upon approval</div>
                    </div>
                </div>

            </div>
            <div class="lp-left-footer">
                © <?= date('Y') ?> DepEd SDO Dasmariñas City<br>
                All rights reserved. Authorized personnel only.<br>
                Developed by <a href="https://mekscsks.github.io/portfolio/" target="_blank" style="color:rgba(255,255,255,.45);text-decoration:none;">Miko R. Vargas</a> | Amazon Q
            </div>
        </div>

        <!-- RIGHT -->
        <div class="lp-right">

            <!-- LOGIN PANEL -->
            <div class="lp-panel active" id="panelLogin">
                <h3>Sign In</h3>
                <p class="lp-sub">Enter your credentials to continue.</p>
    
                <form action="authcode.php" method="POST">
                    <div class="lp-field">
                        <label>Email or Username</label>
                        <div class="lp-field-inner">
                            <i class="bi bi-person lp-ficon"></i>
                            <input type="text" name="identifier" placeholder="Enter username or email" required>
                        </div>
                    </div>

                    <div class="lp-field">
                        <label>Password</label>
                        <div class="lp-field-inner">
                            <i class="bi bi-lock lp-ficon"></i>
                            <input type="password" name="password" id="password" placeholder="Enter password" required>
                            <button type="button" class="lp-toggle-pw" onclick="togglePassword('password', this)">
                                <i class="bi bi-eye" id="pwIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="login_btn" class="lp-btn-submit mt-2">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                </form>

                <div class="lp-copyright">
                    <a href="developers.php" class="text-decoration-none text-muted">Developers</a>
                    &nbsp;·&nbsp;
                    <a href="mailto:admin@deped.gov.ph" class="text-decoration-none text-muted">Contact Admin</a>
                </div>
            </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function switchPanel(to) {
    const fromPanel = document.querySelector('.lp-panel.active');
    const toPanel   = document.getElementById('panel' + to.charAt(0).toUpperCase() + to.slice(1));
    const fromLeft  = document.querySelector('.lp-left-content.active');
    const toLeft    = document.getElementById('left' + to.charAt(0).toUpperCase() + to.slice(1));

    fromPanel.classList.add('exit');
    setTimeout(() => {
        fromPanel.classList.remove('active', 'exit');
        toPanel.classList.add('active');
    }, 250);

    fromLeft.classList.remove('active');
    toLeft.classList.add('active');
}

<?php if ($alert): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: <?= json_encode($alert['type']) ?>,
        title: <?= json_encode($alert['message']) ?>,
        customClass: { popup: 'lp-swal-popup', confirmButton: 'lp-swal-confirm' }
    });
});
<?php elseif ($swal): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: <?= json_encode($swal['icon'] ?? 'info') ?>,
        title: <?= json_encode($swal['title'] ?? '') ?>,
        text: <?= json_encode($swal['text'] ?? '') ?>,
        customClass: { popup: 'lp-swal-popup', confirmButton: 'lp-swal-confirm' }
    });
});
<?php endif; ?>
</script>
</body>
</html>
