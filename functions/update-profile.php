<?php
session_start();
include 'config/dbcon.php';

// Check if user is logged in
$accountId = $_SESSION['auth_user']['account_id'] ?? 0;
if (!$accountId) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

// Sanitize input
$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

// List of allowed fields to update
$allowedFields = ['department', 'phone', 'username', 'name'];

if (!in_array($field, $allowedFields)) {
    http_response_code(400);
    echo "Invalid field";
    exit;
}

if ($field === 'phone' && !preg_match('/^\d+$/', $value)) {
    http_response_code(400);
    echo "Phone must be numeric";
    exit;
}

// Prepare SQL statement
$stmt = $con->prepare("UPDATE accounts SET $field = ? WHERE account_id = ?");
$stmt->bind_param("si", $value, $accountId);

if ($stmt->execute()) {
    // Update session variable if username or other fields are updated
    $_SESSION['auth_user'][$field] = $value;
    echo "Success";
} else {
    http_response_code(500);
    echo "Database error";
}

$stmt->close();
$con->close();
