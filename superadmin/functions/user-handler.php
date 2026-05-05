<?php
session_start();
include('usersfunctions.php'); // include the functions file

if(isset($_GET['role']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $role = $_GET['role'];

    if(changeUserRole($id, $role)) {
        $_SESSION['success'] = "User role updated to $role";
    } else {
        $_SESSION['error'] = "Failed to update user role";
    }
    header("Location: users.php"); // redirect back to the user list
    exit();
}

if(isset($_GET['activate'])) {
    $id = $_GET['activate'];
    if(activateUser($id)) {
        $_SESSION['success'] = "User activated successfully";
    } else {
        $_SESSION['error'] = "Failed to activate user";
    }
    header("Location: users.php");
    exit();
}

if(isset($_GET['deactivate'])) {
    $id = $_GET['deactivate'];
    if(deactivateUser($id)) {
        $_SESSION['success'] = "User deactivated successfully";
    } else {
        $_SESSION['error'] = "Failed to deactivate user";
    }
    header("Location: users.php");
    exit();
}

// Optional: Redirect if no valid GET parameter
header("Location: users.php");
exit();