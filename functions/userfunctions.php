
<?php 
include 'config/dbcon.php';

function getAllUsers()
{
    global $con;

    $query = "SELECT * FROM accounts ORDER BY created_at DESC";
    return mysqli_query($con, $query);
}

function PPMP()
{
    global $con;

    $query = "SELECT * FROM ppmp_submissions ORDER BY created_at DESC";
    return mysqli_query($con, $query);
}

function getPPMP($con, $options = [])
{
    // Defaults
    $searchOffice = $options['office'] ?? '';
    $searchTerm   = $options['search'] ?? '';
    $limit        = $options['limit'] ?? 10;
    $page         = max((int)($options['page'] ?? 1), 1);
    $offset       = ($page - 1) * $limit;

    // Base query
    $baseQuery = "FROM ppmp_submissions WHERE 1";

    // Filters
    if (!empty($searchOffice)) {
        $office = mysqli_real_escape_string($con, $searchOffice);
        $baseQuery .= " AND office = '$office'";
    }

    if (!empty($searchTerm)) {
        $term = mysqli_real_escape_string($con, $searchTerm);
        $baseQuery .= " AND (email LIKE '%$term%' OR ppmp_type LIKE '%$term%')";
    }

    // Count total rows
    $countQuery = "SELECT COUNT(*) AS total $baseQuery";
    $countResult = mysqli_query($con, $countQuery);
    $totalRows = mysqli_fetch_assoc($countResult)['total'];
    $totalPages = ceil($totalRows / $limit);

    // Final query
    $dataQuery = "SELECT * $baseQuery ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $dataResult = mysqli_query($con, $dataQuery);

    return [
        'result'      => $dataResult,
        'total_rows'  => $totalRows,
        'total_pages' => $totalPages,
        'page'        => $page,
        'limit'       => $limit
    ];
}
?>
