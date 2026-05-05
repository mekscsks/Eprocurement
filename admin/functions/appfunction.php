<?php
if(session_status() == PHP_SESSION_NONE) session_start();

// Get account_id safely
$account_id = $_SESSION['auth_user']['account_id'] ?? null;
if(!$account_id){
    echo "<script>alert('You must be logged in to upload an APP.'); window.location='../../index.php';</script>";
    exit;
}
include_once __DIR__ . '/../../config/dbcon.php';
/**
 * Upload an APP file and insert record into the database
 * @param mysqli $con
 * @param array $data (email, ppmp_type, office, unit, notes)
 * @param array $file ($_FILES['app_file'])
 * @return array ['success' => bool, 'message' => string]
 */
function uploadApp($con, $data, $file) {
    // 1?? Get the logged-in user's account_id from session
    $account_id = $_SESSION['auth_user']['account_id'] ?? null;
    if(!$account_id) {
        return ['success'=>false, 'errors'=>['No logged-in user found. Please login first.']];
    }

    $errors = [];

    // 2?? Validate required fields
    if(empty($data['email'])) $errors[] = 'Email is required';
    if(empty($data['ppmp_type'])) $errors[] = 'PPMP Type is required';
    if(empty($data['office'])) $errors[] = 'Office is required';
    if(empty($file['name'])) $errors[] = 'File is required';

    // 3?? Validate file extension
    $allowedExtensions = ['xlsx','xls','pdf','docx'];
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if(!in_array($fileExt, $allowedExtensions)) $errors[] = 'Invalid file type (allowed: xlsx, xls, pdf, docx)';

    // 4?? Validate file size (max 10MB)
    if($file['size'] > 10*1024*1024) $errors[] = 'File size must be less than 10MB';

    if(count($errors) > 0){
        return ['success'=>false, 'errors'=>$errors];
    }

    // 5?? Handle file upload
    $uploadDir = dirname(__DIR__, 2) . '/uploads/app/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $uniqueName = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $fileName);
    $targetPath = $uploadDir . $uniqueName;

    if(!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success'=>false, 'errors'=>['Failed to move uploaded file']];
    }

    // 6?? Insert record into tool_sub with account_id
    $stmt = $con->prepare("INSERT INTO tool_sub (account_id, email, ppmp_type, office, unit, file_path, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "issssss",
        $account_id,          // <-- foreign key column
        $data['email'],
        $data['ppmp_type'],
        $data['office'],
        $data['unit'],
        $uniqueName,
        $data['notes']
    );

    if($stmt->execute()) {
        return ['success'=>true, 'message'=>'APP uploaded successfully'];
    } else {
        return ['success'=>false, 'errors'=>['Database insert failed: '.$stmt->error]];
    }
}

/**
 * Soft delete an APP submission by setting status to 'inactive'
 * @param mysqli $con
 * @param int $id
 * @return array ['success'=>bool,'message'=>string]
 */
function softDeleteApp($con, $id) {
    $stmt = $con->prepare("UPDATE tool_sub SET status='inactive' WHERE id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) {
        return ['success'=>true, 'message'=>'APP deleted successfully'];
    } else {
        return ['success'=>false, 'message'=>'Failed to delete APP'];
    }
}

/**
 * Get all active APP submissions
 * @param mysqli $con
 * @return mysqli_result
 */
function getActiveApps($con) {
    return $con->query("SELECT * FROM tool_sub WHERE status='approved' ORDER BY created_at DESC");
}

// ----------- Handle Upload -----------
if(isset($_POST['upload_app'])) {
    $result = uploadApp($con, $_POST, $_FILES['app_file']);

    if($result['success']){
        echo "<script>alert('{$result['message']}'); window.location='../planning.php';</script>";
    } else {
        // Combine all errors into a single alert
        $allErrors = implode("\n", $result['errors']);
        echo "<script>alert('The following errors occurred:\n{$allErrors}'); window.history.back();</script>";
    }
    exit;
}


// ----------- Handle Soft Delete (AJAX) -----------
if(isset($_POST['delete_app_id'])) {
    $id = intval($_POST['delete_app_id']);
    $res = softDeleteApp($con, $id);
    echo json_encode($res);
    exit;
}

// Approve PPMP
if(isset($_POST['approve_ppmp'])){
    $id = mysqli_real_escape_string($con, $_POST['id']);
    $po = mysqli_real_escape_string($con, $_POST['po']);
    $query = "UPDATE tool_sub SET status='approved', po='$po' WHERE id='$id'";
    if(mysqli_query($con, $query)){
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>mysqli_error($con)]);
    }
    exit;
}

// Reject PPMP
if(isset($_POST['reject_ppmp'])){
    $id = mysqli_real_escape_string($con, $_POST['id']);
    $reason = mysqli_real_escape_string($con, $_POST['reason']);
    $query = "UPDATE tool_sub SET status='rejected', rejection_reason='$reason' WHERE id='$id'";
    if(mysqli_query($con, $query)){
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>mysqli_error($con)]);
    }
    exit;
}

