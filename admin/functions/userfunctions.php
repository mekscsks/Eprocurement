<?php
include 'functions.php';

function getAllUsers()
{
    global $con;

    $query = "SELECT * FROM accounts ORDER BY created_at DESC";
    return mysqli_query($con, $query);
}

// Fetch all PPMPs with optional filtering or pagination
function getPPMPSubmissions($con, $options = []) {
    $search = $options['search'] ?? '';
    $office = $options['office'] ?? '';
    
    $query = "SELECT * FROM tool_sub WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND file_name LIKE ?";
        $params[] = "%$search%";
    }

    if ($office) {
        $query .= " AND office = ?";
        $params[] = $office;
    }

    $query .= " ORDER BY created_at DESC";

    return query($con, $query, $params);
}

function getallppmpsubmissions($con) {
    $query = "SELECT * FROM tool_sub ORDER BY created_at DESC";
    return mysqli_query($con, $query);
}



function getPPMPSubmissionByOffice($con, $office) {
    $query = "SELECT * FROM tool_sub  WHERE office = ?";
    return query($con, $query, [$office]);
}


// Get count of pending procurements older than X days
function getOldPendingCount($con, $days = 7)
{
    // Ensure days is an integer (security)
    $days = (int)$days;

    $query = "
        SELECT COUNT(*) AS old_pending 
        FROM procurements 
        WHERE status='Open' 
        AND created_at <= DATE_SUB(NOW(), INTERVAL $days DAY)
    ";

    $result = mysqli_query($con, $query);

    if ($result) {
        $data = mysqli_fetch_assoc($result);
        return $data['old_pending'] ?? 0;
    }

    return 0;
}

function getProcurementCount($con, $options = [])
{
    $conditions = [];
    $allowedStatuses = ['Open', 'Closed', 'Rejected'];

    // Filter by status
    if (isset($options['status'])) {

        $status = $options['status'];

        // Validate against allowed statuses
        if (in_array($status, $allowedStatuses)) {
            $status = mysqli_real_escape_string($con, $status);
            $conditions[] = "status = '$status'";
        }
    }

    // Filter by older than X days
    if (isset($options['olderThanDays'])) {
        $days = (int)$options['olderThanDays'];
        $conditions[] = "created_at <= DATE_SUB(NOW(), INTERVAL $days DAY)";
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = "WHERE " . implode(" AND ", $conditions);
    }

    $query = "SELECT COUNT(*) AS total FROM procurements $whereClause";
    $result = mysqli_query($con, $query);

    if ($result) {
        $data = mysqli_fetch_assoc($result);
        return (int) $data['total'];
    }

    return 0;
}


?>
