<<<<<<< HEAD
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || !isset($_SESSION['auth_user'])) {
    header('Location: index.php');
    exit();
}
?>

<div id="content" class="p-4">
    <h2>Welcome to the Main Content Area</h2>
    <p>This is where your main content will go.</p>

    <!-- Login Button -->
    <a href="login.php" class="btn btn-primary mt-3">Login</a>
</div>
=======
<?php include 'includes/sidebar.php' ?>
<?php include 'includes/header.php' ?>

<div id="content" class="p-4">
    <h2>Welcome to the Main Content Area</h2>
    <p>This is where your main content will go.</p>

    <!-- Login Button -->
    <a href="login.php" class="btn btn-primary mt-3">Login</a>
</div>
>>>>>>> 8223c11d305d6e08eeaaf66c51f7e53ce72fc974
