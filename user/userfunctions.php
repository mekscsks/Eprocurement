<?php 
include '../config/localdb.php';
function getAccountInfo($con, $account_id)
{
    $query = "SELECT name, username FROM accounts WHERE account_id=?";
    $stmt = mysqli_prepare($con, $query);

    if (!$stmt) {
        // SQL error: return defaults
        return ['name' => '', 'username' => ''];
    }

    mysqli_stmt_bind_param($stmt, "i", $account_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $account = mysqli_fetch_assoc($result);

    // Ensure always returns an array with string values
    return [
        'name' => $account['name'] ?? '',
        'username' => $account['username'] ?? ''
    ];
}


function getUserDocuments(mysqli $con, int $limit = 5, string $search = '', int $page = 1): array
{
    if (!isset($_SESSION['auth_user']['account_id'])) {
        die("User not logged in."); // or redirect to login page
    }
    $account_id = (int)$_SESSION['auth_user']['account_id'];

    $page = max(1, $page);
    $offset = ($page - 1) * $limit;

    // Count total matching records
    $countSql = "SELECT COUNT(*) AS total FROM documents WHERE user_id = ?";
    $countParams = [$account_id];
    $countTypes = "i";
    if ($search !== '') {
        $countSql .= " AND tracking_number LIKE ?";
        $countParams[] = "%$search%";
        $countTypes .= "s";
    }
    $stmtCount = $con->prepare($countSql);
    $stmtCount->bind_param($countTypes, ...$countParams);
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $totalPages = max(1, (int)ceil($totalRecords / $limit));

    // Fetch paginated records
    $listSql = "SELECT * FROM documents WHERE user_id = ?";
    $listParams = [$account_id];
    $listTypes = "i";
    if ($search !== '') {
        $listSql .= " AND tracking_number LIKE ?";
        $listParams[] = "%$search%";
        $listTypes .= "s";
    }
    $listSql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $listParams[] = $limit;
    $listParams[] = $offset;
    $listTypes .= "ii";
    $stmtList = $con->prepare($listSql);
    $stmtList->bind_param($listTypes, ...$listParams);
    $stmtList->execute();
    $result = $stmtList->get_result();

    return [
        'result'       => $result,
        'totalRecords' => $totalRecords,
        'totalPages'   => $totalPages,
        'offset'       => $offset
    ];
}
?>