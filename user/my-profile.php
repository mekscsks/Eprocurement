<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/localdb.php';
require_once 'includes/auth.php';
require_once 'userfunctions.php';

$account_id = $_SESSION['auth_user']['account_id'];

/* GET ACCOUNT INFO */
$account = getAccountInfo($con, $account_id);
$name = $account['name'];
$username = $account['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'includes/header.php' ?>
<?php if (isset($_SESSION['alert'])):
    $alert = $_SESSION['alert'];
    $icon  = $alert['type'] ?? 'info';
    $title = $alert['title'] ?? ucfirst($icon);
    $text  = $alert['msg'] ?? ($alert['message'] ?? '');
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
<?php unset($_SESSION['alert']); endif; ?>
<div class="db-layout">
    <?php include 'includes/sidebar.php' ?>
    <main class="db-main">
        <div class="d-flex align-items-start align-items-md-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h4 mb-1">My Profile</h2>
                <div class="text-muted small">Update your profile details and password</div>
            </div>
            <a href="user.php" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>

        <?php if(isset($_SESSION['status'])): ?>
        <div class="alert alert-info mb-3">
            <?= htmlspecialchars($_SESSION['status']); unset($_SESSION['status']); ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['pass_status'])): ?>
        <div class="alert alert-success mb-3">
            <?= htmlspecialchars($_SESSION['pass_status']); unset($_SESSION['pass_status']); ?>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="db-card">
                    <div class="db-card-head">
                        <div>
                            <h5 class="db-card-title mb-0">Profile Information</h5>
                            <div class="db-card-subtitle">Basic account details</div>
                        </div>
                        <span class="db-card-badge"><i class="bi bi-person"></i> Profile</span>
                    </div>
                    <div class="p-4">
                        <form action="code.php" method="POST" class="vstack gap-3">
                            <div>
                                <label class="form-label" for="profile_name">Name</label>
                                <input id="profile_name" type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>" autocomplete="name" required>
                            </div>

                            <div>
                                <label class="form-label" for="profile_username">Username</label>
                                <input id="profile_username" type="text" class="form-control" name="username" value="<?= htmlspecialchars($username) ?>" autocomplete="username" required>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-success w-100">
                                Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="db-card">
                    <div class="db-card-head">
                        <div>
                            <h5 class="db-card-title mb-0">Change Password</h5>
                            <div class="db-card-subtitle">Use a strong password</div>
                        </div>
                        <span class="db-card-badge"><i class="bi bi-shield-lock"></i> Security</span>
                    </div>
                    <div class="p-4">
                        <form action="code.php" method="POST" class="vstack gap-3">
                            <div>
                                <label class="form-label" for="current_password">Current Password</label>
                                <input id="current_password" type="password" class="form-control" name="current_password" autocomplete="current-password" required>
                            </div>

                            <div>
                                <label class="form-label" for="new_password">New Password</label>
                                <input id="new_password" type="password" class="form-control" name="new_password" autocomplete="new-password" required>
                            </div>

                            <div>
                                <label class="form-label" for="confirm_password">Confirm Password</label>
                                <input id="confirm_password" type="password" class="form-control" name="confirm_password" autocomplete="new-password" required>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-warning w-100">
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
