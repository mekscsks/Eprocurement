<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../config/localdb.php';
include 'functions/authorization.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$result = $con->query("SELECT * FROM purchase_requests WHERE deleted = 0 ORDER BY created_at DESC");
$all = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="admin-main">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0" style="font-family:'DM Serif Display',serif;color:var(--navy);">
                <i class="bi bi-file-earmark-check me-2" style="color:var(--sun)"></i>Resolution to Award
            </h4>
            <p style="color:var(--muted);font-size:.85rem;margin:.25rem 0 0;">Manage and generate RTA documents</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-head">
            <div class="admin-card-title">RTA Records</div>
            <div class="d-flex gap-2">
                <select id="rtaStatusFilter" class="form-select form-select-sm" style="width:160px;" onchange="filterRTA()">
                    <option value="">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                    <option value="PO Generated">PO Generated</option>
                </select>
                <input type="text" id="rtaSearch" class="form-control form-control-sm" placeholder="Search..." style="width:200px;" oninput="filterRTA()">
            </div>
        </div>
        <div class="admin-card-body p-0">
            <table class="admin-table" id="rtaTable">
                <thead>
                    <tr>
                        <th>PR No.</th>
                        <th>Requested By</th>
                        <th>Office</th>
                        <th>Total Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($all)): ?>
                    <tr><td colspan="7" class="empty-cell">No records found.</td></tr>
                <?php else: foreach ($all as $row):
                    $status = $row['status'] ?? 'Pending';
                    $pillClass = match($status) {
                        'Approved'     => 'sp-approved',
                        'Rejected'     => 'sp-rejected',
                        'PO Generated' => 'sp-completed',
                        default        => 'sp-pending'
                    };
                    $date = date('M d, Y', strtotime($row['updated_at'] ?? $row['created_at']));
                ?>
                    <tr data-status="<?= htmlspecialchars($status) ?>">
                        <td><?= htmlspecialchars($row['pr_number']) ?></td>
                        <td><?= htmlspecialchars($row['requested_by']) ?></td>
                        <td><?= htmlspecialchars($row['office']) ?></td>
                        <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                        <td><?= $date ?></td>
                        <td><span class="status-pill <?= $pillClass ?>"><?= htmlspecialchars($status) ?></span></td>
                        <td class="d-flex gap-1 flex-wrap">
                            <a href="view_purchase_requests.php?id=<?= $row['id'] ?>" class="admin-btn sm secondary">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <button class="admin-btn sm" onclick="openSuppliersModal(<?= $row['id'] ?>)">
                                <i class="bi bi-file-earmark-arrow-down"></i> Generate RTA
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Suppliers Modal -->
<div class="modal fade" id="suppliersModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:16px;border:none;">
      <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun);border-radius:16px 16px 0 0;">
        <h5 class="modal-title" style="font-family:'DM Serif Display',serif;color:#fff;"><i class="bi bi-people me-2"></i>Manage Suppliers</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:1.5rem;">
        <input type="hidden" id="modal_pr_id">
        <div id="supplierRows"></div>
        <button type="button" class="admin-btn sm mt-2" style="background:var(--green);" onclick="addSupplierRow()">
          <i class="bi bi-plus-circle"></i> Add Another Supplier
        </button>
      </div>
      <div class="modal-footer" style="border-top:1px solid var(--border);">
        <button type="button" class="admin-btn sm" style="background:var(--muted);" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="admin-btn sm" style="background:var(--green);" onclick="saveSuppliers(false)"><i class="bi bi-save"></i> Save</button>
        <button type="button" class="admin-btn sm" onclick="saveSuppliers(true)"><i class="bi bi-file-earmark-arrow-down"></i> Save & Generate RTA</button>
      </div>
    </div>
  </div>
</div>

<script>
let allSuppliers = [];

async function openSuppliersModal(prId) {
    document.getElementById('modal_pr_id').value = prId;
    document.getElementById('supplierRows').innerHTML = '<p class="text-muted">Loading...</p>';
    const modal = new bootstrap.Modal(document.getElementById('suppliersModal'));
    modal.show();

    if (!allSuppliers.length) {
        const res = await fetch('functions/rta_actions.php?action=list_suppliers');
        allSuppliers = await res.json();
    }

    const res2 = await fetch('functions/rta_actions.php?action=get_suppliers&pr_id=' + prId);
    const existing = await res2.json();

    document.getElementById('supplierRows').innerHTML = '';
    if (existing.length) {
        existing.forEach((s, i) => addSupplierRow(s.supplier_id, s.quoted_amount, s.is_winner == 1 ? i : -1, i, existing.length));
    } else {
        addSupplierRow();
    }
}

function addSupplierRow(selectedId = '', amount = '', isWinner = false) {
    const container = document.getElementById('supplierRows');
    const idx = container.children.length;
    const options = allSuppliers.map(s =>
        `<option value="${s.id}" ${s.id == selectedId ? 'selected' : ''}>${s.name} (${s.supplier_code})</option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'supplier-row d-flex align-items-center gap-2 mb-2 p-2';
    row.style = 'background:var(--bg);border-radius:10px;border:1px solid var(--border);';
    row.innerHTML = `
        <select name="supplier_id[]" class="form-select form-select-sm" style="flex:2;" required>
            <option value="">-- Select Supplier --</option>
            ${options}
        </select>
        <input type="number" name="quoted_amount[]" class="form-control form-control-sm" placeholder="Quoted Amount" value="${amount}" min="0" step="0.01" style="flex:1;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.supplier-row').remove();" title="Remove">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(row);
}

async function saveSuppliers(andGenerate = false) {
    const prId = document.getElementById('modal_pr_id').value;
    const rows = document.querySelectorAll('.supplier-row');
    const formData = new FormData();
    formData.append('action', 'save_suppliers');
    formData.append('pr_id', prId);

    let hasAny = false;
    rows.forEach((row, i) => {
        const sid = row.querySelector('select').value;
        const amt = row.querySelector('input[type=number]').value;
        if (sid) hasAny = true;
        formData.append('supplier_id[]', sid);
        formData.append('quoted_amount[]', amt);
    });

    if (!hasAny) {
        Swal.fire({ icon:'warning', title:'No Supplier', text:'Please select at least one supplier.' });
        return;
    }

    const res = await fetch('functions/rta_actions.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('suppliersModal')).hide();
        if (andGenerate) {
            window.open('functions/generate_rta.php?pr_id=' + prId, '_blank');
        } else {
            Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Suppliers saved!', showConfirmButton:false, timer:2500, timerProgressBar:true });
        }
    } else {
        Swal.fire({ icon:'error', title:'Error', text: data.message || 'Failed to save.' });
    }
}

function filterRTA() {
    const q      = document.getElementById('rtaSearch').value.toLowerCase();
    const status = document.getElementById('rtaStatusFilter').value;
    document.querySelectorAll('#rtaTable tbody tr').forEach(tr => {
        const matchStatus = !status || tr.dataset.status === status;
        const matchSearch = !q || tr.textContent.toLowerCase().includes(q);
        tr.style.display = matchStatus && matchSearch ? '' : 'none';
    });
}
</script>

</body>
</html>
