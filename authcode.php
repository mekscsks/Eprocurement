<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'config/localdb.php';

if (isset($_POST['login_btn'])) { 

    $identifier = trim($_POST['identifier']); // email OR username
    $password   = $_POST['password'];

    if (empty($identifier) || empty($password)) {
        $_SESSION['swal'] = [
            'title' => 'Error!',
            'text'  => 'All fields are required.',
            'icon'  => 'error'
        ];
        header('Location: login.php');
        exit();
    }

    // ============================================
    // 1️⃣ TRY LOCAL DATABASE LOGIN FIRST
    // ============================================
    $stmt = $con->prepare("SELECT * FROM accounts WHERE email = ? OR username = ? LIMIT 1");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            if (isset($user['status']) && $user['status'] !== 'active') {
                $_SESSION['swal'] = [
                    'title' => 'Inactive Account',
                    'text'  => '⚠️ Your account is inactive.',
                    'icon'  => 'error'
                ];
                header('Location: login.php');
                exit();
            }

            loginUser($user);
        } else {
            $_SESSION['swal'] = [
                'title' => 'Invalid Password',
                'text'  => '❌ The password you entered is incorrect.',
                'icon'  => 'error'
            ];
            header('Location: login.php');
            exit();
        }
    } else {
        // ============================================
        // 2️⃣ IF NOT FOUND OR WRONG PASSWORD → TRY LDAP
        // ============================================

        $ldap_server = "172.16.0.4";
        $ldap_port   = 389;
        $base_dn     = "DC=depeddasma,DC=edu,DC=ph";
        $domain      = "depeddasma.edu.ph";

        $ldap_conn = ldap_connect($ldap_server, $ldap_port);

        if (!$ldap_conn) {
            die("Could not connect to LDAP server.");
        }

        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

        $user_upn = $identifier . "@$domain";

        $bind = @ldap_bind($ldap_conn, $user_upn, $password);

        if (!$bind) {
            $_SESSION['swal'] = [
                'title' => 'Invalid Credentials',
                'text'  => '❌ Email/Username or password is incorrect.',
                'icon'  => 'error'
            ];
            header('Location: login.php');
            exit();
        }

        // ============================================
        // 3️⃣ FETCH LDAP USER INFO
        // ============================================
        $filter = "(sAMAccountName=$identifier)";
        $search = ldap_search($ldap_conn, $base_dn, $filter);

        $ldap_name = $identifier;
        $ldap_email = $identifier . "@$domain";

        if ($search) {
            $entries = ldap_get_entries($ldap_conn, $search);
            if ($entries['count'] > 0) {
                $ldap_name = $entries[0]['displayname'][0] ?? $identifier;
                $ldap_email = $entries[0]['mail'][0] ?? $ldap_email;
            }
        }

        ldap_unbind($ldap_conn);

        // ============================================
        // 4️⃣ CHECK IF LDAP USER EXISTS IN DATABASE
        // ============================================
        $check = $con->prepare("SELECT * FROM accounts WHERE username = ? OR email = ? LIMIT 1");
        $check->bind_param("ss", $identifier, $ldap_email);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows === 0) {
            // Auto-create LDAP user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = "user";

            $insert = $con->prepare("INSERT INTO accounts (name, username, email, password, role, status, is_verified) VALUES (?, ?, ?, ?, ?, 'active', 1)");
            $insert->bind_param("sssss", $ldap_name, $identifier, $ldap_email, $hashedPassword, $role);
            $insert->execute();

            $account_id = $insert->insert_id;

            $user = [
                'account_id' => $account_id,
                'name' => $ldap_name,
                'username' => $identifier,
                'email' => $ldap_email,
                'role' => $role
            ];
        } else {
            $user = $checkResult->fetch_assoc();
        }

        loginUser($user);
    }
}

// ============================================
// LOGIN FUNCTION (Reusable)
// ============================================
function loginUser($user)
{
    session_regenerate_id(true);

    $_SESSION['auth'] = true;
    $_SESSION['auth_user'] = [
        'account_id' => $user['account_id'],
        'name'       => $user['name'],
        'username'   => $user['username'],
        'email'      => $user['email'],
        'role'       => $user['role']
    ];

    $_SESSION['role'] = $user['role'];

    $_SESSION['swal'] = [
        'title' => 'Welcome!',
        'text'  => "👋 Welcome, " . $user['name'] . "!",
        'icon'  => 'success'
    ];

    switch($user['role']) {
        case 'superadmin':
            header("Location: /superadmin/index.php");
            break;
        case 'admin':
            header("Location: /admin/dashboard.php");
            break;
        default:
            header("Location: /user/user.php");
            break;
    }
    exit();
} 

if (isset($_POST['register'])) {

    $name       = trim($_POST['name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';

    // Check required fields
    if (empty($name) || empty($username) || empty($email) || empty($department) || empty($phone) || empty($password)) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'All fields are required.'
        ];
        header('Location: login.php');
        exit();
    }

    // Check if email or username already exists
    $stmt = $con->prepare("SELECT * FROM accounts WHERE email = ? OR username = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Email or Username already exists.'
        ];
        header('Location: login.php');
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user';
    $status = 'active';
    $provider = 'local';
    $is_verified = 1;

    $insert = $con->prepare("INSERT INTO accounts (name, username, email, department, phone, password, role, status, provider, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("sssssssssi", $name, $username, $email, $department, $phone, $hashedPassword, $role, $status, $provider, $is_verified);

    if ($insert->execute()) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Registration successful! You can now log in.'
        ];
        header('Location: login.php');
        exit();
    } else {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Registration failed. Please try again.'
        ];
        header('Location: login.php');
        exit();
    }
}