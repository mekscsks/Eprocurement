<?php
include 'config/dbcon.php';

/**
 * Get user profile by account_id
 */
function getProfile($accountId) {
    global $con;

    $stmt = $con->prepare("SELECT * FROM accounts WHERE account_id = ?");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?? []; // returns empty array if not found
}

/**
 * Get company info by user account_id
 */
function getCompany($accountId) {
    global $con;

    $stmt = $con->prepare("SELECT * FROM companies WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?? [];
}
