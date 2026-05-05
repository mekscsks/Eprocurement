<?php
session_start();
include '../config/localdb.php';
$isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);
ini_set('display_errors', $isDebug ? '1' : '0');
ini_set('display_startup_errors', $isDebug ? '1' : '0');
error_reporting($isDebug ? E_ALL : 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: submissions.php');
    exit;
}

function tableHasColumn(mysqli $con, string $table, string $column): bool
{
    $table = $con->real_escape_string($table);
    $column = $con->real_escape_string($column);
    $result = $con->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    if ($result instanceof mysqli_result) {
        $result->free();
    }

    return $exists;
}

function redirectTo(string $location): never
{
    if (!headers_sent()) {
        header("Location: {$location}");
    }

    $safeLocation = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html><head>';
    echo '<meta http-equiv="refresh" content="0;url=' . $safeLocation . '">';
    echo '<script>window.location.href=' . json_encode($location) . ';</script>';
    echo '</head><body>';
    echo 'Redirecting... <a href="' . $safeLocation . '">Continue</a>';
    echo '</body></html>';
    exit;
}

function generateSecureSuffix(int $bytes = 8): string
{
    return bin2hex(random_bytes($bytes));
}

function getUploadDirectory(): string
{
    return __DIR__ . '/../uploads/ppmp/';
}

function ensureUploadDirectory(string $uploadDir): bool
{
    if (is_dir($uploadDir)) {
        return true;
    }

    return mkdir($uploadDir, 0777, true);
}

function isValidUploadedFile(array $file, array $allowedExtensions, array $allowedMimeTypes, int $maxBytes): bool
{
    $tmpName = (string)($file['tmp_name'] ?? '');
    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $size = (int)($file['size'] ?? 0);

    if (!is_uploaded_file($tmpName) || !in_array($extension, $allowedExtensions, true) || $size <= 0 || $size > $maxBytes) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return false;
    }

    $mimeType = (string)finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    return in_array($mimeType, $allowedMimeTypes, true);
}

function buildUploadFileName(string $prefix, string $extension): string
{
    return $prefix . generateSecureSuffix(16) . '.' . $extension;
}

function resolveManagedUploadPath(string $uploadDir, string $storedPath): ?string
{
    $fileName = basename(trim($storedPath));
    if ($fileName === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $fileName)) {
        return null;
    }

    $baseDir = realpath($uploadDir);
    if ($baseDir === false) {
        return null;
    }

    $candidate = $baseDir . DIRECTORY_SEPARATOR . $fileName;
    $resolved = realpath($candidate);
    if ($resolved === false) {
        return null;
    }

    return str_starts_with($resolved, $baseDir . DIRECTORY_SEPARATOR) ? $resolved : null;
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

function ensureToolSubUserUpdateColumn(mysqli $con): bool
{
    static $checked = false;
    static $existsAfterCheck = false;
    if ($checked) {
        return $existsAfterCheck;
    }
    $checked = true;

    $exists = tableHasColumn($con, 'tool_sub', 'allow_user_update');
    if (!$exists) {
        $con->query("ALTER TABLE tool_sub ADD COLUMN allow_user_update TINYINT(1) NOT NULL DEFAULT 0");
        $exists = tableHasColumn($con, 'tool_sub', 'allow_user_update');
    }

    $existsAfterCheck = $exists;
    return $existsAfterCheck;
}




/*
|--------------------------------------------------------------------------
| GET LOGGED USER
|--------------------------------------------------------------------------
*/

$account_id = $_SESSION['auth_user']['account_id'] ?? null;

if (!$account_id) {
    $_SESSION['alert'] = [
        'type'  => 'error',
        'title' => 'Access Denied!',
        'msg'   => 'You must be logged in.'
    ];
    header("Location: ../index.php");
    return;
}

/*
|--------------------------------------------------------------------------
| PPMP SUBMISSION
|--------------------------------------------------------------------------
*/

if (isset($_POST['PPMPSUB'])) {
    ensureToolSubQuantityColumn($con);

    $required = ['ppmp_type','office'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['alert'] = ['type'=>'error','title'=>'Oops!','msg'=>'Please fill in all required fields.'];
            header("Location: ppmp.php");
            return;
        }
    }

    $ppmp_type         = mysqli_real_escape_string($con, $_POST['ppmp_type']);
    $office            = mysqli_real_escape_string($con, $_POST['office']);
    $unit              = mysqli_real_escape_string($con, $_POST['unit'] ?? $office);
    $fiscal_year       = mysqli_real_escape_string($con, $_POST['fiscal_year'] ?? '');
    $app_type          = mysqli_real_escape_string($con, $_POST['app_type'] ?? '');
    $version_number    = !empty($_POST['version_number']) ? (int)$_POST['version_number'] : null;
    $description       = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ', trim($_POST['description'] ?? '')));
    $project_type      = mysqli_real_escape_string($con, $_POST['project_type'] ?? '');
    $quantity          = trim($_POST['quantity'] ?? '') !== '' ? mysqli_real_escape_string($con, $_POST['quantity']) : null;
    $procurement_mode  = !empty($_POST['procurement_mode']) ? mysqli_real_escape_string($con, $_POST['procurement_mode']) : null;
    $preproc           = isset($_POST['preproc']) ? ($_POST['preproc'] === 'Yes' ? 1 : 0) : null;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date   = !empty($_POST['end_date'])   ? $_POST['end_date']   : null;

    if ($start_date !== null && $end_date !== null) {
        $sd = DateTime::createFromFormat('Y-m-d', $start_date);
        $ed = DateTime::createFromFormat('Y-m-d', $end_date);
        if (!$sd || !$ed) {
            $_SESSION['alert'] = ['type'=>'error','title'=>'Invalid Date!','msg'=>'Please enter valid dates.'];
            header("Location: ppmp.php");
            return;
        }
        if ($ed <= $sd) {
            $_SESSION['alert'] = ['type'=>'error','title'=>'Invalid Date Range!','msg'=>'End date must be after start date.'];
            header("Location: ppmp.php");
            return;
        }
        if ($sd < new DateTime('today')) {
            $_SESSION['alert'] = ['type'=>'error','title'=>'Invalid Start Date!','msg'=>'Start date cannot be in the past.'];
            header("Location: ppmp.php");
            return;
        }
    }
    $delivery_period   = mysqli_real_escape_string($con, $_POST['delivery_period'] ?? '');
    $source_funds      = mysqli_real_escape_string($con, $_POST['source_funds'] ?? '');
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
    if ($budget !== null && $budget < 0) {
        $_SESSION['alert'] = ['type'=>'error','title'=>'Invalid Budget!','msg'=>'Budget cannot be negative.'];
        header("Location: ppmp.php");
        return;
    }
    $remarks           = mysqli_real_escape_string($con, $_POST['remarks'] ?? '');

    $tracking_number = 'PPMP-' . date('Ymd') . '-' . strtoupper(generateSecureSuffix(4));

    // Handle optional supporting document upload
    $supporting_doc_path = null;
    if (isset($_FILES['supporting_doc']) && $_FILES['supporting_doc']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['supporting_doc'];
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream',
        ];
        $maxBytes = 10 * 1024 * 1024;

        if (!isValidUploadedFile($file, $allowedExtensions, $allowedMimeTypes, $maxBytes)) {
            $_SESSION['alert'] = ['type'=>'error','title'=>'Invalid File','msg'=>'Only PDF, DOC, DOCX, XLS, XLSX files are allowed.'];
            header('Location: ppmp.php');
            return;
        }
        $uploadDir = getUploadDirectory();
        if (!ensureUploadDirectory($uploadDir)) {
            $_SESSION['alert'] = ['type'=>'error','title'=>'Upload Failed','msg'=>'Upload directory is not available.'];
            header('Location: ppmp.php');
            return;
        }
        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $newName = buildUploadFileName('doc_', $ext);
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
            $supporting_doc_path = '/uploads/ppmp/' . $newName;
        } else {
            $_SESSION['alert'] = ['type'=>'error','title'=>'Upload Failed','msg'=>'Failed to upload supporting document.'];
            header('Location: ppmp.php');
            return;
        }
    }

    // Build INSERT dynamically to support optional supporting_doc column
    $hasDocCol = tableHasColumn($con, 'tool_sub', 'supporting_doc');
    $docColSql = $hasDocCol ? ', supporting_doc' : '';
    $docValSql = $hasDocCol ? ', ?' : '';

    $stmtTool = $con->prepare("
        INSERT INTO tool_sub
            (account_id, ppmp_type, office, unit, fiscal_year, app_type, version_number,
             description, project_type, quantity, procurement_mode, preproc,
             start_date, end_date, delivery_period, source_funds, budget, remarks$docColSql)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?$docValSql)
    ");
    if (!$stmtTool) {
        $_SESSION['alert'] = ['type'=>'error','title'=>'Database Error','msg'=>'Failed to prepare submission: ' . $con->error];
        header('Location: ppmp.php');
        return;
    }
    $bindTypes  = "isssssissssissssds";
    $bindParams = [
        $account_id, $ppmp_type, $office, $unit, $fiscal_year, $app_type,
        $version_number, $description, $project_type, $quantity,
        $procurement_mode, $preproc, $start_date, $end_date,
        $delivery_period, $source_funds, $budget, $remarks
    ];
    if ($hasDocCol) {
        $bindTypes   .= 's';
        $bindParams[] = $supporting_doc_path;
    }
    // bind_param needs refs
    $refs = [&$bindTypes];
    foreach ($bindParams as $k => $_) { $refs[] = &$bindParams[$k]; }
    call_user_func_array([$stmtTool, 'bind_param'], $refs);
    if (!$stmtTool->execute()) {
        $_SESSION['alert'] = ['type'=>'error','title'=>'Submission Failed','msg'=>'Failed to save PPMP: ' . $stmtTool->error];
        $stmtTool->close();
        header('Location: ppmp.php');
        return;
    }
    $stmtTool->close();

    $title  = "PPMP Submission - $ppmp_type";
    $status = "Pending";
    $desc   = "PPMP submitted by account ID: $account_id";

    $stmtDoc = $con->prepare("INSERT INTO documents (tracking_number,user_id,title,description,current_status,created_at) VALUES (?,?,?,?,?,NOW())");
    $stmtDoc->bind_param("sisss", $tracking_number, $account_id, $title, $desc, $status);
    $stmtDoc->execute();
    $document_id = $stmtDoc->insert_id;
    $stmtDoc->close();

    $stmtLog = $con->prepare("INSERT INTO document_logs (document_id,status,remarks,updated_at) VALUES (?,?,?,NOW())");
    $log_remarks = "Submitted by account ID: $account_id";
    $stmtLog->bind_param("iss", $document_id, $status, $log_remarks);
    $stmtLog->execute();
    $stmtLog->close();

    $_SESSION['alert'] = ['type'=>'success','title'=>'Success','msg'=>"PPMP submitted! Tracking: $tracking_number"];
    header("Location: ppmp.php");
    return;
}

/*
|--------------------------------------------------------------------------
| PURCHASE REQUEST SUBMISSION
|--------------------------------------------------------------------------
*/

if (isset($_POST['PRSUB'])) {
    require_once __DIR__ . '/../admin/functions/purchasefunctions.php';

    // Backend guard: block submission if user has no approved PPMP
    $stmtPpmpChk = $con->prepare(
        "SELECT id FROM tool_sub WHERE account_id = ? AND status = 'Approved' LIMIT 1"
    );
    $stmtPpmpChk->bind_param('i', $account_id);
    $stmtPpmpChk->execute();
    $stmtPpmpChk->store_result();
    if ($stmtPpmpChk->num_rows === 0) {
        $stmtPpmpChk->close();
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'PPMP Required',
            'msg'   => 'You must have an approved PPMP before submitting a Purchase Request.'
        ];
        header('Location: purchase_request.php');
        return;
    }
    $stmtPpmpChk->close();

    $section                    = trim($_POST['section'] ?? '');
    $responsibility_center_code = trim($_POST['responsibility_center_code'] ?? '');
    $approved_by                = trim($_POST['approved_by'] ?? '');

    // Server-side: expected_delivery_date must not be in the past
    $edd = trim($_POST['expected_delivery_date'] ?? '');
    if ($edd !== '') {
        $eddDate = DateTime::createFromFormat('Y-m-d', $edd);
        $today   = new DateTime('today');
        if (!$eddDate || $eddDate < $today) {
            $_SESSION['alert'] = ['type'=>'error','title'=>'Invalid Date','msg'=>'Expected delivery date cannot be in the past.'];
            header("Location: purchase_request.php");
            return;
        }
    }

    $created = createPR($con, [
        'pr_number'                => trim($_POST['pr_no'] ?? ''),
        'requested_by'             => $_SESSION['auth_user']['name'] ?? trim($_POST['requested_by'] ?? ''),
        'designation'              => trim($_POST['designation'] ?? ''),
        'office'                   => trim($_POST['office'] ?? ''),
        'section'                  => $section,
        'responsibility_center_code' => $responsibility_center_code,
        'approved_by'              => $approved_by,
        'end_use'                  => trim($_POST['purpose'] ?? ''),
        'account_id'               => $account_id,
        'quantity'                 => $_POST['quantity'] ?? [],
        'unit'                     => $_POST['unit'] ?? [],
        'description'              => $_POST['item_description'] ?? [],
        'unit_cost'                => $_POST['unit_cost'] ?? [],
        'ppmp_attachments_required' => json_encode(array_values($_POST['ppmp_attachments_required'] ?? [])),
        'expected_delivery_date'   => trim($_POST['expected_delivery_date'] ?? ''),
        'procurement_mode'         => trim($_POST['procurement_mode'] ?? ''),
        'is_pr_locked'             => isset($_POST['is_pr_locked']) ? 1 : 0,
    ]);

    if ($created) {
        $stmt = $con->prepare(
            "UPDATE purchase_requests
             SET section = ?, responsibility_center_code = ?, approved_by = ?
             WHERE account_id = ? AND deleted = 0
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('sssi', $section, $responsibility_center_code, $approved_by, $account_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: purchase_request.php");
    return;
}

if ((($_POST['action'] ?? '') === 'reupload_ppmp' || isset($_POST['reupload_ppmp'])) && isset($_POST['id'])) {
    $hasAllowUserUpdateCol = ensureToolSubUserUpdateColumn($con);

    $id = (int)($_POST['id'] ?? 0);
    $redirect = trim((string)($_POST['redirect'] ?? 'submissions.php'));
    $redirectParts = parse_url($redirect);
    if (is_array($redirectParts) && (isset($redirectParts['scheme']) || isset($redirectParts['host']))) {
        $redirect = 'submissions.php';
    }
    if ($redirect === '') {
        $redirect = 'submissions.php';
    }

    if ($id <= 0) {
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Invalid request',
            'msg'   => 'Invalid submission.'
        ];
        header("Location: $redirect");
        return;
    }

    $selectFields = "account_id, file_path, status";
    if ($hasAllowUserUpdateCol) {
        $selectFields .= ", allow_user_update";
    }

    $stmt = $con->prepare("SELECT {$selectFields} FROM tool_sub WHERE id = ? LIMIT 1");
    if (!$stmt) {
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Database error',
            'msg'   => 'Failed to load the PPMP record.'
        ];
        header("Location: $redirect");
        return;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row || (int)$row['account_id'] !== (int)$account_id) {
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Access denied',
            'msg'   => 'Hindi mo puwedeng i-reupload ang submission na ito.'
        ];
        header("Location: $redirect");
        return;
    }

    $status = strtolower(trim((string)($row['status'] ?? '')));
    $allowUserUpdate = $hasAllowUserUpdateCol && (int)($row['allow_user_update'] ?? 0) === 1;
    if ($status === 'approved' && !$allowUserUpdate) {
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Not allowed',
            'msg'   => 'Approved na ito, hindi na puwedeng i-reupload.'
        ];
        header("Location: $redirect");
        return;
    }

    if (!isset($_FILES['ppmp_file']) || !is_array($_FILES['ppmp_file']) || ($_FILES['ppmp_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Upload failed',
            'msg'   => 'Please attach a valid Excel file.'
        ];
        header("Location: $redirect");
        return;
    }

    $file = $_FILES['ppmp_file'];
    $allowedExtensions = ['xlsx', 'xls'];
    $allowedMimeTypes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream',
    ];
    $maxBytes = 10 * 1024 * 1024;
    if (!isValidUploadedFile($file, $allowedExtensions, $allowedMimeTypes, $maxBytes)) {
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Invalid File',
            'msg'   => 'Only Excel files allowed.'
        ];
        header("Location: $redirect");
        return;
    }

    $uploadDir = getUploadDirectory();
    if (!ensureUploadDirectory($uploadDir)) {
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Upload failed',
            'msg'   => 'Upload directory is not available.'
        ];
        header("Location: $redirect");
        return;
    }

    $fileExt = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $newFileName = buildUploadFileName('ppmp_', $fileExt);
    $uploadPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Upload Failed',
            'msg'   => 'File upload failed.'
        ];
        header("Location: $redirect");
        return;
    }

    $dbFilePath = "/uploads/ppmp/" . $newFileName;
    $updateSql = "UPDATE tool_sub SET file_path = ?, status = 'Pending'";
    if ($hasAllowUserUpdateCol) {
        $updateSql .= ", allow_user_update = 0";
    }
    $updateSql .= " WHERE id = ? AND account_id = ?";

    $stmtUp = $con->prepare($updateSql);
    if (!$stmtUp) {
        @unlink($uploadPath);
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Database error',
            'msg'   => 'Failed to update the PPMP record.'
        ];
        header("Location: $redirect");
        return;
    }
    $stmtUp->bind_param("sii", $dbFilePath, $id, $account_id);
    $ok = $stmtUp->execute();
    $stmtUp->close();

    if (!$ok) {
        @unlink($uploadPath);
        $_SESSION['alert'] = [
            'type'  => 'error',
            'title' => 'Reupload failed',
            'msg'   => 'Failed to update record.'
        ];
        header("Location: $redirect");
        return;
    }

    $oldFilePath = (string)($row['file_path'] ?? '');
    $oldCandidate = resolveManagedUploadPath($uploadDir, $oldFilePath);
    if ($oldCandidate !== null && basename($oldCandidate) !== $newFileName && is_file($oldCandidate)) {
        @unlink($oldCandidate);
    }

    $_SESSION['alert'] = [
        'type'  => 'success',
        'title' => 'Reuploaded',
        'msg'   => 'Na-update ang PPMP file. For review ulit ito.'
    ];
    header("Location: $redirect");
    return;
}

if ((($_POST['action'] ?? '') === 'update_ppmp' || isset($_POST['update_ppmp'])) && isset($_POST['id'])) {
    ensureToolSubQuantityColumn($con);
    $hasAllowUserUpdateCol = ensureToolSubUserUpdateColumn($con);

    $id = (int)($_POST['id'] ?? 0);
    $redirect = trim((string)($_POST['redirect'] ?? 'submissions.php'));
    $redirectParts = parse_url($redirect);
    if (is_array($redirectParts) && (isset($redirectParts['scheme']) || isset($redirectParts['host']))) {
        $redirect = 'submissions.php';
    }
    if ($redirect === '') {
        $redirect = 'submissions.php';
    }

    $hasDocCol = tableHasColumn($con, 'tool_sub', 'supporting_doc');
    $selectFields = "account_id, status";
    if ($hasAllowUserUpdateCol) {
        $selectFields .= ", allow_user_update";
    }
    if ($hasDocCol) {
        $selectFields .= ", supporting_doc";
    }

    $stmt = $con->prepare("SELECT {$selectFields} FROM tool_sub WHERE id = ? LIMIT 1");
    if (!$stmt) {
        $_SESSION['alert'] = ['type' => 'error', 'title' => 'Database error', 'msg' => 'Failed to load the PPMP record.'];
        redirectTo($redirect);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row || (int)$row['account_id'] !== (int)$account_id) {
        $_SESSION['alert'] = ['type' => 'error', 'title' => 'Access denied', 'msg' => 'Hindi mo puwedeng i-update ang PPMP na ito.'];
        redirectTo($redirect);
    }

    $status = strtolower(trim((string)($row['status'] ?? '')));
    $allowUserUpdate = $hasAllowUserUpdateCol && (int)($row['allow_user_update'] ?? 0) === 1;
    if ($status === 'approved' && !$allowUserUpdate) {
        $_SESSION['alert'] = ['type' => 'error', 'title' => 'Not allowed', 'msg' => 'Hindi puwedeng i-update ang approved PPMP na ito.'];
        redirectTo($redirect);
    }

    $ppmp_type = trim($_POST['ppmp_type'] ?? '');
    $office = trim($_POST['office'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $description = preg_replace('/\s+/', ' ', trim($_POST['description'] ?? ''));
    $project_type = trim($_POST['project_type'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $procurement_mode = trim($_POST['procurement_mode'] ?? '');
    $preproc = isset($_POST['preproc']) ? ($_POST['preproc'] === 'Yes' ? 1 : 0) : null;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $delivery_period = trim($_POST['delivery_period'] ?? '');
    $source_funds = trim($_POST['source_funds'] ?? '');
    $budget = trim((string)($_POST['budget'] ?? '')) !== '' ? (float) $_POST['budget'] : null;
    $remarks = trim($_POST['remarks'] ?? '');

    if ($ppmp_type === '' || $office === '' || $unit === '' || $description === '') {
        $_SESSION['alert'] = ['type' => 'error', 'title' => 'Validation error', 'msg' => 'Please fill in all required fields.'];
        redirectTo($redirect);
    }

    if ($start_date !== null && $end_date !== null) {
        $sd = DateTime::createFromFormat('Y-m-d', $start_date);
        $ed = DateTime::createFromFormat('Y-m-d', $end_date);
        if (!$sd || !$ed || $ed <= $sd) {
            $_SESSION['alert'] = ['type' => 'error', 'title' => 'Invalid Date Range!', 'msg' => 'End date must be after start date.'];
            redirectTo($redirect);
        }
    }

    if ($budget !== null && $budget < 0) {
        $_SESSION['alert'] = ['type' => 'error', 'title' => 'Invalid Budget!', 'msg' => 'Budget cannot be negative.'];
        redirectTo($redirect);
    }

    $supportingDocPath = $row['supporting_doc'] ?? null;
    $newDocUploaded = false;
    if ($hasDocCol && isset($_FILES['supporting_doc']) && ($_FILES['supporting_doc']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $file = $_FILES['supporting_doc'];
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream',
        ];
        $maxBytes = 10 * 1024 * 1024;
        if (!isValidUploadedFile($file, $allowedExtensions, $allowedMimeTypes, $maxBytes)) {
            $_SESSION['alert'] = ['type' => 'error', 'title' => 'Invalid File', 'msg' => 'Only PDF, DOC, DOCX, XLS, XLSX files are allowed.'];
            redirectTo($redirect);
        }

        $uploadDir = getUploadDirectory();
        if (!ensureUploadDirectory($uploadDir)) {
            $_SESSION['alert'] = ['type' => 'error', 'title' => 'Upload Failed', 'msg' => 'Upload directory is not available.'];
            redirectTo($redirect);
        }
        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $newName = buildUploadFileName('doc_', $ext);
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
            $_SESSION['alert'] = ['type' => 'error', 'title' => 'Upload Failed', 'msg' => 'Failed to upload supporting document.'];
            redirectTo($redirect);
        }
        $supportingDocPath = '/uploads/ppmp/' . $newName;
        $newDocUploaded = true;
    }

    $sql = "UPDATE tool_sub SET ppmp_type=?, office=?, unit=?, description=?, project_type=?, quantity=?, procurement_mode=?, preproc=?, start_date=?, end_date=?, delivery_period=?, source_funds=?, budget=?, remarks=?, status='Pending'";
    if ($hasAllowUserUpdateCol) {
        $sql .= ", allow_user_update=0";
    }
    if ($hasDocCol) {
        $sql .= ", supporting_doc=?";
    }
    $sql .= " WHERE id=? AND account_id=?";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        $_SESSION['alert'] = ['type' => 'error', 'title' => 'Database error', 'msg' => 'Failed to update PPMP.'];
        redirectTo($redirect);
    }

    if ($hasDocCol) {
        $stmt->bind_param(
            "sssssssissssdssii",
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
            $supportingDocPath,
            $id,
            $account_id
        );
    } else {
        $stmt->bind_param(
            "sssssssissssdsii",
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
            $id,
            $account_id
        );
    }
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok && $hasDocCol && $newDocUploaded) {
        $uploadDir = getUploadDirectory();
        $oldPath = resolveManagedUploadPath($uploadDir, (string)($row['supporting_doc'] ?? ''));
        $newBase = basename((string)$supportingDocPath);
        if ($oldPath !== null && basename($oldPath) !== $newBase && is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $_SESSION['alert'] = $ok
        ? ['type' => 'success', 'title' => 'Updated', 'msg' => 'Your PPMP was updated and sent back for review.']
        : ['type' => 'error', 'title' => 'Failed', 'msg' => 'Failed to update your PPMP.'];
    redirectTo($redirect);
}

/*
|--------------------------------------------------------------------------
| USER CHANGE PASSWORD
|--------------------------------------------------------------------------
*/

if(isset($_POST['change_password'])){

    $new_password=$_POST['new_password'];
    $confirm_password=$_POST['confirm_password'];

    if($new_password!==$confirm_password){

        $_SESSION['alert']=[
            'type'=>'error',
            'msg'=>'Passwords do not match.'
        ];

        header("Location: my-profile.php");
        return;
    }

    if(strlen($new_password)<8){

        $_SESSION['alert']=[
            'type'=>'error',
            'msg'=>'Password must be at least 8 characters.'
        ];

        header("Location: my-profile.php");
        return;
    }

    $hashed=password_hash($new_password,PASSWORD_DEFAULT);
    $changedBy = 'user';

    $query="UPDATE accounts 
            SET password=?,
            last_password_change=NOW(),
            last_password_changed_by=?
            WHERE account_id=?";

    $stmt=$con->prepare($query);
    $stmt->bind_param("ssi",$hashed,$changedBy,$account_id);
    $stmt->execute();

    $_SESSION['alert']=[
        'type'=>'success',
        'msg'=>'Password updated successfully.'
    ];

    header("Location: my-profile.php");
    return;
}

if (isset($_POST['update_profile'])) {

    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);

    $query = "UPDATE accounts SET name=?, username=? WHERE account_id=?";
    $stmt  = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $name, $username, $account_id);
    mysqli_stmt_execute($stmt);

    $_SESSION['status'] = "Profile updated successfully";
    header("Location: my-profile.php");
    return;
}

$_SESSION['alert'] = [
    'type' => 'error',
    'title' => 'Invalid request',
    'msg' => 'No valid action was received.'
];
redirectTo('submissions.php');


