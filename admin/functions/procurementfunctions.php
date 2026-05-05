<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../../config/localdb.php';

/* =========================================
   GET ALL PROCUREMENTS
========================================= */
function getProcurements($con) {
    $query = "SELECT * FROM procurements WHERE deleted_at IS NULL ORDER BY created_at DESC";
    return mysqli_query($con, $query);
}

/* =========================================
   GET SINGLE PROCUREMENT BY ID
========================================= */
function getProcurementById($con, $id) {
    $stmt = $con->prepare("SELECT * FROM procurements WHERE id=? AND deleted_at IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/* =========================================
   CREATE PROCUREMENT
========================================= */
function createProcurement($con, $data, $files) {

    // Sanitize input
    $title       = $con->real_escape_string($data['proc_title']);
    $description = $con->real_escape_string($data['proc_description']);
    $mode        = $con->real_escape_string($data['proc_mode']);
    $nature      = $con->real_escape_string($data['proc_nature']);
    $budget      = floatval($data['proc_budget']);
    $status      = $con->real_escape_string($data['proc_status']);
    $winner      = $con->real_escape_string($data['proc_winner']);
    $start       = $data['proc_start_date'] ?: NULL;
    $prebid      = $data['proc_prebid'] ?: NULL;
    $deadline    = $data['proc_deadline'] ?: NULL;
    $bid_opening = $data['proc_bid_opening'] ?: NULL;
    $end         = $data['proc_end_date'] ?: NULL;

    // Auto-generate reference number
    $year = date("Y");
    $result = mysqli_query($con, "SELECT reference_no FROM procurements WHERE reference_no LIKE 'PROC-$year-%' ORDER BY id DESC LIMIT 1");
    $num = ($row = mysqli_fetch_assoc($result)) ? intval(substr($row['reference_no'], -4)) + 1 : 1;
    $reference_no = "PROC-$year-" . str_pad($num, 4, "0", STR_PAD_LEFT);

    // File uploads
    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $document_file = '';
    if (!empty($files['proc_bidding_doc']['name']) && $files['proc_bidding_doc']['error'] === 0) {
        $ext = strtolower(pathinfo($files['proc_bidding_doc']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx'];
        if(!in_array($ext, $allowed)) die("Invalid file type for main document.");
        $document_file = uniqid() . "_" . basename($files['proc_bidding_doc']['name']);
        move_uploaded_file($files['proc_bidding_doc']['tmp_name'], $uploadDir . $document_file);
    }

    $additional_doc = '';
    if (!empty($files['proc_additional_doc']['name']) && $files['proc_additional_doc']['error'] === 0) {
        $ext = strtolower(pathinfo($files['proc_additional_doc']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx'];
        if(!in_array($ext, $allowed)) die("Invalid file type for additional document.");
        $additional_doc = uniqid() . "_" . basename($files['proc_additional_doc']['name']);
        move_uploaded_file($files['proc_additional_doc']['tmp_name'], $uploadDir . $additional_doc);
    }

    // Insert into database
    $stmt = $con->prepare("
        INSERT INTO procurements 
        (title, description, mode, nature, reference_no, document_file, additional_doc, approved_budget, status, winning_bidder, start_date, prebid_datetime, deadline_datetime, bid_opening_datetime, end_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sssssssdsssssss",
        $title, $description, $mode, $nature, $reference_no, $document_file, $additional_doc,
        $budget, $status, $winner, $start, $prebid, $deadline, $bid_opening, $end
    );

    return $stmt->execute();
}

/* =========================================
   UPDATE PROCUREMENT
========================================= */
function updateProcurement($con, $data, $files) {

    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Handle main document
    $document_file = $data['existing_document_file'] ?? '';
    if (!empty($files['proc_bidding_doc']['name']) && $files['proc_bidding_doc']['error'] === 0) {
        $ext = strtolower(pathinfo($files['proc_bidding_doc']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx'];
        if(!in_array($ext, $allowed)) die("Invalid file type for main document.");
        $document_file = uniqid() . "_" . basename($files['proc_bidding_doc']['name']);
        move_uploaded_file($files['proc_bidding_doc']['tmp_name'], $uploadDir . $document_file);
    }

    // Handle additional document
    $additional_doc = $data['existing_additional_doc'] ?? '';
    if (!empty($files['proc_additional_doc']['name']) && $files['proc_additional_doc']['error'] === 0) {
        $ext = strtolower(pathinfo($files['proc_additional_doc']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx'];
        if(!in_array($ext, $allowed)) die("Invalid file type for additional document.");
        $additional_doc = uniqid() . "_" . basename($files['proc_additional_doc']['name']);
        move_uploaded_file($files['proc_additional_doc']['tmp_name'], $uploadDir . $additional_doc);
    }

    // Assign variables
    $title       = $data['proc_title'];
    $description = $data['proc_description'];
    $mode        = $data['proc_mode'];
    $nature      = $data['proc_nature'];
    $reference   = $data['reference_no'];
    $budget      = floatval($data['proc_budget']);
    $status      = $data['proc_status'];
    $winner      = $data['proc_winner'];
    $start       = $data['proc_start_date'] ?: NULL;
    $prebid      = $data['proc_prebid'] ?: NULL;
    $deadline    = $data['proc_deadline'] ?: NULL;
    $bid_opening = $data['proc_bid_opening'] ?: NULL;
    $end         = $data['proc_end_date'] ?: NULL;
    $id          = intval($data['id']);

    $stmt = $con->prepare("
        UPDATE procurements 
        SET title=?, description=?, mode=?, nature=?, reference_no=?, document_file=?, additional_doc=?, approved_budget=?, status=?, winning_bidder=?, start_date=?, prebid_datetime=?, deadline_datetime=?, bid_opening_datetime=?, end_date=? 
        WHERE id=?
    ");
    $stmt->bind_param(
        "sssssssdsssssssi",
        $title, $description, $mode, $nature, $reference, $document_file, $additional_doc,
        $budget, $status, $winner, $start, $prebid, $deadline, $bid_opening, $end, $id
    );

    return $stmt->execute();
}

/* =========================================
   SOFT DELETE PROCUREMENT
========================================= */
function deleteProcurement($con, $id){
    $stmt = $con->prepare("UPDATE procurements SET deleted_at = NOW() WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
?>