<?php 

include '../config/localdb.php';
include 'functions/purchasefunctions.php';
include 'functions/authorization.php';
include 'includes/sidebar.php'; 

$prs = getAllPR($con);
$prIds = array_column($prs, 'id');
$prNumbers = array_column($prs, 'pr_number');

$usePrNoItems = tableExists($con, 'pr_items') && tableHasColumn($con, 'pr_items', 'pr_no');
$itemsByPrNo = $usePrNoItems ? getPRItemsByPRNumbers($con, $prNumbers) : [];
$summaryByPrNo = $usePrNoItems ? getPRItemSummaryByPRNumbers($con, $prNumbers) : [];

$itemsByPrId = getPRItemsByPRIds($con, $prIds);
$summaryByPrId = getPRItemSummaryByPRIds($con, $prIds);

$prOfficeUnits = [
    'Curriculumn Implementation Division' => [
        'LR',
        'EPS',
        'PSDS',
        'ALS',
    ],
    'School Governance and operation Division' => [
        'Planning & Research',
        'HRD',
        'SMNE',
        'SMME',
        'HSNU',
        'FACILITIES',
        'EDUCATION FACILITIES SECTION',
        'YFD',
        'PRIVATE',
    ],
    'Office of the School Division Superintendent' => [
        'ICT Services',
        'Admin services',
        'Cashier',
        'Personnel',
        'Records',
        'Procurement',
        'Supply and Property',
        'Legal services',
        'Finance Services',
        'Budget',
        'Accounting',
    ],
];
?>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/purchase_request.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="pr-page">

    <!-- Header -->
    <div class="pr-header">
        <div>
            <h3 class="pr-header-title"><i class="bi bi-bag-check me-2" style="color:var(--pr-accent)"></i>Purchase Requests</h3>
            <div class="pr-header-sub">Manage purchase requests and generate documents.</div>
        </div>
        <button class="btn-pr-add" id="btnOpenAddPR" data-bs-toggle="modal" data-bs-target="#addPRModal">
            <i class="bi bi-plus-lg"></i> Add Purchase Request
        </button>
    </div>

    <!-- Table Card -->
    <div class="pr-card">
        <div class="pr-table-wrap">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th>PR No.</th>
                        <th>End-User</th>
                        <th>Office</th>
                        <th class="pr-col-right">Items</th>
                        <th class="pr-col-right">Total Estimated Cost (PHP)</th>
                        <th class="pr-col-actions" style="text-align:center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(!empty($prs)) : ?>
                    <?php foreach($prs as $pr): ?>
                        <?php
                            $prId = (int)($pr['id'] ?? 0);

                            $prNo = $pr['pr_number'] ?? ('PR #' . (string)$pr['id']);
                            $endUse    = $pr['requested_by'] ?? '-';
                            $office    = $pr['office'] ?? '-';
                            $items = $usePrNoItems ? ($itemsByPrNo[(string)$prNo] ?? []) : ($itemsByPrId[$prId] ?? []);

                            $itemCount = 0;
                            $totalEstimated = 0.0;

                            if ($usePrNoItems) {
                                $sum = $summaryByPrNo[(string)$prNo] ?? null;
                                $itemCount = (int)($sum['item_count'] ?? 0);
                                $totalEstimated = (float)($sum['total_estimated_cost'] ?? 0);
                            } else {
                                $sum = $summaryByPrId[$prId] ?? null;
                                $itemCount = (int)($sum['item_count'] ?? 0);
                                $totalEstimated = (float)($sum['total_estimated_cost'] ?? 0);
                            }

                            if ($itemCount <= 0 && !empty($items)) {
                                $itemCount = count($items);
                            }
                            if ($totalEstimated <= 0 && !empty($items)) {
                                foreach ($items as $it) {
                                    $rowCost = $it['estimated_cost'] ?? ($it['total_cost'] ?? ($it['amount'] ?? null));
                                    if (!is_numeric($rowCost)) {
                                        $q = $it['quantity'] ?? null;
                                        $uc = $it['unit_cost'] ?? null;
                                        if (is_numeric($q) && is_numeric($uc)) {
                                            $rowCost = (float)$q * (float)$uc;
                                        }
                                    }
                                    if (is_numeric($rowCost)) $totalEstimated += (float)$rowCost;
                                }
                            }

                            $totalEstimatedDisplay = number_format((float)$totalEstimated, 2);
                        ?>
                        <tr>
                            <td class="pr-col-mono"><?= htmlspecialchars((string)$prNo) ?></td>
                            <td><div class="pr-text-wrap"><?= htmlspecialchars((string)$endUse) ?></div></td>
                            <td><?= htmlspecialchars((string)$office) ?></td>
                            <td class="pr-col-right pr-col-mono"><?= htmlspecialchars((string)$itemCount) ?></td>
                            <td class="pr-col-right pr-col-mono">&#8369;<?= htmlspecialchars((string)$totalEstimatedDisplay) ?></td>
                            <td class="pr-col-actions">
                                <div class="pr-actions">
                                    <button type="button" class="pr-btn pr-btn-view" data-bs-toggle="modal" data-bs-target="#viewPRModal<?= $pr['id'] ?>" title="Preview">
                                        <i class="bi bi-eye"></i>
                                        <span class="pr-btn-label"></span>
                                    </button>
                                    <button type="button" class="pr-btn pr-btn-approve btn-approve-pr" data-id="<?= $pr['id'] ?>" title="Approve" style="background:none;border:1.5px solid #d1fae5;color:#16a34a;border-radius:8px;padding:.3rem .55rem;cursor:pointer;transition:.2s;" <?= ($pr['status'] === 'Approved') ? 'disabled style="opacity:.4;cursor:not-allowed;"' : '' ?>>
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <button type="button" class="pr-btn pr-btn-edit" data-bs-toggle="modal" data-bs-target="#editPRModal<?= $pr['id'] ?>" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                        <span class="pr-btn-label"></span>
                                    </button>
                                    <button type="button" class="pr-btn pr-btn-generate btn-generate-pr" data-id="<?= $pr['id'] ?>" title="Generate Word">
                                        <i class="bi bi-file-earmark-word"></i>
                                        <span class="pr-btn-label"></span>
                                    </button>
                                    <button type="button" class="pr-btn pr-btn-delete btn-delete" data-id="<?= $pr['id'] ?>" title="Delete">
                                        <i class="bi bi-trash3"></i>
                                        <span class="pr-btn-label"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="pr-empty">
                                <i class="bi bi-inbox"></i>
                                <span>No purchase requests found.</span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== EDIT + VIEW MODALS ===================== -->
<?php if(!empty($prs)) : ?>
    <?php foreach($prs as $pr): ?>
        <?php
            $prId = (int)($pr['id'] ?? 0);
            $prNoForItems = $pr['pr_number'] ?? ('PR #' . (string)$prId);
            $editItems = $usePrNoItems ? ($itemsByPrNo[(string)$prNoForItems] ?? []) : ($itemsByPrId[$prId] ?? []);

            $editPoNo = $pr['po_number'] ?? ($pr['po_no'] ?? ($pr['purchase_order'] ?? ''));
            if ($editPoNo === '') {
                $r = $pr['remarks'] ?? '';
                if (is_string($r) && $r !== '') {
                    if (preg_match('/PO\\s*Number:\\s*([^|]+)/i', $r, $m)) $editPoNo = trim($m[1]);
                }
            }
            $editEndUse      = $pr['end_use'] ?? ($pr['purpose'] ?? ($pr['remarks'] ?? ''));
            $editFundSource  = $pr['fund_source'] ?? ($pr['fund_cluster'] ?? '');
            $editSupplier    = $pr['supplier'] ?? '';
            if ($editSupplier === '') {
                $r = $pr['remarks'] ?? '';
                if (is_string($r) && $r !== '') {
                    if (preg_match('/Supplier:\\s*([^|]+)/i', $r, $m)) $editSupplier = trim($m[1]);
                }
            }
            $editApprovedBudget = $pr['approved_budget'] ?? ($pr['abc'] ?? '');
            $editTotalAmount    = $pr['total_amount'] ?? '';

            $statusBadgeClass = match($pr['status'] ?? '') {
                'Approved'     => 'pr-badge-approved',
                'Rejected'     => 'pr-badge-rejected',
                'PO Generated' => 'pr-badge-po',
                default        => 'pr-badge-pending'
            };
            $statusIcon = match($pr['status'] ?? '') {
                'Approved'     => 'bi-check-circle-fill',
                'Rejected'     => 'bi-x-circle-fill',
                'PO Generated' => 'bi-archive-fill',
                default        => 'bi-clock-fill'
            };
        ?>


        <!-- EDIT MODAL -->
        <div class="modal fade" id="editPRModal<?= $pr['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-xl" style="max-height:95vh;margin-top:2.5vh;margin-bottom:2.5vh;">
                <div class="modal-content" style="max-height:95vh;display:flex;flex-direction:column;">
                    <form action="functions/pr_actions.php" method="POST" style="display:flex;flex-direction:column;flex:1;min-height:0;">
                        <div class="modal-header" style="flex-shrink:0;">
                            <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Purchase Request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="flex:1;overflow-y:auto;min-height:0;">
                            <input type="hidden" name="update_id" value="<?= $pr['id'] ?>">

                            <!-- Basic Info -->
                            <div class="pr-modal-section">
                                <div class="pr-modal-section-title"><i class="bi bi-info-circle"></i> Basic Information</div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">PR Number</label>
                                        <input type="text" name="pr_number" class="form-control" value="<?= htmlspecialchars($pr['pr_number'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">End-User / Requested By</label>
                                        <input type="text" name="requested_by" class="form-control" value="<?= htmlspecialchars($pr['requested_by'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Designation</label>
                                        <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($pr['designation'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Office</label>
                                        <select
                                            name="office"
                                            class="form-select pr-office-select"
                                            data-unit-target="editSection<?= $pr['id'] ?>"
                                        >
                                            <option value="">Select office</option>
                                            <?php foreach ($prOfficeUnits as $officeName => $units): ?>
                                            <option value="<?= htmlspecialchars($officeName) ?>" <?= (($pr['office'] ?? '') === $officeName) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($officeName) ?>
                                            </option>
                                            <?php endforeach; ?>
                                            <?php if (!isset($prOfficeUnits[$pr['office'] ?? '']) && ($pr['office'] ?? '') !== ''): ?>
                                            <option value="<?= htmlspecialchars($pr['office']) ?>" selected><?= htmlspecialchars($pr['office']) ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Section / Unit</label>
                                        <select
                                            name="section"
                                            id="editSection<?= $pr['id'] ?>"
                                            class="form-select pr-unit-select"
                                            data-selected="<?= htmlspecialchars($pr['section'] ?? '') ?>"
                                        >
                                            <option value="">Select office first</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Info -->
                            <?php
                                $editPoNo = $pr['po_number'] ?? ($pr['po_no'] ?? ($pr['purchase_order'] ?? ''));
                                if ($editPoNo === '') {
                                    if (preg_match('/PO\s*Number:\s*([^|]+)/i', $pr['remarks'] ?? '', $m)) $editPoNo = trim($m[1] ?? '');
                                }
                                $editSupplier   = $pr['supplier'] ?? '';
                                if ($editSupplier === '') {
                                    if (preg_match('/Supplier:\s*([^|]+)/i', $pr['remarks'] ?? '', $m)) $editSupplier = trim($m[1] ?? '');
                                }
                                $editFundSource = $pr['fund_source'] ?? ($pr['fund_cluster'] ?? '');
                                $editEndUse     = $pr['end_use'] ?? ($pr['purpose'] ?? '');
                                $editApprovedBudget = $pr['approved_budget'] ?? ($pr['abc'] ?? 0);
                                $editTotalAmount    = $pr['total_amount'] ?? 0;
                                $editItems = $usePrNoItems ? ($itemsByPrNo[(string)($pr['pr_number'] ?? '')] ?? []) : ($itemsByPrId[$pr['id']] ?? []);
                            ?>
                            <div class="pr-modal-section">
                                <div class="pr-modal-section-title"><i class="bi bi-wallet2"></i> Financial Details</div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">P.R Number</label>
                                        <input type="text" name="po_number" class="form-control" value="<?= htmlspecialchars($editPoNo) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Fund Source</label>
                                        <input type="text" name="fund_source" class="form-control" value="<?= htmlspecialchars($editFundSource) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Approved Budget (ABC)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">&#8369;</span>
                                            <input type="number" step="0.01" min="0" name="approved_budget" class="form-control" value="<?= htmlspecialchars((float)$editApprovedBudget) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Total Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">&#8369;</span>
                                            <input type="number" step="0.01" min="0" name="total_amount" class="form-control pr-total-amount" value="<?= htmlspecialchars(number_format((float)$editTotalAmount, 2)) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Purpose</label>
                                        <textarea name="purpose" rows="2" class="form-control" placeholder="State the purpose of this request"><?= htmlspecialchars($pr['purpose'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Items -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;color:var(--pr-muted)">
                                    <i class="bi bi-list-ul me-1"></i>Items
                                </div>
                                <div class="pr-total-bar">
                                    <span style="color:var(--pr-muted);font-size:.8rem">Grand Total:</span>
                                    <span class="pr-total-val">&#8369;<span class="pr-grand-total"><?= number_format((float)$editTotalAmount, 2) ?></span></span>
                                </div>
                            </div>

                            <div class="pr-items-list" style="padding-right:4px">
                                <?php if(!empty($editItems)) : ?>
                                    <?php foreach($editItems as $item): ?>
                                        <?php
                                            $stockVal    = $item['stock_property_no'] ?? '';
                                            $unitVal     = $item['unit'] ?? '';
                                            $descVal     = $item['description'] ?? ($item['item_description'] ?? '');
                                            $qtyVal      = $item['quantity'] ?? '';
                                            $unitCostVal = $item['unit_cost'] ?? ($item['estimated_cost'] ?? '');
                                            $totalCostVal = $item['total_cost'] ?? ($item['amount'] ?? '');
                                        ?>
                                        <div class="pr-item">
                                            <div class="row g-2">
                                                <div class="col-md-2 col-6">
                                                    <label class="form-label">Stock/Property No.</label>
                                                    <input type="text" name="stock_property_no[]" class="form-control pr-stock" value="<?= htmlspecialchars((string)$stockVal) ?>">
                                                </div>
                                                <div class="col-md-1 col-6">
                                                    <label class="form-label">Unit</label>
                                                    <input type="text" name="unit[]" class="form-control pr-unit" value="<?= htmlspecialchars((string)$unitVal) ?>">
                                                </div>
                                                <div class="col-md-3 col-12">
                                                    <label class="form-label">Item Description</label>
                                                    <textarea name="description[]" rows="3" class="form-control pr-desc"><?= htmlspecialchars((string)$descVal) ?></textarea>
                                                </div>
                                                <div class="col-md-2 col-6">
                                                    <label class="form-label">Quantity</label>
                                                    <input type="number" step="0.01" min="0" name="quantity[]" class="form-control pr-qty" value="<?= htmlspecialchars((string)$qtyVal) ?>">
                                                </div>
                                                <div class="col-md-2 col-6">
                                                    <label class="form-label">Unit Cost</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">&#8369;</span>
                                                        <input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control pr-unit-cost" value="<?= htmlspecialchars((string)$unitCostVal) ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-2 col-6">
                                                    <label class="form-label">Total Cost</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">&#8369;</span>
                                                        <input type="text" name="amount[]" class="form-control pr-amount" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-12 d-flex justify-content-end">
                                                    <button type="button" class="pr-btn pr-btn-delete pr-remove-row pr-btn-sm" title="Remove item">
                                                        <i class="bi bi-dash-circle me-1"></i>Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="pr-item">
                                        <div class="row g-2">
                                            <div class="col-md-2 col-6"><label class="form-label">Stock/Property No.</label><input type="text" name="stock_property_no[]" class="form-control pr-stock"></div>
                                            <div class="col-md-1 col-6"><label class="form-label">Unit</label><input type="text" name="unit[]" class="form-control pr-unit"></div>
                                            <div class="col-md-3 col-12"><label class="form-label">Item Description</label><textarea name="description[]" rows="3" class="form-control pr-desc"></textarea></div>
                                            <div class="col-md-2 col-6"><label class="form-label">Quantity</label><input type="number" step="0.01" min="0" name="quantity[]" class="form-control pr-qty"></div>
                                            <div class="col-md-2 col-6"><label class="form-label">Unit Cost</label><input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control pr-unit-cost"></div>
                                            <div class="col-md-2 col-6"><label class="form-label">Total Cost</label><input type="text" name="amount[]" class="form-control pr-amount" readonly></div>
                                            <div class="col-12 d-flex justify-content-end"><button type="button" class="pr-btn pr-btn-delete pr-remove-row pr-btn-sm">Remove</button></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="button" class="btn-add-item pr-add-item-row mt-2"><i class="bi bi-plus-circle"></i> Add Item</button>

                            <!-- Procurement Details -->
                            <div class="pr-modal-section mt-3">
                                <div class="pr-modal-section-title"><i class="bi bi-gear"></i> Procurement Details</div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Expected Delivery Date</label>
                                        <input type="date" name="expected_delivery_date" class="form-control admin-delivery-date" value="<?= htmlspecialchars($pr['expected_delivery_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Recommended Mode of Procurement</label>
                                        <select name="procurement_mode" class="form-select">
                                            <option value="">— Select Mode —</option>
                                            <?php
                                            $procModes = [
                                                'Section 27 – Competitive Bidding',
                                                'Section 28 – Limited Source Bidding',
                                                'Section 29 – Competitive Dialogue',
                                                'Section 30 – Unsolicited Offer with Bid Matching',
                                                'Section 31 – Direct Contracting',
                                                'Section 32 – Direct Acquisition',
                                                'Section 33 – Repeat Order',
                                                'Section 34 – Small Value Procurement',
                                                'Section 35 – Negotiated Procurement',
                                                'Section 36 – Direct Sales',
                                                'Section 37 – Direct Procurement for Science, Technology and Innovation',
                                            ];
                                            foreach ($procModes as $pm):
                                                $sel = ($pr['procurement_mode'] ?? '') === $pm ? 'selected' : '';
                                            ?>
                                            <option value="<?= htmlspecialchars($pm) ?>" <?= $sel ?>><?= htmlspecialchars($pm) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">PPMP Attachments Required</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php
                                            $savedAtts = json_decode($pr['ppmp_attachments_required'] ?? '[]', true) ?: [];
                                            foreach(['PPMP Form','APP','Budget Utilization Report','Certificate of Availability of Funds','Technical Specifications'] as $att):
                                                $chk = in_array($att, $savedAtts) ? 'checked' : '';
                                            ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ppmp_attachments_required[]" value="<?= htmlspecialchars($att) ?>" <?= $chk ?> id="edit_att_<?= $pr['id'] ?>_<?= md5($att) ?>">
                                                <label class="form-check-label" for="edit_att_<?= $pr['id'] ?>_<?= md5($att) ?>"><?= htmlspecialchars($att) ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input admin-pr-lock" type="checkbox" name="is_pr_locked" value="1" id="editIsPrLocked<?= $pr['id'] ?>" <?= !empty($pr['is_pr_locked']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="editIsPrLocked<?= $pr['id'] ?>"><i class="bi bi-lock"></i> Lock PR (Requires PPMP)</label>
                                        </div>
                                        <div class="admin-lock-warning alert alert-warning py-1 px-2 mt-1" style="<?= !empty($pr['is_pr_locked']) ? '' : 'display:none;' ?>font-size:.82rem;">
                                            <i class="bi bi-exclamation-triangle-fill"></i> PPMP is required before proceeding
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /.modal-body -->

                        <div class="modal-footer gap-2" style="flex-shrink:0;">
                            <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-modal-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- END EDIT MODAL -->


        <!-- VIEW MODAL -->
        <div class="modal fade" id="viewPRModal<?= $pr['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered" style="max-height:95vh;margin-top:2.5vh;margin-bottom:2.5vh;">
                <div class="modal-content" style="max-height:95vh;display:flex;flex-direction:column;">
                    <div class="modal-header" style="flex-shrink:0;">
                        <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Purchase Request Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="flex:1;overflow-y:auto;min-height:0;">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span style="font-family:'DM Mono',monospace;font-size:1rem;font-weight:700;color:var(--pr-accent)"><?= htmlspecialchars($pr['pr_number'] ?? '-') ?></span>
                            <span class="pr-badge <?= $statusBadgeClass ?>">
                                <i class="bi <?= $statusIcon ?>"></i>
                                <?= htmlspecialchars($pr['status'] ?? 'Pending') ?>
                            </span>
                        </div>

                        <div class="pr-info-grid mb-3">
                            <div class="pr-info-item">
                                <span class="pr-info-label">Requested By</span>
                                <span class="pr-info-value"><?= htmlspecialchars($pr['requested_by'] ?? '-') ?></span>
                            </div>
                            <div class="pr-info-item">
                                <span class="pr-info-label">Office</span>
                                <span class="pr-info-value"><?= htmlspecialchars($pr['office'] ?? '-') ?></span>
                            </div>
                            <div class="pr-info-item">
                                <span class="pr-info-label">Total Amount</span>
                                <span class="pr-info-value mono" style="color:var(--pr-accent)">&#8369;<?= number_format($pr['total_amount'] ?? 0, 2) ?></span>
                            </div>
                            <div class="pr-info-item">
                                <span class="pr-info-label">Date Submitted</span>
                                <span class="pr-info-value mono"><?= date('M d, Y', strtotime($pr['created_at'])) ?></span>
                            </div>
                        </div>

                        <?php if(!empty($pr['remarks'])) : ?>
                        <div style="background:#f8fafc;border:1px solid var(--pr-border);border-radius:8px;padding:10px 12px">
                            <div class="pr-info-label mb-1">Remarks</div>
                            <div style="font-size:.85rem;color:var(--pr-text);line-height:1.5"><?= htmlspecialchars($pr['remarks']) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php
                            $viewItems = $usePrNoItems ? ($itemsByPrNo[(string)$prNoForItems] ?? []) : ($itemsByPrId[$prId] ?? []);
                        ?>
                        <div class="mt-3" style="background:#fff;border:1px solid var(--pr-border);border-radius:10px;overflow:hidden">
                            <div style="padding:10px 12px;background:#f8fafc;border-bottom:1px solid var(--pr-border);font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--pr-muted)">
                                Items
                            </div>
                            <div class="table-responsive" style="padding:10px 12px">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr style="font-size:.8rem">
                                            <th>Stock/Property No.</th>
                                            <th>Unit</th>
                                            <th>Item Description</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Unit Cost</th>
                                            <th class="text-end">Total Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size:.85rem">
                                        <?php if(!empty($viewItems)) : ?>
                                            <?php foreach($viewItems as $item): ?>
                                                <?php
                                                    $stockNo = $item['stock_property_no'] ?? '-';
                                                    $unit    = $item['unit'] ?? '-';
                                                    $desc    = $item['description'] ?? ($item['item_description'] ?? '-');
                                                    $qty     = $item['quantity'] ?? null;
                                                    $uc      = $item['unit_cost'] ?? null;

                                                    $rowCost = $item['total_cost'] ?? ($item['amount'] ?? ($item['estimated_cost'] ?? null));
                                                    if (!is_numeric($rowCost) && is_numeric($qty) && is_numeric($uc)) {
                                                        $rowCost = (float)$qty * (float)$uc;
                                                    }
                                                    $ucDisplay   = is_numeric($uc) ? number_format((float)$uc, 2) : '-';
                                                    $rowCostDisplay = is_numeric($rowCost) ? number_format((float)$rowCost, 2) : '-';
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string)$stockNo) ?></td>
                                                    <td><?= htmlspecialchars((string)$unit) ?></td>
                                                    <td><div class="pr-text-wrap" style="white-space:pre-line"><?= nl2br(htmlspecialchars((string)$desc)) ?></div></td>
                                                    <td class="text-end"><?= is_numeric($qty) ? htmlspecialchars((string)$qty) : '-' ?></td>
                                                    <td class="text-end pr-col-mono">&#8369;<?= htmlspecialchars($ucDisplay) ?></td>
                                                    <td class="text-end pr-col-mono">&#8369;<?= htmlspecialchars($rowCostDisplay) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center" style="color:var(--pr-muted)">
                                                    No items found for this PR.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- /.modal-body -->
                    <div class="modal-footer gap-2" style="flex-shrink:0;">
                        <a href="functions/generate_pr.php?id=<?= $pr['id'] ?>" class="btn-modal-success" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                            <i class="bi bi-file-earmark-word"></i> Generate Word
                        </a>
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- END VIEW MODAL -->

    <?php endforeach; ?>
<?php endif; ?>


<!-- ===================== ADD PR MODAL ===================== -->
<div class="modal fade" id="addPRModal" tabindex="-1" aria-labelledby="addPRModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-height:95vh;margin-top:2.5vh;margin-bottom:2.5vh;">
        <div class="modal-content" style="max-height:95vh;display:flex;flex-direction:column;">
            <form action="functions/pr_actions.php" method="POST" style="display:flex;flex-direction:column;flex:1;min-height:0;">
                <div class="modal-header" style="flex-shrink:0;">
                    <h5 class="modal-title" id="addPRModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Purchase Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="flex:1;overflow-y:auto;min-height:0;">
                    <!-- Basic Info -->
                    <div class="pr-modal-section">
                        <div class="pr-modal-section-title"><i class="bi bi-building"></i> Request Details</div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">PR No.</label>
                                <input type="text" name="pr_number" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Office <span style="color:var(--pr-danger)">*</span></label>
                                <select
                                    name="office"
                                    class="form-select pr-office-select"
                                    data-unit-target="addPrSection"
                                >
                                    <option value="">Select office</option>
                                    <?php foreach ($prOfficeUnits as $officeName => $units): ?>
                                    <option value="<?= htmlspecialchars($officeName) ?>"><?= htmlspecialchars($officeName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Section / Unit</label>
                                <select
                                    name="section"
                                    id="addPrSection"
                                    class="form-select pr-unit-select"
                                >
                                    <option value="">Select office first</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Responsibility Center Code</label>
                                <input type="text" name="responsibility_center_code" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fund Source</label>
                                <input type="text" name="fund_source" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Approved Budget (ABC)</label>
                                <div class="input-group">
                                    <span class="input-group-text">&#8369;</span>
                                    <input type="number" step="0.01" min="0" name="approved_budget" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Purpose</label>
                                <textarea name="purpose" rows="2" class="form-control" placeholder="State the purpose of this request"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Requested By</label>
                                <input type="text" name="requested_by" class="form-control" value="<?= htmlspecialchars($_SESSION['auth_user']['name'] ?? $_SESSION['auth_user']['username'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Designation</label>
                                <input type="text" name="designation" class="form-control" placeholder="e.g. Principal, Teacher I">
                            </div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--pr-muted)"><i class="bi bi-list-ul me-1"></i>Items</div>
                        <div class="pr-total-bar">
                            <span style="color:var(--pr-muted);font-size:.8rem">Grand Total:</span>
                            <span class="pr-total-val">&#8369;<span id="prGrandTotal">0.00</span></span>
                        </div>
                    </div>

                    <div class="pr-items-list" id="prItemsList" style="padding-right:4px">
                        <div class="pr-item">
                            <div class="row g-2">
                                <div class="col-md-2 col-6">
                                    <label class="form-label">Stock/Property No.</label>
                                    <input type="text" name="stock_property_no[]" class="form-control pr-stock">
                                </div>
                                <div class="col-md-1 col-6">
                                    <label class="form-label">Unit</label>
                                    <input type="text" name="unit[]" class="form-control pr-unit">
                                </div>
                                <div class="col-md-3 col-12">
                                    <label class="form-label">Item Description</label>
                                    <textarea name="description[]" rows="3" class="form-control pr-desc" placeholder="e.g. PROCUREMENT OF SUPPLEMENTARY LEARNING RESOURCES&#10;Size: A4&#10;Piece: 500"></textarea>
                                </div>
                                <div class="col-md-2 col-6">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" step="0.01" min="0" name="quantity[]" class="form-control pr-qty">
                                </div>
                                <div class="col-md-2 col-6">
                                    <label class="form-label">Unit Cost</label>
                                    <div class="input-group">
                                        <span class="input-group-text">&#8369;</span>
                                        <input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control pr-unit-cost">
                                    </div>
                                </div>
                                <div class="col-md-2 col-6">
                                    <label class="form-label">Total Cost</label>
                                    <div class="input-group">
                                        <span class="input-group-text">&#8369;</span>
                                        <input type="text" name="amount[]" class="form-control pr-amount" readonly>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="button" class="pr-btn pr-btn-delete pr-remove-row pr-btn-sm">
                                        <i class="bi bi-dash-circle me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden total_amount auto-filled by JS -->
                    <input type="hidden" name="total_amount" id="addTotalAmount" value="0">
                </div><!-- /.modal-body -->
                <div class="modal-footer gap-2" style="flex-shrink:0;">
                    <button type="button" class="btn-modal-primary" id="addPRItemRow">
                        <i class="bi bi-plus-circle me-1"></i>Add Item
                    </button>
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_pr" class="btn-modal-success">
                        <i class="bi bi-save me-1"></i>Save Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<?php showAlert(); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const prOfficeUnits = <?= json_encode($prOfficeUnits) ?>;

/* -- Helpers -- */
function formatMoney(value) {
    const num = Number(value);
    if (!Number.isFinite(num)) return '0.00';
    return num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function initTooltips(scope = document) {
    if (!window.bootstrap?.Tooltip) return;
    scope.querySelectorAll('.pr-btn[title]').forEach(el => {
        if (bootstrap.Tooltip.getInstance(el)) return;
        new bootstrap.Tooltip(el);
    });
}

function populatePrUnitSelect(officeSelect) {
    const targetId = officeSelect?.dataset?.unitTarget;
    const unitSelect = targetId ? document.getElementById(targetId) : null;
    if (!unitSelect) return;

    const officeName = officeSelect.value;
    const units = prOfficeUnits[officeName] || [];
    const selectedValue = unitSelect.dataset.selected || '';

    unitSelect.innerHTML = '';

    if (!units.length) {
        unitSelect.disabled = true;
        unitSelect.append(new Option('Select office first', '', true, true));
        return;
    }

    unitSelect.disabled = false;
    unitSelect.append(new Option('Select section / unit', '', selectedValue === '', selectedValue === ''));

    units.forEach(unit => {
        const option = new Option(unit, unit, selectedValue === unit, selectedValue === unit);
        unitSelect.append(option);
    });

    if (selectedValue && !units.includes(selectedValue)) {
        unitSelect.append(new Option(selectedValue, selectedValue, true, true));
    }
}

function newItemHTML() {
    return `<div class="pr-item">
        <div class="row g-2">
            <div class="col-md-2 col-6">
                <label class="form-label">Stock/Property No.</label>
                <input type="text" name="stock_property_no[]" class="form-control pr-stock">
            </div>
            <div class="col-md-1 col-6">
                <label class="form-label">Unit</label>
                <input type="text" name="unit[]" class="form-control pr-unit">
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label">Item Description</label>
                <textarea name="description[]" rows="3" class="form-control pr-desc"></textarea>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">Quantity</label>
                <input type="number" step="0.01" min="0" name="quantity[]" class="form-control pr-qty">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">Unit Cost</label>
                <div class="input-group">
                    <span class="input-group-text">&#8369;</span>
                    <input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control pr-unit-cost">
                </div>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">Total Cost</label>
                <div class="input-group">
                    <span class="input-group-text">&#8369;</span>
                    <input type="text" name="amount[]" class="form-control pr-amount" readonly>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="button" class="pr-btn pr-btn-delete pr-remove-row pr-btn-sm">
                    <i class="bi bi-dash-circle me-1"></i>Remove
                </button>
            </div>
        </div>
    </div>`;
}

document.querySelectorAll('.pr-office-select').forEach(select => {
    select.addEventListener('change', () => {
        const targetId = select.dataset.unitTarget;
        const unitSelect = targetId ? document.getElementById(targetId) : null;
        if (unitSelect) unitSelect.dataset.selected = '';
        populatePrUnitSelect(select);
    });
    populatePrUnitSelect(select);
});

function recalcItemsContainer(containerEl) {
    if (!containerEl) return;
    let grandTotal = 0;
    containerEl.querySelectorAll('.pr-item').forEach(item => {
        const qty      = parseFloat(item.querySelector('.pr-qty')?.value) || 0;
        const unitCost = parseFloat(item.querySelector('.pr-unit-cost')?.value) || 0;
        const amount   = qty * unitCost;
        const amountInput = item.querySelector('.pr-amount');
        if (amountInput) amountInput.value = formatMoney(amount);
        grandTotal += amount;
    });

    const scope     = containerEl.closest('.modal') || document;
    const totalEl   = scope.querySelector('.pr-grand-total') || document.getElementById('prGrandTotal');
    if (totalEl) totalEl.textContent = formatMoney(grandTotal);

    const totalInput = scope.querySelector('.pr-total-amount') || document.getElementById('addTotalAmount');
    if (totalInput) totalInput.value = grandTotal.toFixed(2);
}

/* -- Input delegation -- */
document.addEventListener('input', e => {
    if (e.target?.classList?.contains('pr-qty') || e.target?.classList?.contains('pr-unit-cost')) {
        recalcItemsContainer(e.target.closest('.pr-items-list'));
    }
});

/* -- Add Item (Add Modal) -- */
document.getElementById('addPRItemRow')?.addEventListener('click', () => {
    const list = document.getElementById('prItemsList');
    if (!list) return;
    list.insertAdjacentHTML('beforeend', newItemHTML());
    recalcItemsContainer(list);
});

/* -- Add Item (Edit Modals) -- */
document.addEventListener('click', e => {
    const addBtn = e.target?.closest?.('.pr-add-item-row');
    if (!addBtn) return;
    const list = addBtn.closest('.modal')?.querySelector('.pr-items-list');
    if (!list) return;
    list.insertAdjacentHTML('beforeend', newItemHTML());
    recalcItemsContainer(list);
});

/* -- Remove Item -- */
document.addEventListener('click', e => {
    const btn = e.target?.closest?.('.pr-remove-row');
    if (!btn) return;
    const item = btn.closest('.pr-item');
    const list = item?.parentElement;
    if (!item || !list) return;
    if (list.querySelectorAll('.pr-item').length <= 1) {
        item.querySelectorAll('input').forEach(i => i.value = '');
        recalcItemsContainer(list);
        return;
    }
    item.remove();
    recalcItemsContainer(list);
});

/* -- Recalc on modal open -- */
document.addEventListener('shown.bs.modal', e => {
    const list = e.target?.querySelector?.('.pr-items-list');
    if (list) recalcItemsContainer(list);

    initTooltips(e.target);

    const firstField = e.target?.querySelector?.('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
    if (firstField) setTimeout(() => firstField.focus(), 0);
});

/* -- Generate Word button -- */
document.querySelectorAll('.btn-generate-pr').forEach(btn => {
    btn.addEventListener('click', function () {
        const prId = this.dataset.id;
        Swal.fire({
            title: 'Generate Document',
            text: 'Generate Word document for this purchase request?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-file-earmark-word"></i> Generate',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
        }).then(result => {
            if (result.isConfirmed) window.location.href = 'functions/generate_pr.php?id=' + prId;
        });
    });
});

/* -- Delete -- */
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function () {
        const prId = this.dataset.id;
        Swal.fire({
            title: 'Delete Purchase Request?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'functions/pr_actions.php';
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = 'delete_id';
                input.value = prId;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});

/* -- Approve PR -- */
document.querySelectorAll('.btn-approve-pr').forEach(btn => {
    btn.addEventListener('click', function () {
        const prId = this.dataset.id;
        Swal.fire({
            title: 'Approve Purchase Request?',
            text: 'This will mark the PR as Approved.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-check-circle"></i> Approve',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
        }).then(result => {
            if (!result.isConfirmed) return;
            const fd = new FormData();
            fd.append('bacreso_action', 'approve_pr');
            fd.append('id', prId);
            fetch('functions/bacfunction.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.ok) {
                        Swal.fire({ icon: 'success', title: 'Approved!', timer: 1500, showConfirmButton: false });
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Failed', text: res.msg || 'Could not approve.' });
                    }
                });
        });
    });
});

/* -- Status Toggle -- */
document.querySelectorAll('.status-toggle').forEach(button => {
    button.addEventListener('click', function () {
        const prId = this.dataset.id, newStatus = this.dataset.status;
        Swal.fire({
            title: 'Change Status?',
            text: `Set this PR to "${newStatus}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST'; form.action = 'functions/pr_actions.php';
                [['status_id', prId], ['status', newStatus]].forEach(([n, v]) => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = n; i.value = v;
                    form.appendChild(i);
                });
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});

initTooltips(document);

/* -- Admin Delivery Date: enforce min=today -- */
document.querySelectorAll('.admin-delivery-date').forEach(input => {
    input.min = new Date().toISOString().split('T')[0];
    input.addEventListener('change', function () {
        const today = new Date(); today.setHours(0,0,0,0);
        const sel   = new Date(this.value + 'T00:00:00');
        if (sel < today) { this.setCustomValidity('Delivery date cannot be in the past.'); }
        else             { this.setCustomValidity(''); }
    });
});
</script>
