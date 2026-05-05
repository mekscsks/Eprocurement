<?php
// support/auth.php — shared bootstrap for all support endpoints

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/localdb.php'; // provides $con (MySQLi)

// ── helpers ──────────────────────────────────────────────────────────────────

function auth_required(array $allowed_roles = []): array {
    if (empty($_SESSION['auth_user']['account_id'])) {
        http_response_code(401);
        die(json_encode(['error' => 'Unauthenticated']));
    }
    $user = [
        'id'   => (int) $_SESSION['auth_user']['account_id'],
        'role' => $_SESSION['auth_user']['role'] ?? '',
        'name' => $_SESSION['auth_user']['name']  ?? '',
    ];
    if ($allowed_roles && !in_array($user['role'], $allowed_roles, true)) {
        http_response_code(403);
        die(json_encode(['error' => 'Forbidden']));
    }
    return $user;
}

function json_out(mixed $data): void {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function get_or_create_thread(mysqli $con, int $account_id, string $role): int {
    $stmt = $con->prepare(
        "SELECT id FROM support_threads WHERE account_id=? AND status='open' LIMIT 1"
    );
    $stmt->bind_param('i', $account_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return (int) $row['id'];

    $stmt = $con->prepare(
        "INSERT INTO support_threads (account_id, role) VALUES (?, ?)"
    );
    $stmt->bind_param('is', $account_id, $role);
    $stmt->execute();
    return (int) $con->insert_id;
}

// Allowed MIME types for uploads
const ALLOWED_MIME = ['image/jpeg','image/png','application/pdf'];
const MAX_UPLOAD   = 5 * 1024 * 1024; // 5 MB
const UPLOAD_DIR   = __DIR__ . '/uploads/';
