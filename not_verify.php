<?php
session_start();

// Redirect to login if no user info in session
if(!isset($_SESSION['not_verified_user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['not_verified_user'];
$name  = htmlspecialchars($user['name']);
$email = htmlspecialchars($user['email']);
$account_id = $user['account_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Not Verified</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { background: #f4f6f9; font-family: 'DM Sans', sans-serif; }
.container { max-width: 500px; margin-top: 100px; }
.card { padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
</style>
</head>
<body>

<div class="container">
    <div class="card text-center">
        <i class="bi bi-exclamation-triangle-fill" style="font-size: 50px; color: #ffc107;"></i>
        <h3 class="mt-3">Account Not Verified</h3>
        <p class="mt-2">Hi <strong><?= $name ?></strong>, your account (<strong><?= $email ?></strong>) is not verified yet.</p>
        <p>Please verify your email to access the portal.</p>
        <button id="resendBtn" class="btn btn-primary mt-3"><i class="bi bi-envelope"></i> Resend Verification Email</button>
        <a href="login.php" class="btn btn-secondary mt-2">Back to Login</a>
    </div>
</div>

<script>
document.getElementById('resendBtn').addEventListener('click', function() {
    Swal.fire({
        title: 'Resend Verification Email?',
        text: "A new verification link will be sent to your email.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, send it!'
    }).then((result) => {
        if(result.isConfirmed) {
            // Send POST request to authcode.php to resend
            fetch('authcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'resend_verification',
                    account_id: <?= json_encode($account_id) ?>
                })
            })
            .then(res => res.json())
            .then(data => {
                Swal.fire({
                    title: data.status === 'success' ? 'Sent!' : 'Error!',
                    text: data.message,
                    icon: data.status,
                    confirmButtonColor: '#0d6efd'
                });
            })
            .catch(() => {
                Swal.fire({
                    title: 'Error!',
                    text: 'Something went wrong. Please try again later.',
                    icon: 'error'
                });
            });
        }
    });
});
</script>

</body>
</html>

<?php
// Clear the session variable after displaying
unset($_SESSION['not_verified_user']);
?>