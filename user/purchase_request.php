<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/localdb.php';
include 'includes/auth.php';

// Check if the logged-in user has at least one approved PPMP
$account_id   = $_SESSION['auth_user']['account_id'] ?? 0;
$hasPpmp      = false;
$approvedPpmp = null;

if ($account_id) {
    $stmtChk = $con->prepare(
        "SELECT id, ppmp_type, office FROM tool_sub
         WHERE account_id = ? AND status = 'Approved'
         ORDER BY id DESC LIMIT 1"
    );
    $stmtChk->bind_param('i', $account_id);
    $stmtChk->execute();
    $resChk = $stmtChk->get_result();
    if ($resChk && $resChk->num_rows > 0) {
        $hasPpmp      = true;
        $approvedPpmp = $resChk->fetch_assoc();
    }
    $stmtChk->close();
}
?>
<?php include 'includes/header.php'; ?>
<?php if (isset($_SESSION['alert'])):
    $a = $_SESSION['alert'];
    unset($_SESSION['alert']);
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: <?= json_encode($a['type'] ?? 'info') ?>,
        title: <?= json_encode($a['title'] ?? ucfirst($a['type'] ?? 'Info')) ?>,
        text: <?= json_encode($a['msg'] ?? '') ?>,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
});
</script>
<?php endif; ?>
<link rel="stylesheet" href="assets/css/css.css">

<div class="db-layout">
    <?php include 'includes/sidebar.php'; ?>

    <main class="db-main">
        <div class="db-card">
            <div class="db-card-head">
                <div>
                    <div class="db-card-title">Purchase Request</div>
                    <div class="db-card-subtitle">Submit a new purchase request</div>
                </div>
                <div class="db-card-badge"><i class="bi bi-cart-plus"></i> PR Form</div>
            </div>

            <div class="db-card-body">

                <?php if (!$hasPpmp): ?>
                <div class="pr-ppmp-notice pr-ppmp-notice--warn">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    You need an <strong>approved PPMP</strong> before you can submit a Purchase Request.
                    <a href="ppmp.php" style="margin-left:.5rem;color:inherit;font-weight:700;text-decoration:underline;">Submit PPMP &rarr;</a>
                </div>
                <?php else: ?>
                <div class="pr-ppmp-notice">
                    <i class="bi bi-patch-check-fill"></i>
                    PPMP on file: <strong><?= htmlspecialchars($approvedPpmp['ppmp_type'] ?? '') ?></strong>
                    &mdash; <?= htmlspecialchars($approvedPpmp['office'] ?? '') ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="code.php" id="prForm">

                    <div class="db-row">
                        <div class="db-field">
                            <label>Office <span style="color:var(--red)">*</span></label>
                            <div class="db-field-inner is-select">
                                <i class="bi bi-building db-ficon"></i>
                                <select name="office" id="officeSelect" required <?= !$hasPpmp ? 'disabled' : '' ?>>
                                    <option value="">— Select Office —</option>
                                    <option value="Office of the Schools Division Superintendent">Office of the Schools Division Superintendent</option>
                                    <option value="Curriculum Implementation Division">Curriculum Implementation Division</option>
                                    <option value="School Governance and Operations Division">School Governance and Operations Division</option>
                                </select>
                            </div>
                        </div>
                        <div class="db-field">
                            <label>Unit / Section <span style="color:var(--red)">*</span></label>
                            <div class="db-field-inner is-select">
                                <i class="bi bi-diagram-3 db-ficon"></i>
                                <select name="section" id="unitSelect" required <?= !$hasPpmp ? 'disabled' : '' ?>>
                                    <option value="">— Select Unit —</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="db-row">
                        <div class="db-field">
                            <label>Responsibility Center Code</label>
                            <div class="db-field-inner">
                                <i class="bi bi-upc db-ficon"></i>
                                <input type="text" name="responsibility_center_code" placeholder="Center Code" <?= !$hasPpmp ? 'disabled' : '' ?>>
                            </div>
                        </div>
                        <div class="db-field">
                            <label>Date</label>
                            <div class="db-field-inner">
                                <i class="bi bi-calendar db-ficon"></i>
                                <input type="date" name="date" <?= !$hasPpmp ? 'disabled' : '' ?>>
                            </div>
                        </div>
                    </div>
                    <!-- Expected Delivery Date -->
                    <div class="db-row">
                        <div class="db-field">
                            <label>Expected Delivery Date <span style="color:var(--red)">*</span></label>
                            <div class="db-field-inner">
                                <i class="bi bi-calendar-check db-ficon"></i>
                                <input type="date" name="expected_delivery_date" id="expectedDeliveryDate" <?= !$hasPpmp ? 'disabled' : 'required' ?>>
                            </div>
                            <div class="pr-field-error" id="deliveryDateError" style="display:none;color:var(--red);font-size:.8rem;margin-top:.25rem;">Delivery date cannot be in the past.</div>
                        </div>

                        <!-- Recommended Mode of Procurement -->
                        <div class="db-field">
                            <label>Recommended Mode of Procurement <span style="color:var(--red)">*</span></label>
                            <div class="db-field-inner is-select">
                                <i class="bi bi-clipboard-check db-ficon"></i>
                                <select name="procurement_mode" <?= !$hasPpmp ? 'disabled' : 'required' ?>>
                                    <option value="">— Select Mode —</option>
                                    <option value="Section 27 – Competitive Bidding">Section 27 – Competitive Bidding</option>
                                    <option value="Section 28 – Limited Source Bidding">Section 28 – Limited Source Bidding</option>
                                    <option value="Section 29 – Competitive Dialogue">Section 29 – Competitive Dialogue</option>
                                    <option value="Section 30 – Unsolicited Offer with Bid Matching">Section 30 – Unsolicited Offer with Bid Matching</option>
                                    <option value="Section 31 – Direct Contracting">Section 31 – Direct Contracting</option>
                                    <option value="Section 32 – Direct Acquisition">Section 32 – Direct Acquisition</option>
                                    <option value="Section 33 – Repeat Order">Section 33 – Repeat Order</option>
                                    <option value="Section 34 – Small Value Procurement">Section 34 – Small Value Procurement</option>
                                    <option value="Section 35 – Negotiated Procurement">Section 35 – Negotiated Procurement</option>
                                    <option value="Section 36 – Direct Sales">Section 36 – Direct Sales</option>
                                    <option value="Section 37 – Direct Procurement for Science, Technology and Innovation">Section 37 – Direct Procurement for Science, Technology and Innovation</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="db-field">
                        <label>Purpose</label>
                        <div class="db-field-inner">
                            <textarea name="purpose" rows="3" style="width:100%;padding:.7rem 1rem;border:1.5px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--text);background:var(--white);outline:none;resize:vertical;" placeholder="State the purpose of this request" <?= !$hasPpmp ? 'disabled' : '' ?>></textarea>
                        </div>
                    </div>

                    <div class="db-row">
                        <div class="db-field">
                            <label>Requested By</label>
                            <div class="db-field-inner">
                                <i class="bi bi-person db-ficon"></i>
                                <input type="text" name="requested_by" value="<?= htmlspecialchars($_SESSION['auth_user']['name'] ?? '') ?>" placeholder="Requested by" <?= !$hasPpmp ? 'disabled' : '' ?>>
                            </div>
                        </div>
                        <div class="db-field">
                            <label>Designation</label>
                            <div class="db-field-inner">
                                <i class="bi bi-briefcase db-ficon"></i>
                                <input type="text" name="designation" placeholder="Designation" <?= !$hasPpmp ? 'disabled' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="db-field" style="margin-top:1rem;">
                        <label>Items</label>
                        <div style="overflow-x:auto;">
                            <table class="db-table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Stock / Property No.</th>
                                        <th>Unit</th>
                                        <th>Item Description</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost (PHP)</th>
                                        <th>Total Cost (PHP)</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr>
                                        <td><input type="text" name="stock_no[]" class="pr-input" placeholder="Stock/Property No." <?= !$hasPpmp ? 'disabled' : '' ?>></td>
                                        <td><input type="text" name="unit[]" class="pr-input" placeholder="Unit" <?= !$hasPpmp ? 'disabled' : '' ?>></td>
                                        <td><textarea name="item_description[]" class="pr-input pr-desc-ta" rows="3" placeholder="Description" <?= !$hasPpmp ? 'disabled' : '' ?>></textarea></td>
                                        <td><input type="number" name="quantity[]" class="pr-input qty" min="0" step="any" placeholder="0" <?= !$hasPpmp ? 'disabled' : '' ?>></td>
                                        <td><input type="number" name="unit_cost[]" class="pr-input ucost" min="0" step="0.01" placeholder="0.00" <?= !$hasPpmp ? 'disabled' : '' ?>></td>
                                        <td><input type="number" name="total_cost[]" class="pr-input tcost" readonly placeholder="0.00" style="background:var(--bg);"></td>
                                        <td><button type="button" class="btn-remove-row" title="Remove row" <?= !$hasPpmp ? 'disabled' : '' ?>><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" id="addRowBtn" <?= !$hasPpmp ? 'disabled' : '' ?> style="margin-top:.75rem;display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1rem;border-radius:8px;border:1.5px dashed var(--border);background:var(--bg);color:var(--navy-mid);font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:600;cursor:<?= !$hasPpmp ? 'not-allowed' : 'pointer' ?>;">
                            <i class="bi bi-plus-circle"></i> Add Row
                        </button>
                    </div>

                    <div style="text-align:center;margin-top:1.5rem;">
                        <?php if (!$hasPpmp): ?>
                        <div title="You need an approved PPMP to submit a Purchase Request" style="display:inline-block;">
                            <button type="submit" name="PRSUB" id="prSubmitBtn" class="db-btn-submit" disabled
                                style="opacity:.5;cursor:not-allowed;">
                                <i class="bi bi-lock-fill"></i> Submit Request
                            </button>
                        </div>
                        <?php else: ?>
                        <button type="submit" name="PRSUB" id="prSubmitBtn" class="db-btn-submit">
                            <i class="bi bi-send"></i> Submit Request
                        </button>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </div>
    </main>
</div>

<link rel="stylesheet" href="assets/css/purchase_request.css">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/purchase_request.js"></script>
