<?php
include __DIR__ . '/../../config/localdb.php';

function getProcurementCount($con, $options = [])
{
    $conditions = [];
    $allowedStatuses = ['Open', 'Closed', 'Rejected'];

    if (isset($options['status'])) {
        $status = $options['status'];

        if (in_array($status, $allowedStatuses)) {
            $status = mysqli_real_escape_string($con, $status);
            $conditions[] = "status = '$status'";
        }
    }

    if (isset($options['olderThanDays'])) {
        $days = (int)$options['olderThanDays'];
        $conditions[] = "created_at <= DATE_SUB(NOW(), INTERVAL $days DAY)";
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = "WHERE " . implode(" AND ", $conditions);
    }

    $query = "SELECT COUNT(*) AS total FROM tool_sub $whereClause";
    $result = mysqli_query($con, $query);

    if ($result) {
        $data = mysqli_fetch_assoc($result);
        return (int)$data['total'];
    }

    return 0;
}

function getRecentPendingProcurements($con, $limit = 5)
{
    $limit = (int)$limit;

    $query = "
        SELECT
            ts.id,
            ts.office,
            ts.account_id,
            ts.created_at,
            COALESCE(a.name, a.username) AS submitted_by
        FROM tool_sub ts
        LEFT JOIN accounts a ON a.account_id = ts.account_id
        WHERE ts.status = 'Open' 
        ORDER BY ts.created_at DESC 
        LIMIT $limit
    ";

    $result = mysqli_query($con, $query);

    $data = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }

    return $data;
}

function getMonthlyProcurementCounts($con, $months = 6)
{
    $months = (int)$months;
    if ($months <= 0) {
        $months = 6;
    }

    $monthsBack = $months - 1;
    if ($monthsBack < 0) {
        $monthsBack = 0;
    }

    $query = "
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
        FROM tool_sub
        WHERE is_hidden IS NULL
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL $monthsBack MONTH)
        GROUP BY ym
        ORDER BY ym ASC
    ";

    $result = mysqli_query($con, $query);
    $data = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ym = (string)($row['ym'] ?? '');
            if ($ym === '') continue;
            $data[$ym] = (int)($row['total'] ?? 0);
        }
    }

    return $data;
}

function getProcurementModeCounts($con, $limit = 8)
{
    $limit = (int)$limit;
    if ($limit <= 0) {
        $limit = 8;
    }

    $query = "
        SELECT procurement_mode, COUNT(*) AS total
        FROM tool_sub   
        WHERE is_hidden IS NULL
        GROUP BY procurement_mode
        ORDER BY total DESC
        LIMIT $limit
    ";

    $result = mysqli_query($con, $query);
    $data = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $label = trim((string)($row['procurement_mode'] ?? ''));
            if ($label === '') {
                $label = 'Unspecified';
            }
            $data[] = [
                'label' => $label,
                'total' => (int)($row['total'] ?? 0),
            ];
        }
    }

    return $data;
}

function getTotalPR($con)
{
    $result = mysqli_query($con, "SELECT COUNT(*) AS total FROM purchase_requests WHERE deleted = 0");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return (int)($row['total'] ?? 0);
    }
    return 0;
}

function getTotalPO($con)
{
    $result = mysqli_query($con, "SELECT COUNT(*) AS total FROM purchase_orders WHERE deleted = 0");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return (int)($row['total'] ?? 0);
    }
    return 0;
}

function getPendingNOACount($con)
{
    $query = "
        SELECT COUNT(*) AS total
        FROM notice_to_award nta
        LEFT JOIN procurements p ON nta.procurement_id = p.id
        WHERE (nta.status IS NULL OR nta.status = 'Pending')
    ";
    $result = mysqli_query($con, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return (int)($row['total'] ?? 0);
    }
    return 0;
}

function getPOByProcurementMode($con)
{
    $query = "
        SELECT 
            COALESCE(pr.procurement_mode, 'Unspecified') AS mode,
            COUNT(po.id) AS total
        FROM purchase_orders po
        LEFT JOIN purchase_requests pr ON po.pr_id = pr.id
        WHERE po.deleted = 0
        GROUP BY pr.procurement_mode
        ORDER BY total DESC
    ";
    $result = mysqli_query($con, $query);
    $data = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'mode' => trim($row['mode']),
                'total' => (int)$row['total']
            ];
        }
    }
    return $data;
}

function getPRStatusCounts($con)
{
    $result = mysqli_query($con, "SELECT status, COUNT(*) AS total FROM purchase_requests WHERE deleted = 0 GROUP BY status");
    $data = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0, 'PO Generated' => 0];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $s = (string)($row['status'] ?? '');
            $data[$s] = (int)($row['total'] ?? 0);
        }
    }
    return $data;
}
