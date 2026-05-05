<?php
session_start();
include __DIR__ . '/../../config/localdb.php';

header('Content-Type: application/json');

$po_id = filter_input(INPUT_GET, 'po_id', FILTER_VALIDATE_INT);
if (!$po_id) { echo json_encode([]); exit; }

$stmt = $con->prepare("SELECT * FROM purchase_order_items WHERE po_id = ? ORDER BY id ASC");
$stmt->bind_param('i', $po_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode($rows);
