<?php
if (!isset($_SESSION)) session_start();
include '../config/localdb.php';
include 'functions/authorization.php';
include 'functions/purchasefunctions.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($viewId > 0) {
    $pr = getPRWithItems($con, $viewId);
}
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="assets/css/procurement.css" rel="stylesheet">

<?php if ($viewId > 0): ?>
<?php if (!$pr): ?>
<div class="proc-page">
  <div class="proc-header">
    <div class="proc-header-left">
      <h1><i class="bi bi-exclamation-circle" style="color:var(--accent);margin-right:8px;"></i>Not Found</h1>
      <p>Purchase Request #<?= $viewId ?> does not exist or has been deleted.</p>
    </div>
    <a href="view_purchase_requests.php" class="act-btn act-view"><i class="bi bi-arrow-left"></i> Back to List</a>
  </div>
</div>
<?php else:
  $statusMap = [
    'Pending'      => ['cls' => 's-pending',   'icon' => 'bi-clock'],
    'Approved'     => ['cls' => 's-approved',  'icon' => 'bi-check-circle'],
    'Rejected'     => ['cls' => 's-rejected',  'icon' => 'bi-x-circle'],
    'PO Generated' => ['cls' => 's-completed', 'icon' => 'bi-archive'],
  ];
  $s = $statusMap[$pr['status']] ?? ['cls' => 's-pending', 'icon' => 'bi-circle'];
?>
<div class="proc-page">
  <div class="proc-header">
    <div class="proc-header-left">
      <h1><i class="bi bi-file-earmark-text" style="color:var(--accent);margin-right:8px;"></i><?= htmlspecialchars($pr['pr_number'] ?? 'PR #'.$viewId) ?></h1>
      <p>Purchase Request Detail</p>
    </div>
    <a href="view_purchase_requests.php" class="act-btn act-view"><i class="bi bi-arrow-left"></i> Back to List</a>
  </div>

  <div class="main-card" style="margin-bottom:16px">
    <div class="card-top">
      <div class="card-top-title"><i class="bi bi-info-circle"></i> PR Information</div>
      <span class="badge-status <?= $s['cls'] ?>"><i class="bi <?= $s['icon'] ?>"></i> <?= htmlspecialchars($pr['status'] ?? 'Pending') ?></span>
    </div>
    <div style="padding:16px 20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px 24px">
      <div><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Requested By</div><div style="font-weight:600"><?= htmlspecialchars($pr['requested_by'] ?? '-') ?></div></div>
      <div><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Office</div><div style="font-weight:600"><?= htmlspecialchars($pr['office'] ?? '-') ?></div></div>
      <?php if (!empty($pr['section'])): ?>
      <div><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Section</div><div style="font-weight:600"><?= htmlspecialchars($pr['section']) ?></div></div>
      <?php endif; ?>
      <?php if (!empty($pr['designation'])): ?>
      <div><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Designation</div><div style="font-weight:600"><?= htmlspecialchars($pr['designation']) ?></div></div>
      <?php endif; ?>
      <div><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Total Amount</div><div style="font-weight:700;color:var(--accent)">&#8369;<?= number_format((float)($pr['total_amount'] ?? 0), 2) ?></div></div>
      <div><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Date Submitted</div><div style="font-weight:600"><?= date('M d, Y', strtotime($pr['created_at'])) ?></div></div>
      <?php if (!empty($pr['fund_source'])): ?>
      <div><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Fund Source</div><div style="font-weight:600"><?= htmlspecialchars($pr['fund_source']) ?></div></div>
      <?php endif; ?>
      <?php if (!empty($pr['procurement_mode'])): ?>
      <div style="grid-column:span 2"><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Procurement Mode</div><div style="font-weight:600"><?= htmlspecialchars($pr['procurement_mode']) ?></div></div>
      <?php endif; ?>
      <?php if (!empty($pr['purpose'])): ?>
      <div style="grid-column:1/-1"><div style="font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em">Purpose</div><div><?= htmlspecialchars($pr['purpose']) ?></div></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="main-card">
    <div class="card-top">
      <div class="card-top-title"><i class="bi bi-list-ul"></i> Items</div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Stock/Property No.</th>
            <th>Unit</th>
            <th>Description</th>
            <th style="text-align:right">Qty</th>
            <th style="text-align:right">Unit Cost</th>
            <th style="text-align:right">Total Cost</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($pr['items'])): ?>
            <?php foreach ($pr['items'] as $item): ?>
              <?php
                $qty  = $item['quantity'] ?? null;
                $uc   = $item['unit_cost'] ?? null;
                $tc   = $item['total_cost'] ?? ($item['amount'] ?? null);
                if (!is_numeric($tc) && is_numeric($qty) && is_numeric($uc)) $tc = (float)$qty * (float)$uc;
              ?>
              <tr>
                <td class="td-id"><?= htmlspecialchars($item['stock_property_no'] ?? '-') ?></td>
                <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                <td style="white-space:pre-line"><?= nl2br(htmlspecialchars($item['description'] ?? ($item['item_description'] ?? '-'))) ?></td>
                <td class="td-id" style="text-align:right"><?= is_numeric($qty) ? htmlspecialchars((string)$qty) : '-' ?></td>
                <td class="td-id" style="text-align:right"><?= is_numeric($uc) ? '&#8369;'.number_format((float)$uc,2) : '-' ?></td>
                <td class="td-id" style="text-align:right"><?= is_numeric($tc) ? '&#8369;'.number_format((float)$tc,2) : '-' ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">No items found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <div></div>
      <div style="padding:12px 20px">
        <a href="functions/generate_pr.php?id=<?= $viewId ?>" class="act-btn" style="background:var(--accent);color:#fff;text-decoration:none">
          <i class="bi bi-file-earmark-word"></i> Generate Word
        </a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php else: ?>
<div class="proc-page">

  <div class="proc-header">
    <div class="proc-header-left">
      <h1><i class="bi bi-card-checklist" style="color:var(--accent);margin-right:8px;"></i>Purchase Requests</h1>
      <p>View and manage all submitted purchase requests.</p>
    </div>
  </div>

  <div class="main-card">
    <div class="card-top">
      <div class="card-top-title"><i class="bi bi-table"></i> PR Records</div>
      <div class="toolbar">
        <div class="search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" id="searchInput" placeholder="Search PR No., office, requestor…" oninput="debounce(loadPRs)">
        </div>
        <select class="filter-select" id="statusFilter" onchange="loadPRs()">
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
            <th>Total Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="prTableBody">
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">Loading…</td></tr>
        </tbody>
      </table>
    </div>

    <div class="table-footer">
      <div class="footer-info" id="footerInfo"></div>
      <div class="pagination" id="pagination"></div>
    </div>
  </div>
</div>

<script>
const FETCH_URL = 'functions/get_purchase_requests.php';
let currentPage = 1, debounceTimer;

const statusMap = {
  Pending:      { cls: 's-pending',   icon: 'bi-clock' },
  Approved:     { cls: 's-approved',  icon: 'bi-check-circle' },
  Rejected:     { cls: 's-rejected',  icon: 'bi-x-circle' },
  'PO Generated': { cls: 's-completed', icon: 'bi-archive' },
};

function debounce(fn) {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fn, 350);
}

function loadPRs(page = 1) {
  currentPage = page;
  const search = document.getElementById('searchInput').value.trim();
  const status = document.getElementById('statusFilter').value;

  const params = new URLSearchParams({ page, search, status });

  fetch(`${FETCH_URL}?${params}`)
    .then(r => r.json())
    .then(res => {
      renderRows(res.data);
      renderFooter(res.total, res.pages, res.current);
    })
    .catch(() => {
      document.getElementById('prTableBody').innerHTML =
        `<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">Failed to load records.</td></tr>`;
    });
}

function renderRows(rows) {
  const body = document.getElementById('prTableBody');
  if (!rows.length) {
    body.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">
      <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;"></i>No records found.</td></tr>`;
    return;
  }
  body.innerHTML = rows.map(r => {
    const s = statusMap[r.status] || { cls: 's-pending', icon: 'bi-circle' };
    const date = new Date(r.created_at).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
    return `<tr>
      <td class="td-id">${r.pr_number}</td>
      <td>${r.requested_by}</td>
      <td>${r.office}</td>
      <td class="td-id">₱${parseFloat(r.total_amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
      <td><span class="badge-status ${s.cls}"><i class="bi ${s.icon}"></i> ${r.status}</span></td>
      <td>${date}</td>
      <td>
        <a href="view_purchase_requests.php?id=${r.id}" class="act-btn act-view" title="View"><i class="bi bi-eye"></i></a>
      </td>
    </tr>`;
  }).join('');
}

function renderFooter(total, pages, current) {
  const start = (current - 1) * 10 + 1;
  const end   = Math.min(current * 10, total);
  document.getElementById('footerInfo').textContent = total ? `Showing ${start}–${end} of ${total} records` : '';

  let html = '';
  if (pages > 1) {
    if (current > 1) html += `<button class="page-btn" onclick="loadPRs(${current - 1})"><i class="bi bi-chevron-left"></i></button>`;
    for (let i = 1; i <= pages; i++) {
      html += `<button class="page-btn${i === current ? ' active' : ''}" onclick="loadPRs(${i})">${i}</button>`;
    }
    if (current < pages) html += `<button class="page-btn" onclick="loadPRs(${current + 1})"><i class="bi bi-chevron-right"></i></button>`;
  }
  document.getElementById('pagination').innerHTML = html;
}

loadPRs();
</script>
<?php endif; ?>
