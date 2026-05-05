<?php

function getProcurements($con, $search = '', $office = '', $status = '', $limit = 10, $offset = 0)
{
    $sql = "SELECT * FROM procurements WHERE deleted_at IS NULL";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " AND title LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }

    if (!empty($office)) {
        $sql .= " AND office = ?";
        $params[] = $office;
        $types .= "s";
    }

    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    return $stmt->get_result();
}


function countProcurements($con, $search = '', $office = '', $status = '')
{
    $sql = "SELECT COUNT(*) as total FROM procurements WHERE deleted_at IS NULL";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " AND title LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }

    if (!empty($office)) {
        $sql .= " AND office = ?";
        $params[] = $office;
        $types .= "s";
    }

    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $stmt = $con->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'];
}


function updateStatus($con, $id, $status)
{
    $stmt = $con->prepare("UPDATE procurements SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    return $stmt->execute();
}


function deleteProcurement($con, $id)
{
    $stmt = $con->prepare("UPDATE procurements SET deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}