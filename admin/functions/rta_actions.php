<?php
session_start();
include __DIR__ . '/../../config/localdb.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// GET suppliers for a PR
if ($action === 'get_suppliers') {
    $pr_id = (int)($_GET['pr_id'] ?? 0);
    if ($pr_id <= 0) { echo json_encode([]); exit; }

    $stmt = $con->prepare("
        SELECT ps.id, ps.supplier_id, ps.quoted_amount, ps.is_winner, s.name, s.supplier_code
        FROM pr_suppliers ps
        JOIN suppliers s ON s.id = ps.supplier_id
        WHERE ps.pr_id = ? AND s.deleted = 0
        ORDER BY ps.id ASC
    ");
    $stmt->bind_param('i', $pr_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode($rows);
    exit;
}

// SAVE suppliers for a PR
if ($action === 'save_suppliers') {
    $pr_id          = (int)($_POST['pr_id'] ?? 0);
    $supplier_ids   = $_POST['supplier_id'] ?? [];
    $quoted_amounts = $_POST['quoted_amount'] ?? [];

    if ($pr_id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid PR']); exit; }

    $del = $con->prepare("DELETE FROM pr_suppliers WHERE pr_id = ?");
    $del->bind_param('i', $pr_id);
    $del->execute();
    $del->close();

    foreach ($supplier_ids as $i => $sid) {
        $sid = (int)$sid;
        if ($sid <= 0) continue;
        $amount = is_numeric($quoted_amounts[$i] ?? '') ? (float)$quoted_amounts[$i] : null;
        $ins = $con->prepare("INSERT INTO pr_suppliers (pr_id, supplier_id, quoted_amount, is_winner) VALUES (?, ?, ?, 1)");
        $ins->bind_param('iid', $pr_id, $sid, $amount);
        $ins->execute();
        $ins->close();
    }

    echo json_encode(['success' => true]);
    exit;
}

// GET all active suppliers for dropdown
if ($action === 'list_suppliers') {
    $res  = $con->query("SELECT id, supplier_code, name FROM suppliers WHERE deleted = 0 AND status = 'Active' ORDER BY name ASC");
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode($rows);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
