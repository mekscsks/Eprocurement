<?php 
include '../config/localdb.php';
include 'functions/purchasefunctions.php';
include 'functions/authorization.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// Handle date update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rfq_update_date_id'], $_POST['rfq_update_date'])) {
    header('Content-Type: application/json');
    $id   = (int)$_POST['rfq_update_date_id'];
    $date = $_POST['rfq_update_date'];
    $stmt = $con->prepare("UPDATE purchase_requests SET created_at=? WHERE id=? AND deleted=0");
    $stmt->bind_param('si', $date, $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rfq_status_id'], $_POST['rfq_status'])) {
    updatePRStatus($con, (int)$_POST['rfq_status_id'], $_POST['rfq_status']);
    header('Location: rfq.php');
    exit;
}

// Handle supplier link
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_supplier_pr_id'], $_POST['link_supplier_id'])) {
    $stmt = $con->prepare("UPDATE purchase_requests SET supplier_id=? WHERE id=? AND deleted=0");
    $stmt->bind_param('ii', $_POST['link_supplier_id'], $_POST['link_supplier_pr_id']);
    $stmt->execute();
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

$prs = getAllPR($con);

// Fetch suppliers for dropdown
$suppliersRes = $con->query("SELECT id, name FROM suppliers WHERE deleted=0 AND status='Active' ORDER BY name ASC");
$suppliersList = [];
if ($suppliersRes) while ($r = $suppliersRes->fetch_assoc()) $suppliersList[] = $r;

$prIds     = array_column($prs, 'id');
$prNumbers = array_column($prs, 'pr_number');
$usePrNoItems = tableExists($con, 'pr_items') && tableHasColumn($con, 'pr_items', 'pr_no');
$itemsByPrId  = getPRItemsByPRIds($con, $prIds);
$itemsByPrNo  = $usePrNoItems ? getPRItemsByPRNumbers($con, $prNumbers) : [];
?>

<div class="admin-page">

    <div class="mb-4">
        <h4 style="font-family:'DM Serif Display',serif;color:var(--navy);margin:0;">Request for Quotation</h4>
        <p style="color:var(--muted);font-size:.85rem;margin:.25rem 0 0;">Manage and monitor all RFQ records</p>
    </div>

    <!-- Table -->
    <div class="admin-card">
        <div class="admin-card-head">
            <div><div class="admin-card-title">RFQ Records</div></div>
            <input type="text" id="rfqSearch" class="form-control form-control-sm" placeholder="Search..." 
                style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2);color:#fff;min-width:200px;width:auto;">
        </div>
        <div style="padding:0;">
            <div class="table-responsive">
                <table class="admin-table" id="rfqTable">
                    <thead>
                        <tr>
                            <th>PR No.</th>
                            <th>Requested By</th>
                            <th>Office</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($prs)): ?>
                        <?php foreach ($prs as $pr): 
                            $status = $pr['status'] ?? 'Pending';
                            $pillClass = match($status) {
                                'Approved'     => 'sp-approved',
                                'Rejected'     => 'sp-rejected',
                                default        => 'sp-pending'
                            };
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($pr['pr_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($pr['requested_by'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($pr['office'] ?? '-') ?></td>
                            <td><?= isset($pr['created_at']) ? date('M d, Y', strtotime($pr['created_at'])) : '-' ?></td>
                            <td><span class="status-pill <?= $pillClass ?>"><?= htmlspecialchars($status) ?></span></td>
                            <td>
                                <button class="admin-btn sm btn-view-rfq"
                                    data-id="<?= $pr['id'] ?>"
                                    data-prno="<?= htmlspecialchars($pr['pr_number'] ?? '-', ENT_QUOTES) ?>"
                                    data-requestedby="<?= htmlspecialchars($pr['requested_by'] ?? '-', ENT_QUOTES) ?>"
                                    data-office="<?= htmlspecialchars($pr['office'] ?? '-', ENT_QUOTES) ?>"
                                    data-section="<?= htmlspecialchars($pr['section'] ?? '-', ENT_QUOTES) ?>"
                                    data-enduse="<?= htmlspecialchars($pr['purpose'] ?? $pr['end_use'] ?? '-', ENT_QUOTES) ?>"
                                    data-amount="<?= number_format((float)($pr['total_amount'] ?? 0), 2) ?>"
                                    data-date="<?= htmlspecialchars($pr['created_at'] ?? '', ENT_QUOTES) ?>"
                                    data-status="<?= htmlspecialchars($status, ENT_QUOTES) ?>">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <?php if ($status === 'Pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="rfq_status_id" value="<?= $pr['id'] ?>">
                                    <input type="hidden" name="rfq_status" value="Approved">
                                    <button type="submit" class="admin-btn sm" style="background:var(--green);">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="rfq_status_id" value="<?= $pr['id'] ?>">
                                    <input type="hidden" name="rfq_status" value="Rejected">
                                    <button type="submit" class="admin-btn sm" style="background:var(--red);">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($status === 'Approved'): ?>
                                <button class="admin-btn sm" style="background:var(--navy-mid);" 
                                    onclick="openGenerateRFQ(<?= $pr['id'] ?>, <?= (int)($pr['supplier_id'] ?? 0) ?>, '<?= htmlspecialchars($pr['created_at'] ?? '', ENT_QUOTES) ?>')">
                                    <i class="bi bi-file-earmark-word"></i> Generate
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-cell"><i class="bi bi-inbox me-2"></i>No RFQ records found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Single View Modal -->
<div class="modal fade" id="rfqViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun);">
                <h5 class="modal-title" style="color:#fff;font-family:'DM Serif Display',serif;">
                    <i class="bi bi-file-earmark-text me-2"></i><span id="vPrNo"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div style="font-size:.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;">Requested By</div>
                        <div id="vRequestedBy"></div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;">Office</div>
                        <div id="vOffice"></div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;">Section</div>
                        <div id="vSection"></div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;">Purpose</div>
                        <div id="vEndUse"></div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:.75rem;color:var(--muted);font-weight:600;text-transform:uppercase;">Total Amount</div>
                        <div id="vAmount" style="color:var(--navy);font-weight:700;"></div>
                    </div>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr><th>#</th><th>Description</th><th>Qty</th><th>Unit</th><th>Unit Cost</th><th>Amount</th></tr>
                    </thead>
                    <tbody id="vItemsBody"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn sm" style="background:var(--muted);" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Supplier Select Modal -->
<div class="modal fade" id="generateRFQModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun);">
                <h5 class="modal-title" style="color:#fff;font-family:'DM Serif Display',serif;">
                    <i class="bi bi-file-earmark-word me-2"></i>Generate RFQ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="link_supplier_pr_id" id="gen_pr_id">
                    <label style="font-size:.85rem;font-weight:600;color:var(--navy);">Select Supplier</label>
                    <select name="link_supplier_id" id="gen_supplier_id" class="form-select mt-1">
                        <option value="">-- No Supplier --</option>
                        <?php foreach ($suppliersList as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label style="font-size:.85rem;font-weight:600;color:var(--navy);margin-top:12px;display:block;">Date</label>
                    <input type="date" id="gen_date" class="form-control mt-1">
                    <label style="font-size:.85rem;font-weight:600;color:var(--navy);margin-top:12px;display:block;">Procurement Project</label>
                    <input type="text" id="gen_procurement_project" class="form-control mt-1" placeholder="e.g. Supply of Office Materials">
                    <label style="font-size:.85rem;font-weight:600;color:var(--navy);margin-top:12px;display:block;">Mode of Procurement</label>
                    <select id="gen_mode_of_procurement" class="form-select mt-1">
                        <option value="">-- Select Mode --</option>
                        <option>Section 27 Competitive Bidding</option>
                        <option>Section 28 Limited Source Bidding</option>
                        <option>Section 29 Competitive Dialogue</option>
                        <option>Section 30 Unsolicited Offer with Bid Matching</option>
                        <option>Section 31 Direct Contracting</option>
                        <option>Section 32 Direct Acquisition</option>
                        <option>Section 33 Repeat Order</option>
                        <option>Section 34 Small Value Procurement</option>
                        <option>Section 35 Negotiated Procurement</option>
                        <option>Section 36 Direct Sales</option>
                        <option>Section 37 Direct Procurement for Science, Technology and Innovation</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="admin-btn sm" id="gen_save_btn">
                        <i class="bi bi-floppy"></i> Save &amp; Generate
                    </button>
                    <button type="button" class="admin-btn sm" style="background:var(--muted);" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Build items map keyed by PR id for JS
$itemsForJs = [];
foreach ($prs as $pr) {
    $prId  = (int)$pr['id'];
    $prNo  = $pr['pr_number'] ?? '';
    $items = $usePrNoItems ? ($itemsByPrNo[$prNo] ?? []) : ($itemsByPrId[$prId] ?? []);
    $itemsForJs[$prId] = array_map(fn($it) => [
        'desc'      => $it['description'] ?? $it['item_description'] ?? '-',
        'qty'       => $it['quantity'] ?? 0,
        'unit'      => $it['unit'] ?? '-',
        'unit_cost' => (float)($it['unit_cost'] ?? 0),
        'amount'    => is_numeric($it['total_cost'] ?? null) ? (float)$it['total_cost'] : (float)($it['quantity'] ?? 0) * (float)($it['unit_cost'] ?? 0),
    ], $items);
}
?>
<script>
const rfqItems = <?= json_encode($itemsForJs, JSON_HEX_TAG) ?>;

    // Search
    document.getElementById('rfqSearch').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#rfqTable tbody tr').forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // View modal
    document.querySelectorAll('.btn-view-rfq').forEach(btn => {
        btn.addEventListener('click', function () {
            const id     = this.dataset.id;
            const status = this.dataset.status;

            document.getElementById('vPrNo').textContent       = this.dataset.prno;
            document.getElementById('vRequestedBy').textContent = this.dataset.requestedby;
            document.getElementById('vOffice').textContent     = this.dataset.office;
            document.getElementById('vSection').textContent    = this.dataset.section;
            document.getElementById('vEndUse').textContent     = this.dataset.enduse;
            document.getElementById('vAmount').textContent     = '\u20b1' + this.dataset.amount;

            const items  = rfqItems[id] || [];
            const tbody  = document.getElementById('vItemsBody');
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-cell">No items found.</td></tr>';
            } else {
                tbody.innerHTML = items.map((it, i) => `<tr>
                    <td>${i + 1}</td>
                    <td>${it.desc}</td>
                    <td>${it.qty}</td>
                    <td>${it.unit}</td>
                    <td>\u20b1${it.unit_cost.toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
                    <td>\u20b1${it.amount.toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
                </tr>`).join('');
            }

            new bootstrap.Modal(document.getElementById('rfqViewModal')).show();
        });
    });

    function openGenerateRFQ(prId, currentSupplierId, currentDate) {
        document.getElementById('gen_pr_id').value = prId;
        const sel = document.getElementById('gen_supplier_id');
        sel.value = currentSupplierId || '';
        document.getElementById('gen_date').value = currentDate ? currentDate.substring(0, 10) : '';
        document.getElementById('gen_save_btn').onclick = function(e) {
            e.preventDefault();
            const supplierId         = sel.value;
            const date               = document.getElementById('gen_date').value;
            const procProject        = document.getElementById('gen_procurement_project').value;
            const modeOfProcurement  = document.getElementById('gen_mode_of_procurement').value;
            const saveDate = date
                ? fetch('rfq.php', { method: 'POST', body: (() => { const f = new FormData(); f.append('rfq_update_date_id', prId); f.append('rfq_update_date', date); return f; })() })
                : Promise.resolve();
            const saveSupplier = fetch('rfq.php', { method: 'POST', body: (() => { const f = new FormData(); f.append('link_supplier_pr_id', prId); f.append('link_supplier_id', supplierId); return f; })() });
            Promise.all([saveDate, saveSupplier]).then(() => {
                const params = new URLSearchParams({ id: prId });
                if (procProject)       params.set('procurement_project',   procProject);
                if (modeOfProcurement) params.set('mode_of_procurement',   modeOfProcurement);
                window.location.href = 'functions/generate_rfq.php?' + params.toString();
            });
        };
        new bootstrap.Modal(document.getElementById('generateRFQModal')).show();
    }
</script>

<?php showAlert(); ?>
</body>
</html>
