<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../config/localdb.php';
include 'functions/authorization.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// Auto-create notice_to_award table if not exists
$con->query("CREATE TABLE IF NOT EXISTS `notice_to_award` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nta_number` VARCHAR(50) NOT NULL,
    `pr_id` INT DEFAULT NULL,
    `procurement_id` INT DEFAULT NULL,
    `supplier` VARCHAR(255) DEFAULT '',
    `project` VARCHAR(500) DEFAULT '',
    `amount` DECIMAL(15,2) DEFAULT 0,
    `contact_name` VARCHAR(255) DEFAULT '',
    `contact_position` VARCHAR(255) DEFAULT '',
    `company_name` VARCHAR(255) DEFAULT '',
    `company_location` VARCHAR(255) DEFAULT '',
    `company_city` VARCHAR(255) DEFAULT '',
    `salutation` VARCHAR(10) DEFAULT 'Mr.',
    `status` ENUM('Pending','Completed') DEFAULT 'Pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

foreach (['contact_name VARCHAR(255) DEFAULT ""','contact_position VARCHAR(255) DEFAULT ""','company_name VARCHAR(255) DEFAULT ""','company_location VARCHAR(255) DEFAULT ""','company_city VARCHAR(255) DEFAULT ""','salutation VARCHAR(10) DEFAULT "Mr."'] as $col) {
    $con->query("ALTER TABLE notice_to_award ADD COLUMN IF NOT EXISTS $col");
}

$all = $con->query("SELECT * FROM notice_to_award ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$approvedProcs = $con->query("
    SELECT id, pr_number, requested_by, office, total_amount
    FROM purchase_requests
    WHERE status = 'Approved'
      AND deleted = 0
      AND id NOT IN (SELECT IFNULL(pr_id, 0) FROM notice_to_award WHERE pr_id IS NOT NULL)
    ORDER BY updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

$suppliers = $con->query("SELECT id, name FROM suppliers WHERE deleted=0 AND status='Active' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$alert = $_SESSION['nta_alert'] ?? null;
unset($_SESSION['nta_alert']);
?>

<div class="admin-main">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0" style="font-family:'DM Serif Display',serif;color:var(--navy);">
                <i class="bi bi-file-earmark-text me-2" style="color:var(--sun)"></i>Notice of Award
            </h4>
            <p style="color:var(--muted);font-size:.85rem;margin:.25rem 0 0;">Manage and generate NOA documents</p>
        </div>
        <button class="admin-btn sm" data-bs-toggle="modal" data-bs-target="#addNtaModal">
            <i class="bi bi-plus-lg"></i> Add NOA Manually
        </button>
    </div>

    <?php if ($alert): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: <?= json_encode($alert['type']) ?>,
                title: <?= json_encode($alert['type'] === 'success' ? 'Success!' : 'Error') ?>,
                text: <?= json_encode($alert['msg']) ?>,
                confirmButtonColor: '#1d2671'
            });
        });
    </script>
    <?php endif; ?>

    <!-- BAC RESOLUTIONS READY FOR NOA -->
    <?php if (!empty($approvedProcs)): ?>
    <div class="admin-card mb-4" style="border-left:4px solid var(--sun);">
        <div class="admin-card-head">
            <div>
                <div class="admin-card-title">⚡ BAC Resolutions — Ready for NOA</div>
                <small style="color:var(--muted);">Click "Create NOA" to issue a Notice of Award.</small>
            </div>
            <span class="admin-card-pill"><?= count($approvedProcs) ?> records</span>
        </div>
        <div class="admin-card-body p-0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th><th>PR No.</th><th>Requested By</th><th>Office</th><th>Amount</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approvedProcs as $i => $proc): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($proc['pr_number']) ?></td>
                        <td><?= htmlspecialchars($proc['requested_by']) ?></td>
                        <td><?= htmlspecialchars($proc['office']) ?></td>
                        <td>₱<?= number_format((float)$proc['total_amount'], 2) ?></td>
                        <td>
                            <button type="button" class="admin-btn sm" style="background:var(--sun);color:#fff;"
                                onclick="openCreateNta(<?= $proc['id'] ?>, '<?= addslashes($proc['pr_number']) ?>', '<?= addslashes($proc['requested_by']) ?>', <?= (float)$proc['total_amount'] ?>)">
                                <i class="bi bi-file-earmark-plus"></i> Create NOA
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- MAIN NOA TABLE -->
    <div class="admin-card">
        <div class="admin-card-head">
            <div class="admin-card-title">NOA Records</div>
            <div class="d-flex gap-2">
                <select id="noaStatusFilter" class="form-select form-select-sm" style="width:140px;" onchange="filterNOA()">
                    <option value="">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Completed">Completed</option>
                </select>
                <input type="text" id="noaSearch" class="form-control form-control-sm" placeholder="Search..." style="width:200px;" oninput="filterNOA()">
            </div>
        </div>
        <div class="admin-card-body p-0">
            <table class="admin-table" id="noaTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>NOA No.</th>
                        <th>Supplier</th>
                        <th>Project</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="noaTableBody"></tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-top:1px solid var(--border);">
            <div id="noaFooterInfo" style="font-size:13px;color:var(--muted);"></div>
            <div id="noaPagination" style="display:flex;gap:4px;"></div>
        </div>
    </div>
</div>

<!-- CREATE NOA FROM PROCUREMENT MODAL -->
<div class="modal fade" id="createNtaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="functions/nta_actions.php">
            <input type="hidden" name="action" value="create_from_procurement">
            <input type="hidden" name="procurement_id" id="cnta_proc_id">
            <div class="modal-content" style="border-radius:16px;border:none;">
                <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun);">
                    <h5 class="modal-title" style="font-family:'DM Serif Display',serif;color:#fff;">
                        <i class="bi bi-file-earmark-plus me-2"></i>Create Notice of Award
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">REFERENCE NO.</label>
                        <div id="cnta_ref" class="fw-bold"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Winning Bidder / Supplier <span class="text-danger">*</span></label>
                        <select name="supplier" id="cnta_supplier" class="form-select" required>
                            <option value="">— Select Supplier —</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= htmlspecialchars($sup['name']) ?>"><?= htmlspecialchars($sup['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Project / Title</label>
                        <input type="text" name="project" id="cnta_project" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (₱) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="cnta_amount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <hr>
                    <p class="fw-semibold mb-2" style="font-size:13px;color:var(--navy);">Addressee Details</p>
                    <div class="row g-2">
                        <div class="col-3">
                            <label class="form-label fw-semibold">Salutation</label>
                            <select name="salutation" id="cnta_salutation" class="form-select">
                                <option>Mr.</option><option>Ms.</option><option>Mrs.</option><option>Dr.</option><option>Engr.</option>
                            </select>
                        </div>
                        <div class="col-9">
                            <label class="form-label fw-semibold">Contact Name <span class="text-danger">*</span></label>
                            <input type="text" name="contact_name" id="cnta_contact_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Position / Designation</label>
                            <input type="text" name="contact_position" id="cnta_contact_position" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Company Name</label>
                            <input type="text" name="company_name" id="cnta_company_name" class="form-control">
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold">Company Address</label>
                            <input type="text" name="company_location" id="cnta_company_location" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="company_city" id="cnta_company_city" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="admin-btn"><i class="bi bi-save"></i> Save NOA</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ADD NOA MANUALLY MODAL -->
<div class="modal fade" id="addNtaModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="functions/nta_actions.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-content" style="border-radius:16px;border:none;">
                <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun);">
                    <h5 class="modal-title" style="font-family:'DM Serif Display',serif;color:#fff;">
                        <i class="bi bi-plus-circle me-2"></i>Add Notice of Award
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier" id="add_supplier" class="form-select" required>
                            <option value="">— Select Supplier —</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= htmlspecialchars($sup['name']) ?>"><?= htmlspecialchars($sup['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Project / Description <span class="text-danger">*</span></label>
                        <input type="text" name="project" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (₱) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <hr>
                    <p class="fw-semibold mb-2" style="font-size:13px;color:var(--navy);">Addressee Details</p>
                    <div class="row g-2">
                        <div class="col-3">
                            <label class="form-label fw-semibold">Salutation</label>
                            <select name="salutation" class="form-select">
                                <option>Mr.</option><option>Ms.</option><option>Mrs.</option><option>Dr.</option><option>Engr.</option>
                            </select>
                        </div>
                        <div class="col-9">
                            <label class="form-label fw-semibold">Contact Name</label>
                            <input type="text" name="contact_name" id="add_contact_name" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Position</label>
                            <input type="text" name="contact_position" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Company Name</label>
                            <input type="text" name="company_name" id="add_company_name" class="form-control">
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold">Company Address</label>
                            <input type="text" name="company_location" id="add_company_location" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="company_city" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="admin-btn"><i class="bi bi-save"></i> Save NOA</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- VIEW NOA MODAL -->
<div class="modal fade" id="viewNtaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:16px;border:none;">
            <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun);">
                <h5 class="modal-title" style="font-family:'DM Serif Display',serif;color:#fff;">
                    <i class="bi bi-eye me-2"></i>NOA Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr><th style="width:40%;color:var(--muted);font-size:12px;">NOA No.</th><td id="view_noa_number" class="fw-semibold"></td></tr>
                        <tr><th style="color:var(--muted);font-size:12px;">Supplier</th><td id="view_noa_supplier"></td></tr>
                        <tr><th style="color:var(--muted);font-size:12px;">Project</th><td id="view_noa_project"></td></tr>
                        <tr><th style="color:var(--muted);font-size:12px;">Amount</th><td id="view_noa_amount" class="fw-semibold"></td></tr>
                        <tr><th style="color:var(--muted);font-size:12px;">Date</th><td id="view_noa_date"></td></tr>
                        <tr><th style="color:var(--muted);font-size:12px;">Status</th><td id="view_noa_status"></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const allNOA = <?= json_encode(array_map(fn($r) => [
    'id'         => $r['id'],
    'nta_number' => $r['nta_number'],
    'supplier'   => $r['supplier'],
    'project'    => $r['project'],
    'amount'     => number_format((float)$r['amount'], 2),
    'date'       => date('M d, Y', strtotime($r['created_at'])),
    'status'     => $r['status'],
], $all), JSON_HEX_TAG) ?>;

const PER_PAGE = 5;
let currentPage = 1;
let filtered = [...allNOA];

function filterNOA() {
    const q      = document.getElementById('noaSearch').value.toLowerCase();
    const status = document.getElementById('noaStatusFilter').value;
    filtered = allNOA.filter(r =>
        (!status || r.status === status) &&
        (!q || r.nta_number.toLowerCase().includes(q) || r.supplier.toLowerCase().includes(q) || r.project.toLowerCase().includes(q))
    );
    currentPage = 1;
    renderNOA();
}

function renderNOA() {
    const start = (currentPage - 1) * PER_PAGE;
    const slice = filtered.slice(start, start + PER_PAGE);
    const tbody = document.getElementById('noaTableBody');

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-cell">No records found.</td></tr>';
    } else {
        tbody.innerHTML = slice.map((r, i) => {
            const pillClass = r.status === 'Completed' ? 'sp-approved' : 'sp-pending';
            return `<tr>
                <td>${start + i + 1}</td>
                <td>${r.nta_number}</td>
                <td>${r.supplier}</td>
                <td>${r.project}</td>
                <td>₱${r.amount}</td>
                <td>${r.date}</td>
                <td><span class="status-pill ${pillClass}">${r.status}</span></td>
                <td class="d-flex gap-1">
                    <button class="admin-btn sm secondary" onclick="viewNOA(${r.id})">
                        <i class="bi bi-eye"></i> View
                    </button>
                    <a href="functions/generate_nta.php?id=${r.id}" class="admin-btn sm" target="_blank">
                        <i class="bi bi-file-earmark-word"></i> Generate
                    </a>
                    ${r.status === 'Pending' ? `
                    <form method="POST" action="functions/nta_actions.php" style="display:inline;">
                        <input type="hidden" name="action" value="mark_complete">
                        <input type="hidden" name="id" value="${r.id}">
                        <button type="submit" class="admin-btn sm" style="background:var(--green);">
                            <i class="bi bi-check-circle"></i> Complete
                        </button>
                    </form>` : ''}
                </td>
            </tr>`;
        }).join('');
    }

    // Pagination
    const total = Math.ceil(filtered.length / PER_PAGE);
    const pg    = document.getElementById('noaPagination');
    let html    = `<button class="pg-btn" onclick="goNOAPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>`;
    for (let p = 1; p <= total; p++) {
        html += `<button class="pg-btn ${p === currentPage ? 'active' : ''}" onclick="goNOAPage(${p})">${p}</button>`;
    }
    html += `<button class="pg-btn" onclick="goNOAPage(${currentPage + 1})" ${currentPage === total || total === 0 ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>`;
    pg.innerHTML = html;

    const end = Math.min(currentPage * PER_PAGE, filtered.length);
    document.getElementById('noaFooterInfo').innerHTML = filtered.length
        ? `Showing <strong>${start + 1}&ndash;${end}</strong> of <strong>${filtered.length}</strong> records`
        : '';
}

function goNOAPage(p) {
    const total = Math.ceil(filtered.length / PER_PAGE);
    if (p < 1 || p > total) return;
    currentPage = p;
    renderNOA();
}

function openCreateNta(id, prNumber, requestedBy, amount) {
    document.getElementById('cnta_proc_id').value   = id;
    document.getElementById('cnta_ref').textContent = prNumber;
    document.getElementById('cnta_project').value   = requestedBy;
    document.getElementById('cnta_amount').value    = amount > 0 ? amount : '';
    ['cnta_supplier','cnta_contact_name','cnta_contact_position','cnta_company_name','cnta_company_location','cnta_company_city'].forEach(f => {
        const el = document.getElementById(f); if (el) el.value = '';
    });
    document.getElementById('cnta_salutation').value = 'Mr.';
    new bootstrap.Modal(document.getElementById('createNtaModal')).show();
}

function autoFillSupplier(selectEl, prefix) {
    const name = selectEl.value;
    if (!name) return;
    fetch(`functions/nta_actions.php?action=get_supplier&name=${encodeURIComponent(name)}`)
        .then(r => r.json())
        .then(s => {
            if (!s || !s.name) return;
            const set = (id, val) => { const el = document.getElementById(id); if (el && val) el.value = val; };
            set(prefix + 'contact_name',     s.printed_name_signature);
            set(prefix + 'company_name',     s.company_name);
            set(prefix + 'company_location', s.address || s.location);
        });
}

function viewNOA(id) {
    const r = allNOA.find(x => x.id == id);
    if (!r) return;
    document.getElementById('view_noa_number').textContent  = r.nta_number;
    document.getElementById('view_noa_supplier').textContent = r.supplier;
    document.getElementById('view_noa_project').textContent  = r.project;
    document.getElementById('view_noa_amount').textContent   = '₱' + r.amount;
    document.getElementById('view_noa_date').textContent     = r.date;
    document.getElementById('view_noa_status').textContent   = r.status;
    new bootstrap.Modal(document.getElementById('viewNtaModal')).show();
}

document.getElementById('cnta_supplier').addEventListener('change', function () { autoFillSupplier(this, 'cnta_'); });
document.getElementById('add_supplier').addEventListener('change',  function () { autoFillSupplier(this, 'add_'); });

renderNOA();
</script>

</body>
</html>
