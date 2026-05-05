<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/localdb.php';
include 'includes/auth.php';
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/user.css" rel="stylesheet">

<?php include 'includes/header.php'; ?>

<?php 
$swal = $_SESSION['swal'] ?? null; 
$alert = $_SESSION['alert'] ?? null; 
?>
<?php if ($swal || $alert): 
    $icon  = $swal['icon'] ?? ($alert['type'] ?? 'info');
    $title = $swal['title'] ?? ($alert['title'] ?? ucfirst($icon));
    $text  = $swal['text']  ?? ($alert['msg'] ?? ($alert['message'] ?? ''));
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: <?= json_encode($icon) ?>,
        title: <?= json_encode($title) ?>,
        text: <?= json_encode($text) ?>,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
});
</script>
<?php unset($_SESSION['swal']); unset($_SESSION['alert']); endif; ?>




<div class="launcher-main">

<div class="app-grid">

<a href="../user/ppmp.php" class="app-card">
<div class="app-icon-wrap"><i class="bi bi-file-earmark-check"></i></div>
<div class="app-title">PPMP Tool</div>
<div class="app-desc">View PPMP types</div>
</a>

<a href="../user/submissions.php" class="app-card">
<div class="app-icon-wrap"><i class="bi bi-clock-history"></i></div>
<div class="app-title">My Submissions</div>
<div class="app-desc">Track your PPMP</div>
</a>

<a href="../user/purchase_request.php" class="app-card">
<div class="app-icon-wrap"><i class="bi bi-cart-plus"></i></div>
<div class="app-title">Purchase Request</div>
<div class="app-desc">Submit purchase requests</div>
</a>


<a href="my-profile.php" class="app-card">
<div class="app-icon-wrap"><i class="bi bi-person-circle"></i></div>
<div class="app-title">My Profile</div>
<div class="app-desc">Manage account</div>
</a>

<a href="../logout.php" class="app-card red">
<div class="app-icon-wrap"><i class="bi bi-box-arrow-right"></i></div>
<div class="app-title">Logout</div>
<div class="app-desc">Sign out</div>
</a>

</div>
</div>



<div class="modal fade"
id="forceChangePasswordModal"
tabindex="-1"
data-bs-backdrop="static"
data-bs-keyboard="false"
data-show="<?= !empty($force_change_password) ? '1' : '0' ?>">

<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

<form id="forceChangePasswordForm" method="POST" action="code.php">

<div class="modal-header">
<h5 class="modal-title">Change Your Password</h5>
</div>

<div class="modal-body">

<p>Your password was reset by the administrator. Please create a new password.</p>

<input
type="password"
name="new_password"
class="form-control mb-2"
placeholder="New Password"
required>

<input
type="password"
name="confirm_password"
class="form-control"
placeholder="Confirm Password"
required>

</div>

<div class="modal-footer">

<button
type="submit"
name="change_password"
class="btn btn-primary w-100">
Change Password
</button>

</div>

</form>
</div>
</div>
</div>

</form>
</div>
</div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/user.js"></script>
