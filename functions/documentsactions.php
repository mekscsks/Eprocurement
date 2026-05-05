<?php
include '../config/localdb.php';

function getDocumentsByUser($con, $account_id)
{
    $stmt = $con->prepare("
        SELECT * FROM documents 
        WHERE id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $account_id);
    $stmt->execute();
    return $stmt->get_result();
}

function searchDocumentByUser($con, $tracking_number, $account_id)
{
    $stmt = $con->prepare("
        SELECT * FROM documents 
        WHERE tracking_number = ? 
        AND account_id = ?
    ");
    $stmt->bind_param("si", $tracking_number, $account_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getDocumentById($con, $id, $account_id)
{
    $stmt = $con->prepare("
        SELECT * FROM documents 
        WHERE id = ? 
        AND account_id = ?
    ");
    $stmt->bind_param("ii", $id, $account_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getDocumentLogs($con, $doc_id)
{
    $stmt = $con->prepare("
        SELECT * FROM document_logs 
        WHERE document_id = ?
        ORDER BY updated_at ASC
    ");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getPRByUser($con, $account_id)
{
    $stmt = $con->prepare("
        SELECT * FROM purchase_requests
        WHERE account_id = ?
        AND deleted = 0
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $account_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getPPMPByUser($con, $account_id)
{
    $stmt = $con->prepare("SELECT * FROM tool_sub WHERE account_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $account_id);
    $stmt->execute();

    return $stmt->get_result();
}

if(isset($_POST['add_document'])){

    $tracking = "DOC-" . rand(100000,999999);
    $title = $con->real_escape_string($_POST['title']);
    $desc  = $con->real_escape_string($_POST['description']);

    $con->query("INSERT INTO documents 
        (tracking_number,title,description,current_status)
        VALUES ('$tracking','$title','$desc','Received')");

    $doc_id = $con->insert_id;

    $con->query("INSERT INTO document_logs
        (document_id,status,remarks)
        VALUES ('$doc_id','Received','Document received in office')");

    header("Location: index.php");
    exit();
}


if(isset($_POST['update_status'])){

    $doc_id = intval($_POST['document_id']);
    $status = $con->real_escape_string($_POST['status']);
    $remarks = $con->real_escape_string($_POST['remarks']);

    $con->query("UPDATE documents 
                 SET current_status='$status'
                 WHERE id=$doc_id");

    $con->query("INSERT INTO document_logs
        (document_id,status,remarks)
        VALUES ('$doc_id','$status','$remarks')");

    header("Location: index.php?view=$doc_id");
    exit();
}
?>