<?php
/**
 * Reusable brute-force protection helpers
 */

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCK_TIME_MINUTES', 15);

/**
 * Check if account is locked
 */
function isAccountLocked(array $user): bool
{
    if (empty($user['lock_until'])) {
        return false;
    }

    return strtotime($user['lock_until']) > time();
}

/**
 * Handle failed login attempt
 */
function registerFailedAttempt(mysqli $con, int $userId, int $currentAttempts): void
{
    $attempts = $currentAttempts + 1;

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $lockUntil = date(
            'Y-m-d H:i:s',
            strtotime('+' . LOCK_TIME_MINUTES . ' minutes')
        );

        $stmt = $con->prepare(
            "UPDATE accounts 
             SET failed_attempts = ?, lock_until = ?
             WHERE account_id = ?"
        );
        $stmt->bind_param("isi", $attempts, $lockUntil, $userId);
    } else {
        $stmt = $con->prepare(
            "UPDATE accounts 
             SET failed_attempts = ?
             WHERE account_id = ?"
        );
        $stmt->bind_param("ii", $attempts, $userId);
    }

    $stmt->execute();
    $stmt->close();
}

/**
 * Reset attempts after successful login
 */
function resetLoginAttempts(mysqli $con, int $userId): void
{
    $stmt = $con->prepare(
        "UPDATE accounts 
         SET failed_attempts = 0, lock_until = NULL
         WHERE account_id = ?"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get remaining attempts
 */
function remainingAttempts(int $currentAttempts): int
{
    return max(0, MAX_LOGIN_ATTEMPTS - $currentAttempts);
}
