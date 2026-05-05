<?php
if (!isset($_SESSION)) session_start();
include __DIR__ . '/../../config/localdb.php';
include __DIR__ . '/authorization.php';

header('Content-Type: application/json');

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where  = ['deleted = 0'];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(pr_number LIKE ? OR office LIKE ? OR requested_by LIKE ?)';
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}
if ($status !== '') {
    $where[]  = 'status = ?';
    $params[] = $status;
    $types   .= 's';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// total count
$countStmt = $con->prepare("SELECT COUNT(*) FROM purchase_requests $whereSQL");
if ($types) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countStmt->bind_result($total);
$countStmt->fetch();
$countStmt->close();

// records
$sql  = "SELECT id, pr_number, requested_by, office, total_amount, status, created_at
         FROM purchase_requests $whereSQL ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $con->prepare($sql);
$allParams = array_merge($params, [$limit, $offset]);
$allTypes  = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

echo json_encode([
    'total'   => (int)$total,
    'pages'   => (int)ceil($total / $limit),
    'current' => $page,
    'data'    => $rows,
]);
