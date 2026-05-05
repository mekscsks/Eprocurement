<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include __DIR__ . '/../../config/localdb.php';
include 'purchasefunctions.php';

if(isset($_POST['create_pr'])){
    createPR($con, $_POST);
}

if(isset($_POST['update_id'])){
    updatePR($con, (int)$_POST['update_id'], $_POST);
}

if(isset($_POST['delete_id'])){
    deletePR($con, (int)$_POST['delete_id']);
}

if(isset($_POST['status_id'])){
    updatePRStatus($con, $_POST['status_id'], $_POST['status'] ?? '');
}

// Redirect back to main page
header("Location: ../purchase_requests.php");
exit;
?>
