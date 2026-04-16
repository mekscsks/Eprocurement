<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$alert = $_SESSION['alert'] ?? null;
$swal = $_SESSION['swal'] ?? null;
$swalData = $_SESSION['swal_data'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="assets/css/login.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <link rel="icon" href="assets/logo/CSDO.png" type="image/x-icon">

</head>
<body>

<nav class="lp-navbar">
    <div class="lp-nav-inner">
        <a href="index.php" class="lp-nav-brand">
            <div class=""></div>
                <img src="assets/logo/CSDO.png" style="width: 50px;" alt="DepEd Logo">
            <div>
                <div class="lp-nav-title">DepEd – SDO Dasmariñas</div>
                <div class="lp-nav-sub">eProcurement Portal</div>
            </div>
        </a>
        <a href="index.php" class="lp-nav-back">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>
    </div>
</nav>
<div class="lp-main">
    <div class="lp-card">
        <div class="lp-left">
            <div class="lp-left-top">
                <div class="lp-left-content active" id="leftLogin">
                    <div class="lp-eyebrow"><i class="bi bi-shield-check"></i> Secure Portal</div>
                    <h2>Welcome to<br>DepEd <span>eProcurement</span></h2>
                    <p>The official procurement portal of SDO Dasmariñas. Log in to access PPMP tools, bidding opportunities, and documents.</p>
                </div>
                <div class="lp-left-content" id="leftRegister">
                    <div class="lp-eyebrow"><i class="bi bi-person-plus"></i> New Account</div>
                    <h2>Join DepEd<br><span>eProcurement</span></h2>
                    <p>Create your account to access the portal and start submitting PPMPs and bidding documents online.</p>
                </div>
            </div>
            <div class="lp-left-footer">
                Governed by Republic Act No. 9184<br>Government Procurement Reform Act
            </div>
        </div>
        <div class="lp-right">
            <div class="lp-panel active" id="panelLogin">
                <h3>Sign In</h3>
                <p class="lp-sub">Enter your credentials to access your account.</p>
                <form action="authcode.php" method="POST">
                    <div class="lp-field">
                        <label for="identifier">Email or Username</label>
                        <div class="lp-field-inner">
                            <i class="bi bi-person lp-ficon"></i>
                            <input type="text" name="identifier" id="identifier" placeholder="Enter email or username" required autocomplete="email">
                        </div>
                    </div>
                    <div class="lp-field">
                        <label for="password">Password</label>
                        <div class="lp-field-inner">
                            <i class="bi bi-lock lp-ficon"></i>
                            <input type="password" name="password" id="password" placeholder="Enter password" required autocomplete="current-password">
                            <button type="button" class="lp-toggle-pw" onclick="lpTogglePw()">
                                <i class="bi bi-eye" id="lpPwIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" name="login_btn" class="lp-btn-submit"><i class="bi bi-box-arrow-in-right"></i> Login</button>
                </form>
            </div>

            <!-- REGISTER PANEL -->
            <div class="lp-panel" id="panelRegister">
                <h3>Create Account</h3>
                <p class="lp-sub">Fill in your details to register for the portal.</p>

                <form method="POST" action="authcode.php">
                    <div class="lp-field">
                        <label for="nameReg">Full Name</label>
                        <div class="lp-field-inner">
                            <i class="bi bi-person lp-ficon"></i>
                            <input type="text" name="name" id="nameReg" placeholder="e.g. Juan dela Cruz" required>
                        </div>
                    </div>

</div>

<!-- ── SCRIPTS ── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* Panel switch */
function switchPanel(target) {
    const panelLogin = document.getElementById('panelLogin');
    const panelRegister = document.getElementById('panelRegister');
    const leftLogin = document.getElementById('leftLogin');
    const leftRegister = document.getElementById('leftRegister');

    const fromPanel = target === 'register' ? panelLogin : panelRegister;
    const toPanel = target === 'register' ? panelRegister : panelLogin;
    const fromLeft = target === 'register' ? leftLogin : leftRegister;
    const toLeft = target === 'register' ? leftRegister : leftLogin;

    fromPanel.classList.add('exit');
    fromPanel.classList.remove('active');
    fromLeft.classList.remove('active');

    setTimeout(() => {
        fromPanel.classList.remove('exit');
        toPanel.classList.add('active');
        toLeft.classList.add('active');
    }, 280);
}

/* Password toggle */
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
function lpTogglePw() { togglePassword('password','lpPwIcon'); }
function lpToggleRegPw() { togglePassword('passwordReg','regPwIcon'); }

/* Reusable Swal */
function showSwal(options){
    Swal.fire({
        icon:'info',
        title:'',
        text:'',
        confirmButtonColor:'#0d6efd',
        customClass:{popup:'lp-swal-popup',confirmButton:'lp-swal-confirm'},
        ...options
    });
}

document.addEventListener('DOMContentLoaded', function () {
    /* PHP session alerts */
    <?php if($alert): ?>
    showSwal({
        icon: <?= json_encode($alert['type']); ?>,
        title: <?= json_encode($alert['type']==='error'?'Oops!':'Success!'); ?>,
        text: <?= json_encode($alert['message']); ?>
    });
    <?php unset($_SESSION['alert']); endif; ?>

    <?php if($swal): ?>
    showSwal({
        icon: <?= json_encode($swal['icon'] ?? 'info'); ?>,
        title: <?= json_encode($swal['title'] ?? ''); ?>,
        text: <?= json_encode($swal['text'] ?? ''); ?>
    });
    <?php unset($_SESSION['swal']); endif; ?>

    <?php if($swalData && $swalData['type']==='not_verified'): ?>
    showSwal({
        icon: 'warning',
        title: 'Not Verified!',
        html: `Hi <strong><?= htmlspecialchars($swalData['name']); ?></strong>, your account (<?= htmlspecialchars($swalData['email']); ?>) is not verified.`,
        confirmButtonText: 'Resend Verification Email',
        showCancelButton:true,
        cancelButtonText:'Cancel'
    }).then(result=>{
        if(result.isConfirmed){
            fetch('authcode.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'resend_verification', account_id: <?= $swalData['account_id']; ?>})
            })
            .then(res=>res.json())
            .then(data=>{
                showSwal({
                    icon:data.status==='success'?'success':'error',
                    title:data.status==='success'?'Sent!':'Error!',
                    text:data.message
                });
            });
        }
    });
    <?php unset($_SESSION['swal_data']); endif; ?>
});
</script>
</body>
</html>
