<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("../config/localdb.php");

/*
|--------------------------------------------------------------------------
| UPDATE ROLE
|--------------------------------------------------------------------------
*/
if(isset($_GET['role']) && isset($_GET['id']))
{
    $role = $_GET['role'];
    $id = $_GET['id'];

    // allow only valid roles
    if($role === "admin" || $role === "user")
    {
        $query = "UPDATE accounts SET role=? WHERE account_id=?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "si", $role, $id);

        if(mysqli_stmt_execute($stmt))
        {
            header("Location: user.php?msg=Role updated successfully");
            exit();
        }
        else
        {
            echo "Something went wrong while updating role.";
        }
    }
    else
    {
        echo "Invalid role.";
    }
}


/*
|--------------------------------------------------------------------------
| ADD USER
|--------------------------------------------------------------------------
*/
if(isset($_POST['add_user']))
{
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $result = addUser($con, $name, $username, $email, $password, $role);

    if($result === true)
    {
        header("Location: user.php?msg=User added successfully");
        exit();
    }
    else
    {
        header("Location: user.php?msg=" . urlencode($result));
        exit();
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE PASSWORD
|--------------------------------------------------------------------------
*/
if(isset($_POST['change_password']))
{
    $account_id = $_POST['account_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($account_id) || empty($new_password) || empty($confirm_password)) {
        header("Location: user.php?msg=All fields are required");
        exit();
    }

    if (strlen($new_password) < 8) {
        header("Location: user.php?msg=Password must be at least 8 characters");
        exit();
    }

    if ($new_password !== $confirm_password) {
        header("Location: user.php?msg=Passwords do not match");
        exit();
    }

    $result = updateUserPassword($con, $account_id, $new_password);

    if($result === true)
    {
        header("Location: user.php?msg=Password updated successfully");
        exit();
    }
    else
    {
        echo $result;
    }
}


/*
|--------------------------------------------------------------------------
| FUNCTION: UPDATE USER PASSWORD
|--------------------------------------------------------------------------
*/
function updateUserPassword($con, $account_id, $new_password, $changed_by = 'superadmin')
{
    if(empty($account_id) || empty($new_password))
    {
        return "Password cannot be empty.";
    }

    // hash password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // update password and log who changed it
    $query = "UPDATE accounts 
              SET password=?, 
                  last_password_change=NOW(), 
                  last_password_changed_by=? 
              WHERE account_id=?";
    $stmt = mysqli_prepare($con, $query);

    if(!$stmt)
    {
        return "Database prepare failed.";
    }

    mysqli_stmt_bind_param($stmt, "ssi", $hashed_password, $changed_by, $account_id);

    if(mysqli_stmt_execute($stmt))
    {
        return true;
    }

    return "Failed to update password.";
}


/*
|--------------------------------------------------------------------------
| FUNCTION: ADD USER
|--------------------------------------------------------------------------
*/
function addUser($con, $name, $username, $email, $password, $role)
{
    $name = mysqli_real_escape_string($con, $name);
    $username = mysqli_real_escape_string($con, $username);
    $email = mysqli_real_escape_string($con, $email);
    $role = mysqli_real_escape_string($con, $role);

    // check if email or username already exists
    $check_query = "SELECT * FROM accounts WHERE email=? OR username=?";
    $check_stmt = mysqli_prepare($con, $check_query);
    mysqli_stmt_bind_param($check_stmt, "ss", $email, $username);
    mysqli_stmt_execute($check_stmt);
    $check_res = mysqli_stmt_get_result($check_stmt);

    if(mysqli_num_rows($check_res) > 0)
    {
        return "Email or Username already exists";
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO accounts (name, username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())";
    $stmt = mysqli_prepare($con, $query);

    if(!$stmt)
    {
        return "Database prepare failed.";
    }

    mysqli_stmt_bind_param($stmt, "sssss", $name, $username, $email, $hashed_password, $role);

    if(mysqli_stmt_execute($stmt))
    {
        return true;
    }

    return "Something went wrong while adding user.";
}
?>
