<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/localdb.php';
include 'functions/authorization.php';

$redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'planning.php');
$redirectParts = parse_url($redirect);
if (is_array($redirectParts) && (isset($redirectParts['scheme']) || isset($redirectParts['host']))) {
    $redirect = 'planning.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect");
    exit;
}

function ensureToolSubAppColumns(mysqli $con): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $needed = [
        'general_requirements' => "ALTER TABLE tool_sub ADD COLUMN general_requirements TEXT NULL AFTER remarks",
        'miscellaneous_items' => "ALTER TABLE tool_sub ADD COLUMN miscellaneous_items TEXT NULL AFTER general_requirements",
        'cse_ps_dbm' => "ALTER TABLE tool_sub ADD COLUMN cse_ps_dbm VARCHAR(20) NULL AFTER miscellaneous_items",
    ];

    foreach ($needed as $column => $sql) {
        $exists = $con->query("SHOW COLUMNS FROM `tool_sub` LIKE '{$column}'");
        if ($exists && $exists->num_rows === 0) {
            $con->query($sql);
        }
        if ($exists instanceof mysqli_result) {
            $exists->free();
        }
    }
}

function ensureToolSubQuantityColumn(mysqli $con): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $result = $con->query("SHOW COLUMNS FROM `tool_sub` LIKE 'quantity'");
    $row = $result ? $result->fetch_assoc() : null;
    if ($result instanceof mysqli_result) {
        $result->free();
    }

    $type = strtolower((string) ($row['Type'] ?? ''));
    if ($type !== '' && strpos($type, 'varchar') === false && strpos($type, 'text') === false) {
        $con->query("ALTER TABLE tool_sub MODIFY COLUMN quantity VARCHAR(255) NULL");
    }
}

function ensureToolSubUserUpdateColumn(mysqli $con): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $result = $con->query("SHOW COLUMNS FROM `tool_sub` LIKE 'allow_user_update'");
    $exists = $result && $result->num_rows > 0;
    if ($result instanceof mysqli_result) {
        $result->free();
    }

    if (!$exists) {
        $con->query("ALTER TABLE tool_sub ADD COLUMN allow_user_update TINYINT(1) NOT NULL DEFAULT 0 AFTER is_hidden");
    }
}

function normalizeProcurementMode(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $allowed = [
        'Small Value Procurement',
        'Shopping',
        'Public Bidding',
        'Direct Contracting',
        'Repeat Order',
    ];

    if (in_array($value, $allowed, true)) {
        return $value;
    }

    $map = [
        'Section 27 - Competitive Bidding' => 'Public Bidding',
        'Section 27 � Competitive Bidding' => 'Public Bidding',
        'Competitive Bidding' => 'Public Bidding',
        'Section 28 - Limited Source Bidding' => 'Public Bidding',
        'Section 28 � Limited Source Bidding' => 'Public Bidding',
        'Limited Source Bidding' => 'Public Bidding',
        'Section 29 - Competitive Dialogue' => 'Public Bidding',
        'Section 29 � Competitive Dialogue' => 'Public Bidding',
        'Section 30 - Unsolicited Offer with Bid Matching' => 'Public Bidding',
        'Section 30 � Unsolicited Offer with Bid Matching' => 'Public Bidding',
        'Section 31 - Direct Contracting' => 'Direct Contracting',
        'Section 31 � Direct Contracting' => 'Direct Contracting',
        'Section 32 - Direct Acquisition' => 'Shopping',
        'Section 32 � Direct Acquisition' => 'Shopping',
        'Section 33 - Repeat Order' => 'Repeat Order',
        'Section 33 � Repeat Order' => 'Repeat Order',
        'Section 34 - Small Value Procurement' => 'Small Value Procurement',
        'Section 34 � Small Value Procurement' => 'Small Value Procurement',
        'Section 35 - Negotiated Procurement' => 'Shopping',
        'Section 35 � Negotiated Procurement' => 'Shopping',
        'Negotiated Procurement' => 'Shopping',
        'Section 36 - Direct Sales' => 'Direct Contracting',
        'Section 36 � Direct Sales' => 'Direct Contracting',
        'Section 37 - Direct Procurement for Science, Technology and Innovation' => 'Direct Contracting',
        'Section 37 � Direct Procurement for Science, Technology and Innovation' => 'Direct Contracting',
    ];

    return $map[$value] ?? null;
}

function normalizePreproc(?string $value): ?int
{
    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return null;
    }

    if (in_array($value, ['yes', '1', 'true'], true)) {
        return 1;
    }

    if (in_array($value, ['no', '0', 'false'], true)) {
        return 0;
    }

    return null;
}

if (isset($_POST['set_status'], $_POST['id'])) {
    $id = intval($_POST['id']);
    $status = trim($_POST['set_status']);
    $notes = $_POST['notes'] ?? '';

    if ($id <= 0 || $status === '') {
        $_SESSION['swal'] = ['title' => 'Invalid request', 'text' => 'Invalid request.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt = $con->prepare("UPDATE tool_sub SET status = ? WHERE id = ?");
    if (!$stmt) {
        $_SESSION['swal'] = ['title' => 'Database error', 'text' => 'Failed to update status.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt->bind_param("si", $status, $id);
    $ok = $stmt->execute();
    $stmt->close();

    $_SESSION['swal'] = $ok
        ? ['title' => 'Updated', 'text' => 'Status updated successfully.', 'icon' => 'success']
        : ['title' => 'Failed', 'text' => 'Failed to update status.', 'icon' => 'error'];

    header("Location: $redirect");
    exit;
}

if (isset($_POST['upload_ppmp'])) {
    $email = trim($_POST['email'] ?? '');
    $ppmp_type = trim($_POST['ppmp_type'] ?? '');
    $office = trim($_POST['office'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if ($email === '' || $ppmp_type === '' || $office === '' || $unit === '') {
        $errors[] = 'Please fill out all required fields.';
    }

    if (!isset($_FILES['ppmp_file']) || !is_array($_FILES['ppmp_file'])) {
        $errors[] = 'No file uploaded.';
    }

    $file = $_FILES['ppmp_file'] ?? null;
    if (!$errors && $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed.';
        } else {
            $fileName = $file['name'] ?? '';
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'xls'], true)) {
                $errors[] = 'Invalid file type. Allowed: xlsx, xls.';
            }

            $maxBytes = 10 * 1024 * 1024;
            if (($file['size'] ?? 0) > $maxBytes) {
                $errors[] = 'File too large. Max 10MB.';
            }
        }
    }

    $account_id = $_SESSION['auth_user']['account_id'] ?? null;
    if (!$errors && !$account_id) {
        $errors[] = 'You must be logged in.';
    }

    if ($errors) {
        $_SESSION['swal'] = ['title' => 'Upload failed', 'text' => implode(' ', $errors), 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $uploadDir = dirname(__DIR__) . '/uploads/ppmp/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $safeBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($file['name']));
    $uniqueName = time() . '_' . $safeBase;
    $targetPath = $uploadDir . $uniqueName;

    if (!is_dir($uploadDir) || !move_uploaded_file($file['tmp_name'], $targetPath)) {
        $_SESSION['swal'] = ['title' => 'Upload failed', 'text' => 'Failed to save uploaded file.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt = $con->prepare("INSERT INTO tool_sub (account_id, email, ppmp_type, office, unit, file_path, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    if (!$stmt) {
        @unlink($targetPath);
        $_SESSION['swal'] = ['title' => 'Database error', 'text' => 'Failed to save record.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt->bind_param("isssss", $account_id, $email, $ppmp_type, $office, $unit, $uniqueName);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        @unlink($targetPath);
        $_SESSION['swal'] = ['title' => 'Upload failed', 'text' => 'Failed to save record.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $_SESSION['swal'] = ['title' => 'Uploaded', 'text' => 'PPMP uploaded successfully.', 'icon' => 'success'];
    header("Location: $redirect");
    exit;
}

if (isset($_POST['reupload_ppmp'], $_POST['id'])) {
    $id = intval($_POST['id']);
    $errors = [];
    if ($id <= 0) {
        $errors[] = 'Invalid submission.';
    }

    if (!isset($_FILES['ppmp_file']) || !is_array($_FILES['ppmp_file'])) {
        $errors[] = 'No file uploaded.';
    }

    $file = $_FILES['ppmp_file'] ?? null;
    if (!$errors && $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed.';
        } else {
            $fileName = $file['name'] ?? '';
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'xls'], true)) {
                $errors[] = 'Invalid file type. Allowed: xlsx, xls.';
            }

            $maxBytes = 10 * 1024 * 1024;
            if (($file['size'] ?? 0) > $maxBytes) {
                $errors[] = 'File too large. Max 10MB.';
            }
        }
    }

    $oldFile = null;
    if (!$errors) {
        $stmt = $con->prepare("SELECT file_path FROM tool_sub WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $errors[] = 'Database error.';
        } else {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if (!$row) {
                $errors[] = 'Submission not found.';
            } else {
                $oldFile = $row['file_path'] ?? null;
            }
        }
    }

    if ($errors) {
        $_SESSION['swal'] = ['title' => 'Reupload failed', 'text' => implode(' ', $errors), 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $uploadDir = dirname(__DIR__) . '/uploads/ppmp/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $safeBase = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($file['name']));
    $uniqueName = time() . '_' . $safeBase;
    $targetPath = $uploadDir . $uniqueName;

    if (!is_dir($uploadDir) || !move_uploaded_file($file['tmp_name'], $targetPath)) {
        $_SESSION['swal'] = ['title' => 'Reupload failed', 'text' => 'Failed to save uploaded file.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt = $con->prepare("UPDATE tool_sub SET file_path = ?, status = 'Pending' WHERE id = ?");
    if (!$stmt) {
        @unlink($targetPath);
        $_SESSION['swal'] = ['title' => 'Database error', 'text' => 'Failed to update record.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt->bind_param("si", $uniqueName, $id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        @unlink($targetPath);
        $_SESSION['swal'] = ['title' => 'Reupload failed', 'text' => 'Failed to update record.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $baseOld = $oldFile ? basename($oldFile) : null;
    if ($baseOld) {
        $oldCandidates = [
            $uploadDir . $baseOld,
            dirname(__DIR__) . '/uploads/' . $baseOld
        ];
        foreach ($oldCandidates as $p) {
            if (is_file($p) && basename($p) !== $uniqueName) {
                @unlink($p);
                break;
            }
        }
    }

    $_SESSION['swal'] = ['title' => 'Reuploaded', 'text' => 'PPMP file updated successfully.', 'icon' => 'success'];
    header("Location: $redirect");
    exit;
}

if (isset($_POST['ppmp_id'], $_POST['status'])) {
    $ppmp_id = intval($_POST['ppmp_id']);
    $status = strtolower(trim($_POST['status']));

    $redirectPath = parse_url($redirect, PHP_URL_PATH) ?? '';
    $isPpmpSubmit = $redirectPath !== '' && substr($redirectPath, -strlen('ppmpsubmit.php')) === 'ppmpsubmit.php';

    $allowed = ['approved', 'rejected', 'supplemental'];
    if ($ppmp_id <= 0 || !in_array($status, $allowed, true)) {
        if ($isPpmpSubmit) {
            header("Location: ppmpsubmit.php?error=invalid");
        } else {
            $_SESSION['swal'] = ['title' => 'Invalid request', 'text' => 'Invalid status.', 'icon' => 'error'];
            header("Location: $redirect");
        }
        exit;
    }

    $stmt = $con->prepare("SELECT status FROM tool_sub WHERE id = ? LIMIT 1");
    if (!$stmt) {
        if ($isPpmpSubmit) {
            header("Location: ppmpsubmit.php?error=invalid");
        } else {
            $_SESSION['swal'] = ['title' => 'Database error', 'text' => 'Failed to check status.', 'icon' => 'error'];
            header("Location: $redirect");
        }
        exit;
    }

    $stmt->bind_param("i", $ppmp_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        if ($isPpmpSubmit) {
            header("Location: ppmpsubmit.php?error=not_found");
        } else {
            $_SESSION['swal'] = ['title' => 'Not found', 'text' => 'PPMP not found.', 'icon' => 'error'];
            header("Location: $redirect");
        }
        exit;
    }

    $current = strtolower(trim($row['status'] ?? ''));
    if ($current === 'approved') {
        if ($isPpmpSubmit) {
            header("Location: ppmpsubmit.php?error=locked");
        } else {
            $_SESSION['swal'] = ['title' => 'Locked', 'text' => 'Already approved, cannot be changed.', 'icon' => 'info'];
            header("Location: $redirect");
        }
        exit;
    }

    $stmt = $con->prepare("UPDATE tool_sub SET status = ? WHERE id = ?");
    if (!$stmt) {
        if ($isPpmpSubmit) {
            header("Location: ppmpsubmit.php?error=invalid");
        } else {
            $_SESSION['swal'] = ['title' => 'Database error', 'text' => 'Failed to update status.', 'icon' => 'error'];
            header("Location: $redirect");
        }
        exit;
    }

    $stmt->bind_param("si", $status, $ppmp_id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($isPpmpSubmit) {
        header("Location: ppmpsubmit.php?" . ($ok ? "success=updated" : "error=invalid"));
    } else {
        $_SESSION['swal'] = $ok
            ? ['title' => 'Updated', 'text' => 'Status updated successfully.', 'icon' => 'success']
            : ['title' => 'Failed', 'text' => 'Failed to update status.', 'icon' => 'error'];
        header("Location: $redirect");
    }
    exit;
}

if (isset($_POST['edit_app'], $_POST['edit_app_id'])) {
    ensureToolSubAppColumns($con);

    $id               = intval($_POST['edit_app_id']);
    $ppmp_type        = trim($_POST['ppmp_type'] ?? '');
    $description      = trim($_POST['description'] ?? '');
    $procurement_mode = normalizeProcurementMode($_POST['procurement_mode'] ?? '');
    $preproc          = normalizePreproc($_POST['preproc'] ?? '');
    $project_type     = trim($_POST['project_type'] ?? '');
    $start_date       = trim($_POST['start_date'] ?? '') ?: null;
    $end_date         = trim($_POST['end_date'] ?? '') ?: null;
    $source_funds     = trim($_POST['source_funds'] ?? '');
    $budget           = $_POST['budget'] !== '' ? (float)$_POST['budget'] : null;
    $delivery_period  = trim($_POST['delivery_period'] ?? '');
    $remarks          = trim($_POST['remarks'] ?? '');
    $general_requirements = trim($_POST['general_requirements'] ?? '');
    $miscellaneous_items = trim($_POST['miscellaneous_items'] ?? '');
    $cse_ps_dbm = trim($_POST['cse_ps_dbm'] ?? '');

    if ($id <= 0) {
        $_SESSION['swal'] = ['title' => 'Invalid request', 'text' => 'Invalid entry.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt = $con->prepare("UPDATE tool_sub SET ppmp_type=?, description=?, procurement_mode=?, preproc=?, project_type=?, start_date=?, end_date=?, source_funds=?, budget=?, delivery_period=?, remarks=?, general_requirements=?, miscellaneous_items=?, cse_ps_dbm=? WHERE id=?");
    $stmt->bind_param("ssssssssdsssssi", $ppmp_type, $description, $procurement_mode, $preproc, $project_type, $start_date, $end_date, $source_funds, $budget, $delivery_period, $remarks, $general_requirements, $miscellaneous_items, $cse_ps_dbm, $id);
    $ok = $stmt->execute();
    $stmt->close();

    $_SESSION['swal'] = $ok
        ? ['title' => 'Updated', 'text' => 'APP entry updated successfully.', 'icon' => 'success']
        : ['title' => 'Failed', 'text' => 'Failed to update entry.', 'icon' => 'error'];
    header("Location: $redirect");
    exit;
}

if (isset($_POST['edit_ppmp'], $_POST['edit_ppmp_id'])) {
    ensureToolSubQuantityColumn($con);
    ensureToolSubUserUpdateColumn($con);

    $id               = intval($_POST['edit_ppmp_id']);
    $ppmp_type        = trim($_POST['ppmp_type'] ?? '');
    $office           = trim($_POST['office'] ?? '');
    $unit             = trim($_POST['unit'] ?? '');
    $description      = preg_replace('/\s+/', ' ', trim($_POST['description'] ?? ''));
    $project_type     = trim($_POST['project_type'] ?? '');
    $quantity         = trim($_POST['quantity'] ?? '');
    $procurement_mode = normalizeProcurementMode($_POST['procurement_mode'] ?? '');
    $preproc          = normalizePreproc($_POST['preproc'] ?? '');
    $start_date       = trim($_POST['start_date'] ?? '') ?: null;
    $end_date         = trim($_POST['end_date'] ?? '') ?: null;
    $delivery_period  = trim($_POST['delivery_period'] ?? '');
    $source_funds     = trim($_POST['source_funds'] ?? '');
    $budget           = $_POST['budget'] !== '' ? (float) $_POST['budget'] : null;
    $remarks          = trim($_POST['remarks'] ?? '');

    if ($id <= 0 || $ppmp_type === '' || $office === '' || $unit === '' || $description === '') {
        $_SESSION['swal'] = ['title' => 'Validation error', 'text' => 'Please fill out all required fields.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt = $con->prepare("UPDATE tool_sub SET ppmp_type=?, office=?, unit=?, description=?, project_type=?, quantity=?, procurement_mode=?, preproc=?, start_date=?, end_date=?, delivery_period=?, source_funds=?, budget=?, remarks=? WHERE id=?");
    if (!$stmt) {
        $_SESSION['swal'] = ['title' => 'Database error', 'text' => 'Failed to update PPMP entry.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt->bind_param(
        "ssssssssssssdsi",
        $ppmp_type,
        $office,
        $unit,
        $description,
        $project_type,
        $quantity,
        $procurement_mode,
        $preproc,
        $start_date,
        $end_date,
        $delivery_period,
        $source_funds,
        $budget,
        $remarks,
        $id
    );
    $ok = $stmt->execute();
    $stmt->close();

    $_SESSION['swal'] = $ok
        ? ['title' => 'Updated', 'text' => 'PPMP entry updated successfully.', 'icon' => 'success']
        : ['title' => 'Failed', 'text' => 'Failed to update PPMP entry.', 'icon' => 'error'];
    header("Location: $redirect");
    exit;
}

if (isset($_POST['create_ppmp'])) {
    ensureToolSubQuantityColumn($con);

    $ppmp_type        = trim($_POST['ppmp_type'] ?? '');
    $office           = trim($_POST['office'] ?? '');
    $unit             = trim($_POST['unit'] ?? '');
    $description      = preg_replace('/\s+/', ' ', trim($_POST['description'] ?? ''));
    $project_type     = trim($_POST['project_type'] ?? '');
    $quantity         = trim($_POST['quantity'] ?? '');
    $procurement_mode = normalizeProcurementMode($_POST['procurement_mode'] ?? '');
    $preproc          = normalizePreproc($_POST['preproc'] ?? '');
    $start_date       = trim($_POST['start_date'] ?? '') ?: null;
    $end_date         = trim($_POST['end_date'] ?? '') ?: null;
    $delivery_period  = trim($_POST['delivery_period'] ?? '');
    $source_funds     = trim($_POST['source_funds'] ?? '');
    $budget           = $_POST['budget'] !== '' ? (float)$_POST['budget'] : null;
    $remarks          = trim($_POST['remarks'] ?? '');
    $account_id       = $_SESSION['auth_user']['account_id'] ?? null;

    if ($ppmp_type === '' || $office === '' || $unit === '' || $description === '') {
        $_SESSION['swal'] = ['title' => 'Validation Error', 'text' => 'Please fill out all required fields.', 'icon' => 'error'];
        header("Location: $redirect");
        exit;
    }

    $stmt = $con->prepare("INSERT INTO tool_sub (account_id, ppmp_type, office, unit, description, project_type, quantity, procurement_mode, preproc, start_date, end_date, delivery_period, source_funds, budget, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("issssssssssssds",
        $account_id, $ppmp_type, $office, $unit, $description,
        $project_type, $quantity, $procurement_mode, $preproc,
        $start_date, $end_date, $delivery_period, $source_funds, $budget, $remarks
    );
    $ok = $stmt->execute();
    $stmt->close();

    $_SESSION['swal'] = $ok
        ? ['title' => 'Created', 'text' => 'PPMP entry created successfully.', 'icon' => 'success']
        : ['title' => 'Failed', 'text' => 'Failed to create PPMP entry.', 'icon' => 'error'];
    header("Location: $redirect");
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'remove_ppmp' && isset($_POST['ppmp_id'])) {
    header('Content-Type: application/json');
    if (($_SESSION['role'] ?? null) !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }
    $ppmp_id = intval($_POST['ppmp_id']);
    if ($ppmp_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }

    $colCheck = $con->query("SHOW COLUMNS FROM `tool_sub` LIKE 'is_hidden'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $con->query("ALTER TABLE tool_sub ADD COLUMN is_hidden TINYINT(1) DEFAULT 0");
    }
    if ($colCheck instanceof mysqli_result) {
        $colCheck->free();
    }

    $stmt = $con->prepare("UPDATE tool_sub SET is_hidden = 1 WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare request.']);
        exit;
    }
    $stmt->bind_param("i", $ppmp_id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Record removed.' : 'Failed to remove record.']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'toggle_user_ppmp_update' && isset($_POST['ppmp_id'])) {
    header('Content-Type: application/json');
    if (($_SESSION['role'] ?? null) !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    ensureToolSubUserUpdateColumn($con);

    $ppmp_id = intval($_POST['ppmp_id']);
    $allow = intval($_POST['allow'] ?? 0) === 1 ? 1 : 0;
    if ($ppmp_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }

    $stmt = $con->prepare("UPDATE tool_sub SET allow_user_update = ? WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare request.']);
        exit;
    }
    $stmt->bind_param("ii", $allow, $ppmp_id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => $ok,
        'message' => $ok
            ? ($allow ? 'User update enabled.' : 'User update disabled.')
            : 'Failed to update permission.'
    ]);
    exit;
}

$_SESSION['swal'] = ['title' => 'Invalid request', 'text' => 'Missing form data.', 'icon' => 'error'];
header("Location: $redirect");
exit;
