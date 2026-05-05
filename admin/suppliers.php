<?php
if (!isset($_SESSION)) {
    session_start();
}
include '../config/localdb.php';
include 'functions/userfunctions.php';
include 'functions/authorization.php';
include 'functions/supplier_actions.php';

$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 10;
$offset         = ($page - 1) * $perPage;
$search         = trim($_GET['search'] ?? '');
$filterStatus   = $_GET['status'] ?? '';
$filterCategory = $_GET['category'] ?? '';

$result     = getSuppliers($con, $search, $filterStatus, $filterCategory, $perPage, $offset);
$suppliers  = $result['rows'];
$total      = $result['total'];
$totalPages = max(1, (int)ceil($total / $perPage));
$cats       = getSupplierCategories($con);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="admin-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Suppliers</h2>
    <button class="btn btn-primary" onclick="openModal()"><i class="bi bi-plus-lg"></i> Add Supplier</button>
  </div>

  <!-- Filters -->
  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
      <input type="text" name="search" class="form-control" placeholder="Search name, email, phone…" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-2">
      <select name="status" class="form-select">
        <option value="">All Status</option>
        <option value="Active"   <?= $filterStatus === 'Active'   ? 'selected' : '' ?>>Active</option>
        <option value="Inactive" <?= $filterStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <div class="col-md-3">
      <select name="category" class="form-select">
        <option value="">All Categories</option>
        <?php foreach ($cats as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto d-flex gap-2">
      <button type="submit" class="btn btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
      <a href="suppliers.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  <!-- Table -->
  <div class="admin-card">
    <div class="admin-card-head">
      <div>
        <div class="admin-card-title">Supplier List</div>
        <div class="admin-card-sub"><?= $total ?> suppliers found</div>
      </div>
    </div>
    <div class="admin-card-body p-0">
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Company Name</th>
              <th>Name</th>
              <th>Number</th>
              <th>Location</th>
              <th>Category</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($suppliers)): ?>
              <tr><td colspan="6" class="empty-cell">No suppliers found.</td></tr>
            <?php else: ?>
              <?php foreach ($suppliers as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['company_name'] ?: '—') ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
                <td><?= htmlspecialchars($s['location'] ?: '—') ?></td>
                <td><?= htmlspecialchars($s['category'] ?: '—') ?></td>
                <td>
                  <button class="btn btn-sm btn-info"    onclick="previewSupplier(<?= $s['id'] ?>)"  title="Preview"><i class="bi bi-eye"></i></button>
                  <button class="btn btn-sm btn-warning" onclick="editSupplier(<?= $s['id'] ?>)"     title="Edit"><i class="bi bi-pencil"></i></button>
                  <a      class="btn btn-sm btn-success" href="functions/supplier_actions.php?action=word&id=<?= $s['id'] ?>" title="Export Word"><i class="bi bi-file-word"></i></a>
                  <button class="btn btn-sm btn-danger"  onclick="deleteSupplier(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>')" title="Delete"><i class="bi bi-trash"></i></button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <nav>
    <ul class="pagination pagination-sm justify-content-end mt-2">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&category=<?= urlencode($filterCategory) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>

</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun)">
        <h5 class="modal-title text-white" id="modalTitle">Add Supplier</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="supplierForm">
          <input type="hidden" id="supplierId" name="id" value="0">
          <input type="hidden" name="action" value="save">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Supplier Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="fName" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Email</label>
              <input type="email" class="form-control" name="email" id="fEmail">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Phone</label>
              <input type="text" class="form-control" name="phone" id="fPhone">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Location</label>
              <input type="text" class="form-control" name="location" id="fLocation">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Category</label>
              <input type="text" class="form-control" name="category" id="fCategory" list="catList" placeholder="e.g. Office Supplies">
              <datalist id="catList">
                <?php foreach ($cats as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Status</label>
              <select class="form-select" name="status" id="fStatus">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Company Name</label>
              <input type="text" class="form-control" name="company_name" id="fCompanyName">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Position / Designation</label>
              <input type="text" class="form-control" name="position" id="fPosition">
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun)">
        <h5 class="modal-title text-white">Supplier Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="previewBody">Loading…</div>
      <div class="modal-footer">
        <a id="previewWordBtn" href="#" class="btn btn-success"><i class="bi bi-file-word"></i> Export Word</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- FIX: Removed duplicate Bootstrap JS (keep only one). SweetAlert2 added here if not already in header.php -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function esc(v) {
  return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

let supplierModal, previewModal;
document.addEventListener('DOMContentLoaded', function () {
  supplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));
  previewModal  = new bootstrap.Modal(document.getElementById('previewModal'));
});

function openModal(data = null) {
  document.getElementById('modalTitle').textContent  = data ? 'Edit Supplier' : 'Add Supplier';
  document.getElementById('supplierId').value        = data?.id           ?? 0;
  document.getElementById('fName').value             = data?.name         ?? '';
  document.getElementById('fEmail').value            = data?.email        ?? '';
  document.getElementById('fPhone').value            = data?.phone        ?? '';
  document.getElementById('fLocation').value         = data?.location     ?? '';
  document.getElementById('fCategory').value         = data?.category     ?? '';
  document.getElementById('fStatus').value           = data?.status       ?? 'Active';
  document.getElementById('fCompanyName').value      = data?.company_name ?? '';
  document.getElementById('fPosition').value         = data?.position     ?? '';
  supplierModal.show();
}

// FIX: Added .catch() for error handling
function editSupplier(id) {
  fetch(`functions/supplier_actions.php?action=get&id=${id}`)
    .then(r => r.json())
    .then(data => openModal(data))
    .catch(() => Swal.fire('Error', 'Failed to load supplier data.', 'error'));
}

// FIX: Added .catch() + removed mismatched fields (address/contact_number -> location/phone/position)
function previewSupplier(id) {
  document.getElementById('previewBody').innerHTML = 'Loading…';
  document.getElementById('previewWordBtn').href   = `functions/supplier_actions.php?action=word&id=${id}`;
  previewModal.show();
  fetch(`functions/supplier_actions.php?action=get&id=${id}`)
    .then(r => r.json())
    .then(s => {
      document.getElementById('previewBody').innerHTML = `
        <table class="table table-sm table-bordered mb-0">
          <tr><th style="width:35%;background:#E8EFF9">Code</th><td>${esc(s.supplier_code)}</td></tr>
          <tr><th style="background:#E8EFF9">Name</th><td>${esc(s.name)}</td></tr>
          <tr><th style="background:#E8EFF9">Email</th><td>${esc(s.email)||'—'}</td></tr>
          <tr><th style="background:#E8EFF9">Phone</th><td>${esc(s.phone)||'—'}</td></tr>
          <tr><th style="background:#E8EFF9">Location</th><td>${esc(s.location)||'—'}</td></tr>
          <tr><th style="background:#E8EFF9">Category</th><td>${esc(s.category)||'—'}</td></tr>
          <tr><th style="background:#E8EFF9">Status</th><td>${esc(s.status)}</td></tr>
          <tr><th style="background:#E8EFF9">Company Name</th><td>${esc(s.company_name)||'—'}</td></tr>
          <tr><th style="background:#E8EFF9">Position</th><td>${esc(s.position)||'—'}</td></tr>
          <tr><th style="background:#E8EFF9">Date Added</th><td>${esc(s.created_at)}</td></tr>
        </table>`;
    })
    .catch(() => {
      document.getElementById('previewBody').innerHTML = '<p class="text-danger">Failed to load supplier details.</p>';
    });
}

// FIX: Explicit id: id in URLSearchParams + added .catch()
function deleteSupplier(id, name) {
  Swal.fire({
    title: 'Delete Supplier?',
    text: `"${name}" will be removed.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#C0272D',
    confirmButtonText: 'Yes, delete',
    customClass: { popup: 'db-swal-popup', confirmButton: 'db-swal-confirm' }
  }).then(r => {
    if (!r.isConfirmed) return;
    fetch('functions/supplier_actions.php', {
      method: 'POST',
      body: new URLSearchParams({ action: 'delete', id: id })
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) location.reload();
        else Swal.fire('Error', res.message, 'error');
      })
      .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
  });
}

// FIX: Added .catch() for save errors
document.getElementById('supplierForm').addEventListener('submit', function (e) {
  e.preventDefault();
  fetch('functions/supplier_actions.php', {
    method: 'POST',
    body: new URLSearchParams(new FormData(this))
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        supplierModal.hide();
        Swal.fire({ icon: 'success', title: 'Saved!', text: res.message,
          customClass: { popup: 'db-swal-popup', confirmButton: 'db-swal-confirm' }
        }).then(() => location.reload());
      } else {
        Swal.fire('Error', res.message, 'error');
      }
    })
    .catch(() => Swal.fire('Error', 'Save failed. Please try again.', 'error'));
});
</script>
</body>
</html>