<?php
session_start();
include('../config/localdb.php');

if (!isset($_SESSION['auth_user']['account_id']) || ($_SESSION['auth_user']['role'] ?? '') !== 'superadmin') {
    header("Location: ../index.php");
    exit();
}

$getCount = function($sql) use ($con) {
    $res = mysqli_query($con, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : ['total' => 0];
    return (int)($row['total'] ?? 0);
};

$totalUsers   = $getCount("SELECT COUNT(*) AS total FROM accounts WHERE role='user'");
$totalAdmins  = $getCount("SELECT COUNT(*) AS total FROM accounts WHERE role='admin'");
$activeCount  = $getCount("SELECT COUNT(*) AS total FROM accounts WHERE status='active'");
$inactiveCount= $getCount("SELECT COUNT(*) AS total FROM accounts WHERE status='inactive'");

$recentAdmins = mysqli_query($con, "SELECT name, email, status, created_at FROM accounts WHERE role='admin' ORDER BY created_at DESC LIMIT 5");
?>

<?php include('includes/header.php'); ?>
<?php include('includes/sidebar.php'); ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .sa-container { margin-left: 260px; padding: 24px; }
    .sa-banner { background: linear-gradient(135deg,#0D2B55 0%, #1A4080 100%); border-radius: 14px; color: #fff; padding: 22px 22px; margin-bottom: 18px; display:flex; align-items:center; justify-content:space-between; }
    .sa-banner h4 { margin: 0; font-weight: 700; }
    .stat-card { border: 0; border-radius: 12px; }
    .stat-card .icon { font-size: 24px; width: 44px; height: 44px; display:flex; align-items:center; justify-content:center; border-radius: 10px; }
    .stat-card .value { font-size: 28px; font-weight: 700; }
</style>

<div class="sa-container">
    <div class="sa-banner">
        <div>
            <h4 class="mb-1">Superadmin Dashboard</h4>
            <div class="opacity-75">System overview and quick actions</div>
        </div>
        <div class="d-flex gap-2">
            <a href="admins.php" class="btn btn-light btn-sm"><i class="bi bi-people me-1"></i> Manage Admins</a>
            <a href="user.php" class="btn btn-outline-light btn-sm"><i class="bi bi-person-gear me-1"></i> Manage Users</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-3 col-6">
            <div class="card stat-card shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon bg-primary text-white"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="text-muted small">Total Users</div>
                        <div class="value"><?= $totalUsers ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon bg-warning text-dark"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <div class="text-muted small">Total Admins</div>
                        <div class="value"><?= $totalAdmins ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon bg-success text-white"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="text-muted small">Active Accounts</div>
                        <div class="value"><?= $activeCount ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon bg-secondary text-white"><i class="bi bi-pause-circle"></i></div>
                    <div>
                        <div class="text-muted small">Inactive Accounts</div>
                        <div class="value"><?= $inactiveCount ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>Recent Admins</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentAdmins && mysqli_num_rows($recentAdmins) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($recentAdmins)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['name']); ?></td>
                                            <td><?= htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <span class="badge <?= $row['status']==='active'?'bg-success':'bg-secondary'; ?>">
                                                    <?= htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted">No recent admins</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>Quick Actions</strong>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="admins.php" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus me-1"></i> Create or Manage Admins
                        </a>
                        <a href="user.php" class="btn btn-outline-secondary">
                            <i class="bi bi-people-fill me-1"></i> View Users
                        </a>
                        <a href="../logout.php" class="btn btn-outline-danger">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
