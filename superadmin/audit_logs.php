<?php
require_once '../config/localdb.php';
require_once 'includes/auth.php';

$account_id_ses = $_SESSION['auth_user']['account_id'];

// Sanitize GET inputs
$f_date_from  = isset($_GET['date_from'])  ? trim($_GET['date_from'])  : '';
$f_date_to    = isset($_GET['date_to'])    ? trim($_GET['date_to'])    : '';
$f_account_id = isset($_GET['account_id']) ? trim($_GET['account_id']) : '';
$f_action     = isset($_GET['action'])     ? trim($_GET['action'])     : '';
$f_module     = isset($_GET['module'])     ? trim($_GET['module'])     : '';

$allowed_actions = ['LOGIN','CREATE','UPDATE','DELETE','APPROVE','REJECT','EXPORT'];
if ($f_action && !in_array($f_action, $allowed_actions)) $f_action = '';

// Build WHERE clause
$where   = [];
$types   = '';
$params  = [];

if ($f_date_from) { $where[] = 'DATE(created_at) >= ?'; $types .= 's'; $params[] = $f_date_from; }
if ($f_date_to)   { $where[] = 'DATE(created_at) <= ?'; $types .= 's'; $params[] = $f_date_to; }
if ($f_account_id){ $where[] = 'account_id = ?';        $types .= 's'; $params[] = $f_account_id; }
if ($f_action)    { $where[] = 'action = ?';            $types .= 's'; $params[] = $f_action; }
if ($f_module)    { $where[] = 'module LIKE ?';         $types .= 's'; $params[] = '%' . $f_module . '%'; }

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── CSV EXPORT ──────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql  = "SELECT id, account_id, action, module, description, ip_address, created_at FROM audit_logs $where_sql ORDER BY created_at DESC";
    $stmt = $con->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Account ID','Action','Module','Description','IP Address','Created At']);
    while ($row = $res->fetch_assoc()) fputcsv($out, $row);
    fclose($out);
    exit;
}

// ── PAGINATION ──────────────────────────────────────────────────────────────
$per_page = 15;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$count_stmt = $con->prepare("SELECT COUNT(*) FROM audit_logs $where_sql");
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_rows);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = max(1, ceil($total_rows / $per_page));

$sql  = "SELECT id, account_id, action, module, description, ip_address, created_at FROM audit_logs $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $con->prepare($sql);
$fetch_types  = $types . 'ii';
$fetch_params = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($fetch_types, ...$fetch_params);
$stmt->execute();
$logs = $stmt->get_result();

// Badge color map
$badge_map = [
    'LOGIN'  => 'primary',  'CREATE' => 'success',
    'UPDATE' => 'warning',  'DELETE' => 'danger',
    'APPROVE'=> 'info',     'REJECT' => 'secondary',
    'EXPORT' => 'dark',
];

// Build query string helper (preserves filters across pagination/export)
function qstr(array $extra = []): string {
    $base = ['date_from','date_to','account_id','action','module'];
    $p = [];
    foreach ($base as $k) if (!empty($_GET[$k])) $p[$k] = $_GET[$k];
    return http_build_query(array_merge($p, $extra));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .sidebar-wrap { width: 260px; flex-shrink: 0; }
        .main-content { margin-left: 260px; padding: 30px 24px; min-height: 100vh; }
        .page-header { background: #fff; border-radius: 10px; padding: 18px 24px; margin-bottom: 22px;
                       box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .filter-card  { background: #fff; border-radius: 10px; padding: 18px 20px; margin-bottom: 22px;
                        box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .table-card   { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.07); overflow: hidden; }
        .table thead th { background: #212529; color: #fff; font-size: .82rem; text-transform: uppercase;
                          letter-spacing: .04em; border: none; }
        .table tbody tr:hover { background: #f0f4ff; }
        .table td, .table th { vertical-align: middle; font-size: .88rem; }
        .badge { font-size: .75rem; padding: .35em .65em; }
        .desc-cell { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar-wrap position-fixed top-0 start-0 h-100">
    <?php include 'includes/sidebar.php'; ?>
</div>

<div class="main-content">

    <!-- Page Header -->
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Audit Log Monitoring</h4>
            <small class="text-muted">Read-only system activity records</small>
        </div>
        <a href="?<?= qstr(['export' => 'csv']) ?>" class="btn btn-success btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($f_date_from) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($f_date_to) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Account ID</label>
                <input type="text" name="account_id" class="form-control form-control-sm"
                       placeholder="e.g. 1001" value="<?= htmlspecialchars($f_account_id) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php foreach ($allowed_actions as $a): ?>
                        <option value="<?= $a ?>" <?= $f_action === $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Module</label>
                <input type="text" name="module" class="form-control form-control-sm"
                       placeholder="e.g. Purchase" value="<?= htmlspecialchars($f_module) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="audit_logs.php" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <small class="text-muted">
                Showing <strong><?= $total_rows > 0 ? $offset + 1 : 0 ?>–<?= min($offset + $per_page, $total_rows) ?></strong>
                of <strong><?= $total_rows ?></strong> records
            </small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Account ID</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($logs->num_rows === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No audit log records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $logs->fetch_assoc()):
                        $badge = $badge_map[$row['action']] ?? 'secondary';
                    ?>
                    <tr>
                        <td class="text-muted" style="font-size:.8rem;"><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['account_id']) ?></td>
                        <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($row['action']) ?></span></td>
                        <td><?= htmlspecialchars($row['module'] ?? '—') ?></td>
                        <td class="desc-cell" title="<?= htmlspecialchars($row['description'] ?? '') ?>">
                            <?= htmlspecialchars($row['description'] ?? '—') ?>
                        </td>
                        <td><code style="font-size:.8rem;"><?= htmlspecialchars($row['ip_address'] ?? '—') ?></code></td>
                        <td style="white-space:nowrap;"><?= htmlspecialchars($row['created_at']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-center py-3">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= qstr(['page' => $page - 1]) ?>">‹ Prev</a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($total_pages, $page + 2);
                    if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= qstr(['page' => $i]) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor;
                    if ($end < $total_pages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= qstr(['page' => $page + 1]) ?>">Next ›</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div><!-- /table-card -->

</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
