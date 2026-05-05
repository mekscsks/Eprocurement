<?php
session_start();
include __DIR__ . '/../../config/localdb.php';

$action = $_POST['action'] ?? '';

function ntpNumber($con) {
    $year = date('Y');
    $res  = $con->query("SELECT ntp_number FROM notice_to_proceed WHERE ntp_number LIKE 'NTP-$year-%' ORDER BY id DESC LIMIT 1");
    $last = 0;
    if ($res && $res->num_rows > 0) {
        $parts = explode('-', $res->fetch_assoc()['ntp_number']);
        $last  = (int)($parts[2] ?? 0);
    }
    return 'NTP-' . $year . '-' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
}

if ($action === 'edit') {
    $id               = (int)($_POST['id'] ?? 0);
    $ntp_number       = trim($_POST['ntp_number']       ?? '');
    $supplier         = trim($_POST['supplier']         ?? '');
    $project          = trim($_POST['project']          ?? '');
    $amount           = is_numeric($_POST['amount'] ?? '') ? (float)$_POST['amount'] : 0;
    $delivery_days    = max(1, (int)($_POST['delivery_days'] ?? 30));
    $status           = in_array($_POST['status'] ?? '', ['Pending','Completed']) ? $_POST['status'] : 'Pending';
    $contact_name     = trim($_POST['contact_name']     ?? '');
    $contact_position = trim($_POST['contact_position'] ?? '');
    $company_name     = trim($_POST['company_name']     ?? '');
    $company_location = trim($_POST['company_location'] ?? '');
    $company_city     = trim($_POST['company_city']     ?? '');
    $salutation       = trim($_POST['salutation']       ?? 'Mr.');

    if ($id > 0) {
        $stmt = $con->prepare("UPDATE notice_to_proceed SET ntp_number=?, supplier=?, project=?, amount=?, delivery_days=?, status=?, contact_name=?, contact_position=?, company_name=?, company_location=?, company_city=?, salutation=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssdisssssssi", $ntp_number, $supplier, $project, $amount, $delivery_days, $status, $contact_name, $contact_position, $company_name, $company_location, $company_city, $salutation, $id);
        $stmt->execute();
        $_SESSION['ntp_alert'] = ['type' => 'success', 'msg' => 'NTP updated successfully.'];
    }
    header('Location: ../ntp.php');
    exit;
}

if ($action === 'mark_complete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $con->prepare("UPDATE notice_to_proceed SET status='Completed', updated_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['ntp_alert'] = ['type' => 'success', 'msg' => 'Marked as Completed.'];
    }
    header('Location: ../ntp.php');
    exit;
}

if ($action === 'create_from_nta') {
    $nta_id = (int)($_POST['nta_id'] ?? 0);
    if ($nta_id <= 0) {
        $_SESSION['ntp_alert'] = ['type' => 'error', 'msg' => 'Invalid NTA ID.'];
        header('Location: ../ntp.php'); exit;
    }

    $exists = $con->query("SELECT id FROM notice_to_proceed WHERE nta_id = $nta_id LIMIT 1")->num_rows;
    if ($exists) {
        $_SESSION['ntp_alert'] = ['type' => 'error', 'msg' => 'NTP already exists for this NTA.'];
        header('Location: ../ntp.php'); exit;
    }

    $supplier         = trim($_POST['supplier']         ?? '');
    $project          = trim($_POST['project']          ?? '');
    $amount           = is_numeric($_POST['amount'] ?? '') ? (float)$_POST['amount'] : 0;
    $delivery_days    = max(1, (int)($_POST['delivery_days'] ?? 30));
    $contact_name     = trim($_POST['contact_name']     ?? '');
    $contact_position = trim($_POST['contact_position'] ?? '');
    $company_name     = trim($_POST['company_name']     ?? '');
    $company_location = trim($_POST['company_location'] ?? '');
    $company_city     = trim($_POST['company_city']     ?? '');
    $salutation       = trim($_POST['salutation']       ?? 'Mr.');

    $ntp_number = ntpNumber($con);

    $stmt = $con->prepare("INSERT INTO notice_to_proceed (ntp_number, nta_id, supplier, project, amount, delivery_days, contact_name, contact_position, company_name, company_location, company_city, salutation) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sissdissssss", $ntp_number, $nta_id, $supplier, $project, $amount, $delivery_days, $contact_name, $contact_position, $company_name, $company_location, $company_city, $salutation);
    $stmt->execute();
    $_SESSION['ntp_alert'] = ['type' => 'success', 'msg' => "NTP '$ntp_number' created successfully."];
    header('Location: ../ntp.php');
    exit;
}

if ($action === 'add') {
    $supplier      = trim($_POST['supplier']      ?? '');
    $project       = trim($_POST['project']       ?? '');
    $amount        = is_numeric($_POST['amount'] ?? '') ? (float)$_POST['amount'] : 0;
    $delivery_days = max(1, (int)($_POST['delivery_days'] ?? 30));

    $ntp_number = ntpNumber($con);

    $stmt = $con->prepare("INSERT INTO notice_to_proceed (ntp_number, supplier, project, amount, delivery_days) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssdi", $ntp_number, $supplier, $project, $amount, $delivery_days);
    $stmt->execute();
    $_SESSION['ntp_alert'] = ['type' => 'success', 'msg' => "NTP '$ntp_number' created successfully."];
    header('Location: ../ntp.php');
    exit;
}

header('Location: ../ntp.php');
exit;
