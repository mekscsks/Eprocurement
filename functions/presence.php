<?php

function ensurePresenceColumns(mysqli $con): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $hasLastSeen = false;
    $hasIsOnline = false;

    if ($result = $con->query("SHOW COLUMNS FROM accounts LIKE 'last_seen'")) {
        $hasLastSeen = $result->num_rows > 0;
        $result->free();
    }

    if ($result = $con->query("SHOW COLUMNS FROM accounts LIKE 'is_online'")) {
        $hasIsOnline = $result->num_rows > 0;
        $result->free();
    }

    if (!$hasLastSeen) {
        $con->query("ALTER TABLE accounts ADD COLUMN last_seen DATETIME NULL DEFAULT NULL AFTER last_password_changed_by");
    }

    if (!$hasIsOnline) {
        $con->query("ALTER TABLE accounts ADD COLUMN is_online TINYINT(1) NOT NULL DEFAULT 0 AFTER last_seen");
    }

    $ensured = true;
}

function markUserPresence(mysqli $con, int $accountId, bool $isOnline): void
{
    if ($accountId <= 0) {
        return;
    }

    ensurePresenceColumns($con);

    $online = $isOnline ? 1 : 0;
    $stmt = $con->prepare("UPDATE accounts SET last_seen = NOW(), is_online = ? WHERE account_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ii", $online, $accountId);
        $stmt->execute();
        $stmt->close();
    }
}

function touchUserPresence(mysqli $con, int $accountId): void
{
    markUserPresence($con, $accountId, true);
}
