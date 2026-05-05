<?php
// support/support.php — floating chat widget (user & admin)
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['auth_user']['account_id'])) {
    header('Location: ../index.php'); exit;
}

$role = $_SESSION['auth_user']['role'] ?? '';
if ($role === 'superadmin') {
    header('Location: superadmin_support.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Support</title>
<!-- This page is just a shell; the widget is injected via chat_widget.php -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/chat_widget.php'; ?>
</body>
</html>
