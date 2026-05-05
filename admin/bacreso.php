<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'functions/userfunctions.php';
include 'functions/authorization.php';
include 'includes/header.php';
include 'functions/bacfunction.php';
include 'includes/sidebar.php';

$purchaseRequests = getPurchaseRequestsForBac($con);

$total    = count($purchaseRequests);
$pending  = count(array_filter($purchaseRequests, fn($r) => $r['status'] === 'Pending'));
$approved = count(array_filter($purchaseRequests, fn($r) => $r['status'] === 'Approved'));
$rejected = count(array_filter($purchaseRequests, fn($r) => $r['status'] === 'Rejected'));
$totalAmt = array_sum(array_column($purchaseRequests, 'total_amount'));
?>

<link href="assets/css/procurement.css" rel="stylesheet">

<div class="proc-page">

  <!-- PAGE HEADER -->
  <div class="proc-header">
    <div class="proc-header-left">
      <h1><i class="bi bi-clipboard2-check" style="color:var(--accent);margin-right:8px;"></i>BAC Resolution Management</h1>
      <p>Manage procurement records, suppliers, and bidding processes.</p>
    </div>
  </div>

  <!-- PR MONITORING TABLE -->
  <div class="main-card" style="margin-top:24px;">
    <div class="card-top">
      <div class="card-top-title"><i class="bi bi-bag-check"></i> Purchase Request Monitoring</div>
      <div class="toolbar">
        <div class="search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" id="prSearchInput" placeholder="Search PR No., office…" oninput="filterPRTable()">
        </div>
        <select class="filter-select" id="prStatusFilter" onchange="filterPRTable()">
          <option value="">All Status</option>
          <option>Pending</option>
          <option>Approved</option>
          <option>Rejected</option>
          <option>PO Generated</option>
        </select>
      </div>
    </div>
    <div class="table-wrap">
      <table id="prTable">
        <thead>
          <tr>
            <th>PR No.</th>
            <th>Requested By</th>
            <th>Office</th>
            <th>Fund Source</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="prTableBody"></tbody>
      </table>
    </div>
    <div class="table-footer">
      <div class="footer-info" id="prFooterInfo"></div>
      <div class="pagination" id="prPagination"></div>
    </div>
  </div>

</div>

<!-- PHP data passed to JS -->
<script>
const allPRData = <?= json_encode(array_map(fn($r) => [
  'id'           => (int)$r['id'],
  'pr_number'    => $r['pr_number'],
  'requested_by' => $r['requested_by'],
  'office'       => $r['office'],
  'fund_source'  => $r['fund_source'] ?? '',
  'total_amount' => floatval($r['total_amount']),
  'status'       => $r['status'],
  'created_at'   => $r['created_at'],
], $purchaseRequests), JSON_HEX_TAG) ?>;
</script>
<script src="assets/js/bacreso.js"></script>
