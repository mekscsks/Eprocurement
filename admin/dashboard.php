<?php
if (!isset($_SESSION)) {
    session_start();
}
include '../config/localdb.php';
include 'functions/authorization.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'functions/dashboardfunctions.php';

$pendingList = getRecentPendingProcurements($con);

$totalPPMP    = getProcurementCount($con);
$approvedPPMP = getProcurementCount($con, ['status' => 'Closed']);
$pendingPPMP  = getProcurementCount($con, ['status' => 'Open']);
$rejectedPPMP = getProcurementCount($con, ['status' => 'Rejected']);
$oldPending   = getProcurementCount($con, ['status' => 'Open', 'olderThanDays' => 7]);

$totalPR    = getTotalPR($con);
$totalPO    = getTotalPO($con);
$pendingNOA = getPendingNOACount($con);
$prStatuses = getPRStatusCounts($con);

$monthlyCountsMap = getMonthlyProcurementCounts($con, 6);
$modeCounts       = getProcurementModeCounts($con, 8);
$poByMode         = getPOByProcurementMode($con);

$trendLabels = [];
$trendValues = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i month"));
    $trendLabels[] = date('M Y', strtotime($ym . '-01'));
    $trendValues[] = (int)($monthlyCountsMap[$ym] ?? 0);
}

$modeLabels = [];
$modeValues = [];
foreach ($modeCounts as $row) {
    $modeLabels[] = (string)($row['label'] ?? '');
    $modeValues[] = (int)($row['total'] ?? 0);
}

$poModeLabels = [];
$poModeValues = [];
foreach ($poByMode as $row) {
    $poModeLabels[] = $row['mode'];
    $poModeValues[] = $row['total'];
}

$approvalRate  = $totalPPMP > 0 ? round(($approvedPPMP / $totalPPMP) * 100, 1) : 0;
$rejectionRate = $totalPPMP > 0 ? round(($rejectedPPMP / $totalPPMP) * 100, 1) : 0;
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="admin-page">

  <!-- Header -->
  <div class="dash-header">
    <h2>Dashboard Overview</h2>
    <p>System summary, pending approvals, and alerts</p>
  </div>

  <!-- KPI Row -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="kpi-card kpi-blue">
        <div class="kpi-icon"><i class="bi bi-file-earmark-text"></i></div>
        <div class="kpi-label">Total PPMP</div>
        <div class="kpi-value"><?= number_format($totalPPMP) ?></div>
        <div class="kpi-sub">All time submissions</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="kpi-card kpi-green">
        <div class="kpi-icon"><i class="bi bi-cart3"></i></div>
        <div class="kpi-label">Total Purchase Requests</div>
        <div class="kpi-value"><?= number_format($totalPR) ?></div>
        <div class="kpi-sub">Purchase requests filed</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="kpi-card kpi-amber">
        <div class="kpi-icon"><i class="bi bi-receipt"></i></div>
        <div class="kpi-label">Total Purchase Orders</div>
        <div class="kpi-value"><?= number_format($totalPO) ?></div>
        <div class="kpi-sub">Purchase orders issued</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="kpi-card kpi-red">
        <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
        <div class="kpi-label">NOA Pending Conversion</div>
        <div class="kpi-value"><?= number_format($pendingNOA) ?></div>
        <div class="kpi-sub">POs awaiting NOA conversion</div>
      </div>
    </div>
  </div>

  <!-- NOA Alert Banner (shown only when there are pending NOAs) -->
  <?php if ($pendingNOA > 0): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2 mb-4 rounded-3 border-0 shadow-sm" role="alert" style="background:#fff8e1;border-left:4px solid #f59e0b!important;">
    <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
    <div>
      <strong><?= number_format($pendingNOA) ?> Purchase Order<?= $pendingNOA > 1 ? 's' : '' ?></strong> currently at NOA status and pending conversion to Purchase Order.
      <a href="nta.php" class="alert-link ms-1">View NOA records &rarr;</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Trend + Status Pie -->
  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="dash-card h-100">
        <div class="card-title">Procurement Trend</div>
        <div class="card-sub">Last 6 months submissions</div>
        <div class="chart-wrap">
          <canvas id="trendChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="dash-card h-100">
        <div class="card-title">PPMP Status Distribution</div>
        <div class="card-sub">Open vs Closed vs Rejected</div>
        <div class="chart-wrap-pie">
          <canvas id="statusPie"></canvas>
        </div>
        <div class="chart-legend">
          <span><span class="legend-dot" style="background:#3b82f6"></span>Open <?= $pendingPPMP ?></span>
          <span><span class="legend-dot" style="background:#22c55e"></span>Closed <?= $approvedPPMP ?></span>
          <span><span class="legend-dot" style="background:#ef4444"></span>Rejected <?= $rejectedPPMP ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- PO by Mode of Procurement (Bar + Pie side by side) -->
  <div class="row g-3 mb-4">
    <div class="col-lg-7">
      <div class="dash-card h-100">
        <div class="card-title">Purchase Orders by Mode of Procurement</div>
        <div class="card-sub">Volume breakdown per procurement mode</div>
        <div class="chart-wrap-bar">
          <canvas id="poModeBar"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="dash-card h-100">
        <div class="card-title">Procurement Mode Share</div>
        <div class="card-sub">Proportional distribution of POs</div>
        <div class="chart-wrap-pie">
          <canvas id="poModePie"></canvas>
        </div>
        <div class="chart-legend" id="poModeLegend"></div>
      </div>
    </div>
  </div>

  <!-- PPMP Mode Bar -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="dash-card">
        <div class="card-title">PPMP Mode Breakdown</div>
        <div class="card-sub">Top procurement modes by PPMP volume</div>
        <div class="chart-wrap-bar">
          <canvas id="modeBar"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Table -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="dash-card">
        <div class="card-title mb-3">Pending Approvals</div>
        <table class="dash-table">
          <thead>
            <tr>
              <th>PPMP No.</th>
              <th>Office</th>
              <th>Submitted By</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($pendingList)) : ?>
              <?php foreach ($pendingList as $row) :
                $daysOld = (int) floor((time() - strtotime($row['created_at'])) / 86400);
                $isOverdue = $daysOld > 7;
              ?>
                <tr>
                  <td><strong>PPMP-<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                  <td><?= htmlspecialchars($row['office'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($row['submitted_by'] ?? 'N/A') ?></td>
                  <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                  <td>
                    <?php if ($isOverdue) : ?>
                      <span class="badge-overdue">Overdue</span>
                    <?php else : ?>
                      <span class="badge-pending">Pending</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else : ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4" style="font-size:0.82rem;">
                  No pending records found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        <a href="#" class="btn-view-all">View all pending</a>
      </div>
    </div>
  </div>

  <!-- Alerts + PR Status -->
  <div class="row g-3">
    <div class="col-md-6">
      <div class="dash-card">
        <div class="card-title mb-3">Alerts</div>
        <div class="alert-row">
          <div class="alert-dot dot-red"></div>
          <div style="color:#dc2626"><?= $oldPending ?> PPMPs pending for more than 7 days</div>
        </div>
        <?php if ($pendingNOA > 0): ?>
        <div class="alert-row">
          <div class="alert-dot dot-red"></div>
          <div style="color:#dc2626"><?= $pendingNOA ?> PO<?= $pendingNOA > 1 ? 's' : '' ?> at NOA status pending conversion</div>
        </div>
        <?php endif; ?>
        <div class="alert-row">
          <div class="alert-dot dot-amber"></div>
          <div>APP generation delayed</div>
        </div>
        <div class="alert-row">
          <div class="alert-dot dot-amber"></div>
          <div>2 items nearing fiscal deadline</div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="dash-card">
        <div class="card-title">Purchase Request Status</div>
        <div class="card-sub">Breakdown by current status</div>
        <div class="chart-wrap-pie">
          <canvas id="bottleneckPie"></canvas>
        </div>
        <div class="chart-legend">
          <span><span class="legend-dot" style="background:#f59e0b"></span>Pending <?= $prStatuses['Pending'] ?></span>
          <span><span class="legend-dot" style="background:#22c55e"></span>Approved <?= $prStatuses['Approved'] ?></span>
          <span><span class="legend-dot" style="background:#ef4444"></span>Rejected <?= $prStatuses['Rejected'] ?></span>
          <span><span class="legend-dot" style="background:#3b82f6"></span>PO Generated <?= $prStatuses['PO Generated'] ?></span>
        </div>
      </div>
    </div>
  </div>

</div><!-- /.admin-page -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<div id="dashboardData" data-dashboard='<?= htmlspecialchars(json_encode([
    "trendLabels"   => $trendLabels,
    "trendValues"   => $trendValues,
    "modeLabels"    => $modeLabels,
    "modeValues"    => $modeValues,
    "poModeLabels"  => $poModeLabels,
    "poModeValues"  => $poModeValues,
    "totalPPMP"     => (int)$totalPPMP,
    "pendingPPMP"   => (int)$pendingPPMP,
    "approvedPPMP"  => (int)$approvedPPMP,
    "rejectedPPMP"  => (int)$rejectedPPMP,
    "prPending"     => (int)$prStatuses['Pending'],
    "prApproved"    => (int)$prStatuses['Approved'],
    "prRejected"    => (int)$prStatuses['Rejected'],
    "prPOGenerated" => (int)$prStatuses['PO Generated'],
]), ENT_QUOTES) ?>' style="display:none"></div>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
