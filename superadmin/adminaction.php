<?php
include __DIR__ . '/auth.php';
include __DIR__ . '/../config/localdb.php';
include __DIR__ . '/classes/Admin.php';

$adminObj = new Admin($con);

// CREATE admin
if (isset($_POST['save_admin'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($adminObj->create($name, $username, $email, $password)) {
        $_SESSION['admin_success'] = "Admin created successfully!";
    } else {
        $_SESSION['admin_error'] = "Failed to create admin.";
    }

    header("Location: admins.php"); // redirect back to list page
    exit();
}

// You can also add EDIT, TOGGLE STATUS handling here later
