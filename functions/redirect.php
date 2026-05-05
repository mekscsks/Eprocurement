<?php
session_start();
include 'config/dbcon.php';

require __DIR__ . '/../vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

// Google Client
$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope('email');
$client->addScope('profile');

// Check if code is provided
if (!isset($_GET["code"])) {
    exit("Login failed");
}

// Fetch token
$token = $client->fetchAccessTokenWithAuthCode($_GET["code"]);
if (isset($token['error'])) {
    exit("Google Auth Error: " . $token['error']);
}

$client->setAccessToken($token["access_token"]);

// Get user info
$oauth = new Google\Service\Oauth2($client);
$userinfo = $oauth->userinfo->get();

// Domain restriction
$email = $userinfo->email;
$name  = $userinfo->name;
$google_id = $userinfo->id;

$allowedDomains = ['deped.gov.ph','ncst.edu.ph','gmail.com'];
$emailDomain = substr(strrchr($email, "@"), 1);

if (!in_array($emailDomain, $allowedDomains)) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Access Denied',
        text: 'Unauthorized email domain.',
        confirmButtonText: 'OK'
    }).then(() => window.location.href = '/index.php');
    </script>";
    exit();
}

// Database check
$stmt = $con->prepare("SELECT * FROM accounts WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // Insert new user
    $insert = $con->prepare("INSERT INTO accounts (name, email, google_id, provider, role, status) VALUES (?, ?, ?, 'google', 'user', 'active')");
    $insert->bind_param("sss", $name, $email, $google_id);
    $insert->execute();
    
    $user_id = $con->insert_id;
    $stmt = $con->prepare("SELECT * FROM accounts WHERE account_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

// Set session
session_regenerate_id(true);
$_SESSION['auth'] = true;
$_SESSION['auth_user'] = [
    'account_id' => $user['account_id'],
    'name'       => $user['name'],
    'email'      => $user['email'],
    'role'       => $user['role']
];
$_SESSION['role'] = $user['role'];

// Redirect by role
switch($user['role']) {
    case 'superadmin':
        header("Location: ../superadmin/index.php"); break;
    case 'admin':
        header("Location: ../admin/dashboard.php"); break;
    default:
        header("Location: /index.php"); break;
}
exit();
