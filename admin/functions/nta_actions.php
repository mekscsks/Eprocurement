<?php
session_start();
include __DIR__ . '/../../config/localdb.php';

// Auto-create/migrate table
$con->query("CREATE TABLE IF NOT EXISTS `notice_to_award` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nta_number` VARCHAR(50) NOT NULL,
    `pr_id` INT DEFAULT NULL,
    `supplier` VARCHAR(255) DEFAULT '',
    `project` VARCHAR(500) DEFAULT '',
    `amount` DECIMAL(15,2) DEFAULT 0,
    `contact_name` VARCHAR(255) DEFAULT '',
    `contact_position` VARCHAR(255) DEFAULT '',
    `company_name` VARCHAR(255) DEFAULT '',
    `company_location` VARCHAR(255) DEFAULT '',
    `company_city` VARCHAR(255) DEFAULT '',
    `salutation` VARCHAR(10) DEFAULT 'Mr.',
    `status` ENUM('Pending','Completed') DEFAULT 'Pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

foreach (['contact_name VARCHAR(255) DEFAULT ""','contact_position VARCHAR(255) DEFAULT ""','company_name VARCHAR(255) DEFAULT ""','company_location VARCHAR(255) DEFAULT ""','company_city VARCHAR(255) DEFAULT ""','salutation VARCHAR(10) DEFAULT "Mr."'] as $col) {
    $con->query("ALTER TABLE notice_to_award ADD COLUMN IF NOT EXISTS $col");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── AJAX: get supplier details ────────────────────────────────────────────────
if ($action === 'get_supplier' && isset($_GET['name'])) {
    header('Content-Type: application/json');
    $name = trim($_GET['name']);
    $stmt = $con->prepare("SELECT name, company_name, address, contact_number, email_address, printed_name_signature, location FROM suppliers WHERE name = ? AND deleted = 0 LIMIT 1");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    echo json_encode($row ?: []);
    exit;
}

// ── Helper: generate next NTA number ─────────────────────────────────────────
function nextNtaNumber($con) {
    $year = date('Y');
    $res  = $con->query("SELECT nta_number FROM notice_to_award WHERE nta_number LIKE 'NTA-$year-%' ORDER BY id DESC LIMIT 1");
    $last = 0;
    if ($res && $res->num_rows > 0) {
        $parts = explode('-', $res->fetch_assoc()['nta_number']);
        $last  = (int)($parts[2] ?? 0);
    }
    return 'NTA-' . $year . '-' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
}

// ── mark_complete ─────────────────────────────────────────────────────────────
if ($action === 'mark_complete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $con->prepare("UPDATE notice_to_award SET status='Completed', updated_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['nta_alert'] = ['type' => 'success', 'msg' => 'Marked as Completed.'];
    }
    header('Location: ../nta.php');
    exit;
}

// ── create_from_procurement ───────────────────────────────────────────────────
if ($action === 'create_from_procurement') {
    $pr_id = (int)($_POST['procurement_id'] ?? 0);
    if ($pr_id <= 0) {
        $_SESSION['nta_alert'] = ['type' => 'error', 'msg' => 'Invalid PR ID.'];
        header('Location: ../nta.php'); exit;
    }

    $proc = $con->query("SELECT * FROM purchase_requests WHERE id = $pr_id AND deleted = 0 LIMIT 1")->fetch_assoc();
    if (!$proc) {
        $_SESSION['nta_alert'] = ['type' => 'error', 'msg' => 'Purchase request not found.'];
        header('Location: ../nta.php'); exit;
    }

    if ($con->query("SELECT id FROM notice_to_award WHERE pr_id = $pr_id LIMIT 1")->num_rows) {
        $_SESSION['nta_alert'] = ['type' => 'error', 'msg' => 'NTA already exists for this PR.'];
        header('Location: ../nta.php'); exit;
    }

    $supplier_name    = trim($_POST['supplier']          ?? '');
    $project          = trim($_POST['project']           ?? $proc['requested_by']);
    $amount           = is_numeric($_POST['amount'] ?? '') ? (float)$_POST['amount'] : (float)$proc['total_amount'];
    $salutation       = trim($_POST['salutation']        ?? 'Mr.');
    $contact_name     = trim($_POST['contact_name']      ?? '');
    $contact_position = trim($_POST['contact_position']  ?? '');
    $company_name     = trim($_POST['company_name']      ?? '');
    $company_location = trim($_POST['company_location']  ?? '');
    $company_city     = trim($_POST['company_city']      ?? '');

    // Auto-fill from suppliers table if fields are empty
    if ($supplier_name !== '' && ($contact_name === '' || $company_name === '')) {
        $sup = $con->query("SELECT * FROM suppliers WHERE name = '" . $con->real_escape_string($supplier_name) . "' AND deleted = 0 LIMIT 1")->fetch_assoc();
        if ($sup) {
            if ($contact_name     === '') $contact_name     = $sup['printed_name_signature'] ?? '';
            if ($company_name     === '') $company_name     = $sup['company_name']           ?? $sup['name'];
            if ($company_location === '') $company_location = $sup['address']                ?? $sup['location'] ?? '';
        }
    }

    $nta_number = nextNtaNumber($con);
    $stmt = $con->prepare("INSERT INTO notice_to_award (nta_number, pr_id, supplier, project, amount, contact_name, contact_position, company_name, company_location, company_city, salutation) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sissdssssss", $nta_number, $pr_id, $supplier_name, $project, $amount, $contact_name, $contact_position, $company_name, $company_location, $company_city, $salutation);
    $stmt->execute();

    $_SESSION['nta_alert'] = ['type' => 'success', 'msg' => "NTA '$nta_number' created for {$proc['pr_number']}."];
    header('Location: ../nta.php');
    exit;
}

// ── add manually ─────────────────────────────────────────────────────────────
if ($action === 'add') {
    $supplier         = trim($_POST['supplier']          ?? '');
    $project          = trim($_POST['project']           ?? '');
    $amount           = is_numeric($_POST['amount'] ?? '') ? (float)$_POST['amount'] : 0;
    $salutation       = trim($_POST['salutation']        ?? 'Mr.');
    $contact_name     = trim($_POST['contact_name']      ?? '');
    $contact_position = trim($_POST['contact_position']  ?? '');
    $company_name     = trim($_POST['company_name']      ?? '');
    $company_location = trim($_POST['company_location']  ?? '');
    $company_city     = trim($_POST['company_city']      ?? '');

    $nta_number = nextNtaNumber($con);
    $stmt = $con->prepare("INSERT INTO notice_to_award (nta_number, supplier, project, amount, contact_name, contact_position, company_name, company_location, company_city, salutation) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssdssssss", $nta_number, $supplier, $project, $amount, $contact_name, $contact_position, $company_name, $company_location, $company_city, $salutation);
    $stmt->execute();

    $_SESSION['nta_alert'] = ['type' => 'success', 'msg' => "NTA '$nta_number' created successfully."];
    header('Location: ../nta.php');
    exit;
}

header('Location: ../nta.php');
exit;
