<?php
include('../../config/localdb.php');
include('functions/user-functions.php');
session_start();

/*
|--------------------------------------------------------------------------
| UPDATE USER ROLE
|--------------------------------------------------------------------------
*/
function updateUserRole($account_id, $role)
{
    global $con;

    $allowed_roles = ['admin', 'user', 'superadmin'];

    if (!in_array($role, $allowed_roles)) {
        return false;
    }

    // Prevent changing your own role
    if ($_SESSION['auth_user']['account_id'] == $account_id) {
        return false;
    }

    $account_id = mysqli_real_escape_string($con, $account_id);
    $role = mysqli_real_escape_string($con, $role);

    $query = "UPDATE accounts SET role='$role' WHERE account_id='$account_id'";
    return mysqli_query($con, $query);
}


/*
|--------------------------------------------------------------------------
| UPDATE USER STATUS
|--------------------------------------------------------------------------
*/
function updateUserStatus($account_id, $status)
{
    global $con;

    $allowed_status = ['active', 'inactive'];

    if (!in_array($status, $allowed_status)) {
        return false;
    }

    $account_id = mysqli_real_escape_string($con, $account_id);
    $status = mysqli_real_escape_string($con, $status);

    $query = "UPDATE accounts SET status='$status' WHERE account_id='$account_id'";
    return mysqli_query($con, $query);
}


/*
|--------------------------------------------------------------------------
| ADD USER
|--------------------------------------------------------------------------
*/
function addUser($name, $email, $password, $role)
{
    global $con;

    $name = mysqli_real_escape_string($con, $name);
    $email = mysqli_real_escape_string($con, $email);
    $password = password_hash($password, PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($con, $role);

    $query = "INSERT INTO accounts (name, email, password, role, status, created_at)
              VALUES ('$name', '$email', '$password', '$role', 'active', NOW())";

    return mysqli_query($con, $query);
}


/*
|--------------------------------------------------------------------------
| DELETE USER (SOFT DELETE)
|--------------------------------------------------------------------------
*/
function deleteUser($account_id)
{
    global $con;

    $account_id = mysqli_real_escape_string($con, $account_id);

    $query = "UPDATE accounts SET status='inactive' WHERE account_id='$account_id'";
    return mysqli_query($con, $query);
}




if(isset($_GET['role']) && isset($_GET['id'])){
    updateUserRole($_GET['id'], $_GET['role']);
}

if(isset($_GET['activate'])){
    updateUserStatus($_GET['activate'], 'active');
}

if(isset($_GET['deactivate'])){
    updateUserStatus($_GET['deactivate'], 'inactive');
}

?>