<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/localdb.php';
include 'functions/authorization.php';
include('includes/sidebar.php');
$_swal = $_SESSION['swal'] ?? null;
unset($_SESSION['swal']);


// Ensure is_hidden column exists
$_col_check = mysqli_query($con, "SHOW COLUMNS FROM `tool_sub` LIKE 'is_hidden'");
if ($_col_check && mysqli_num_rows($_col_check) === 0) {
    mysqli_query($con, "ALTER TABLE tool_sub ADD COLUMN is_hidden TINYINT(1) DEFAULT 0");
}
$_update_col_check = mysqli_query($con, "SHOW COLUMNS FROM `tool_sub` LIKE 'allow_user_update'");
if ($_update_col_check && mysqli_num_rows($_update_col_check) === 0) {
    mysqli_query($con, "ALTER TABLE tool_sub ADD COLUMN allow_user_update TINYINT(1) NOT NULL DEFAULT 0 AFTER is_hidden");
}

// FETCH PPMP grouped by office -> unit
$ppmp_query = "SELECT * FROM tool_sub WHERE ppmp_type NOT IN ('APP', 'Annual Procurement Plan') AND (is_hidden = 0 OR is_hidden IS NULL) ORDER BY office ASC, unit ASC, id DESC";
$ppmp_result = mysqli_query($con, $ppmp_query);
$ppmp_by_office = [];
while ($r = mysqli_fetch_assoc($ppmp_result)) {
    $office = $r['office'] ?: 'Unknown';
    $unit   = $r['unit']   ?: 'Unknown';
    $ppmp_by_office[$office][$unit][] = $r;
}

// FETCH APP grouped by office -> unit (never show hidden in APP)
$app_query = "SELECT * FROM tool_sub WHERE ppmp_type NOT IN ('APP', 'Annual Procurement Plan') AND status = 'Approved' AND (is_hidden = 0 OR is_hidden IS NULL) ORDER BY office ASC, unit ASC, id DESC";
$app_result = mysqli_query($con, $app_query);
$app_by_office = [];
while ($r = mysqli_fetch_assoc($app_result)) {
    $office = $r['office'] ?: 'Unknown';
    $unit   = $r['unit']   ?: 'Unknown';
    $app_by_office[$office][$unit][] = $r;
}

$ppmpOfficeCount = count($ppmp_by_office);
$ppmpUnitCount = 0;
$ppmpRecordCount = 0;
foreach ($ppmp_by_office as $unitGroups) {
    $ppmpUnitCount += count($unitGroups);
    foreach ($unitGroups as $rows) {
        $ppmpRecordCount += count($rows);
    }
}

$appOfficeCount = count($app_by_office);
$appRecordCount = 0;
foreach ($app_by_office as $unitGroups) {
    foreach ($unitGroups as $rows) {
        $appRecordCount += count($rows);
    }
}

$planningOfficeUnits = [
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

$procurementModeOptions = [
    'Section 27 Competitive Bidding',
    'Section 28 Limited Source Bidding',
    'Section 29 Competitive Dialogue',
    'Section 30 Unsolicited Offer with Bid Matching',
    'Section 31 Direct Contracting',
    'Section 35 Negotiated Procurement',
    'Section 32 Direct Acquisition',
    'Section 33 Repeat Order',
    'Section 34 Small Value Procurement',
    'Section 36 Direct Sales',
    'Section 37 Direct Procurement for Science, Technology and Innovation',
];
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="assets/css/planning.css">

<div class="planning-page">
    <div class="planning-shell">
        <section class="planning-card">
            <div class="planning-card-head">
                <div class="planning-card-copy">
                    <h2>Planning</h2>
                    <p>Review records by office and unit, then update status or export files as needed.</p>
                </div>
                <div class="planning-tabs" role="tablist" aria-label="Planning sections">
                    <button class="planning-tab-btn active" data-tab="ppmp" type="button">
                        <i class="bi bi-folder2-open"></i>
                        <span>PPMP</span>
                    </button>
                    <button class="planning-tab-btn" data-tab="app" type="button">
                        <i class="bi bi-calendar3"></i>
                        <span>APP</span>
                    </button>
                </div>
            </div>

            <div class="planning-tab-pane active" id="tab-ppmp">
                <div class="planning-toolbar">
                    <div class="planning-toolbar-copy">
                        <h3>Project Procurement Management Plan</h3>
                        <p><?= number_format($ppmpRecordCount) ?> total records across <?= number_format($ppmpOfficeCount) ?> office<?= $ppmpOfficeCount === 1 ? '' : 's' ?>.</p>
                    </div>
                    <div class="planning-toolbar-actions">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPpmpModal" title="Create PPMP">
                            <i class="bi bi-plus-circle me-1"></i> Create PPMP
                        </button>
                    </div>
                </div>
                <div class="planning-content">
                    <?php if (empty($ppmp_by_office)): ?>
                        <div class="alert alert-info planning-empty-state">No records found.</div>
                    <?php else: ?>
                        <div class="accordion planning-accordion" id="ppmpAccordion">
                            <?php $officeIdx = 0; foreach ($ppmp_by_office as $officeName => $unitGroups): ?>
                            <?php $offAccId = 'ppmpOffice' . $officeIdx;
                                  $totalRows = array_sum(array_map('count', $unitGroups)); ?>
                            <div class="accordion-item planning-office-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?= $officeIdx > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $offAccId ?>">
                                        <span class="planning-accordion-title">
                                            <i class="bi bi-building me-2"></i>
                                            <?= htmlspecialchars($officeName) ?>
                                        </span>
                                        <span class="badge rounded-pill text-bg-primary ms-2"><?= $totalRows ?> PPMP<?= $totalRows > 1 ? 's' : '' ?></span>
                                    </button>
                                </h2>
                                <div id="<?= $offAccId ?>" class="accordion-collapse collapse <?= $officeIdx === 0 ? 'show' : '' ?>" data-bs-parent="#ppmpAccordion">
                                    <div class="accordion-body">
                                        <div class="planning-section-actions">
                                            <a href="functions/generate_ppmp.php?office=<?= urlencode($officeName) ?>" class="btn btn-success btn-sm" title="Download PPMP Excel" target="_blank">
                                                <i class="bi bi-file-earmark-excel"></i> Download PPMP
                                            </a>
                                        </div>
                                        <div class="accordion planning-unit-accordion" id="ppmpUnitAccordion<?= $officeIdx ?>">
                                            <?php $unitIdx = 0; foreach ($unitGroups as $unitName => $unitRows): ?>
                                            <?php $unitAccId = 'ppmpUnit' . $officeIdx . '_' . $unitIdx; ?>
                                            <div class="accordion-item planning-unit-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button py-2 <?= $unitIdx > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $unitAccId ?>">
                                                        <i class="bi bi-diagram-3 me-2 text-primary"></i>
                                                        <?= htmlspecialchars($unitName) ?>
                                                        <span class="badge text-bg-secondary ms-2"><?= count($unitRows) ?></span>
                                                    </button>
                                                </h2>
                                                <div id="<?= $unitAccId ?>" class="accordion-collapse collapse <?= $unitIdx === 0 ? 'show' : '' ?>" data-bs-parent="#ppmpUnitAccordion<?= $officeIdx ?>">
                                                    <div class="accordion-body p-0">
                                                        <div class="planning-table-wrap">
                                                            <table class="table planning-table table-striped mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th>General Description &amp; Objective</th>
                                                                        <th>PPMP Type</th>
                                                                        <th>Status</th>
                                                                        <th>File</th>
                                                                        <th>Submitted At</th>
                                                                        <th>Notes</th>
                                                                        <th></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($unitRows as $row): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($row['description']) ?></td>
                                                                        <td><?= htmlspecialchars($row['ppmp_type']) ?></td>
                                                                        <td>
                                                                            <?php
                                                                            $statusKey = strtolower(trim($row['status'] ?? 'pending'));
                                                                            $statusClass = match($statusKey) {
                                                                                'pending'  => 'sp-pending',
                                                                                'approved' => 'sp-approved',
                                                                                'rejected' => 'sp-rejected',
                                                                                default    => 'sp-supplemental'
                                                                            };
                                                                            ?>
                                                                            <span class="status-pill <?= $statusClass ?>"><?= ucfirst($statusKey) ?></span>
                                                                        </td>
                                                                        <td class="text-nowrap">
                                                                            <button type="button"
                                                                                class="btn btn-outline-info btn-sm icon-action-btn btn-view-details me-1"
                                                                                title="View Details"
                                                                                data-id="<?= $row['id'] ?>"
                                                                                data-office="<?= htmlspecialchars($row['office']) ?>"
                                                                                data-unit="<?= htmlspecialchars($row['unit']) ?>"
                                                                                data-description="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                                                                data-project_type="<?= htmlspecialchars($row['project_type'] ?? '') ?>"
                                                                                data-quantity="<?= htmlspecialchars($row['quantity'] ?? '') ?>"
                                                                                data-procurement_mode="<?= htmlspecialchars($row['procurement_mode'] ?? '') ?>"
                                                                                data-preproc="<?= htmlspecialchars($row['preproc'] ?? '') ?>"
                                                                                data-start_date="<?= htmlspecialchars($row['start_date'] ?? '') ?>"
                                                                                data-end_date="<?= htmlspecialchars($row['end_date'] ?? '') ?>"
                                                                                data-delivery_period="<?= htmlspecialchars($row['delivery_period'] ?? '') ?>"
                                                                                data-source_funds="<?= htmlspecialchars($row['source_funds'] ?? '') ?>"
                                                                                data-budget="<?= htmlspecialchars($row['budget'] ?? '') ?>"
                                                                                data-remarks="<?= htmlspecialchars($row['remarks'] ?? '') ?>"
                                                                            ><i class="bi bi-info-circle"></i></button>
                                                                        </td>
                                                                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                                                                        <td>
                                                                            <input type="text" name="notes" class="form-control form-control-sm"
                                                                                placeholder="Add notes..."
                                                                                value="<?= htmlspecialchars($row['remarks'] ?? '') ?>"
                                                                                form="actForm-<?= $row['id'] ?>">
                                                                        </td>
                                                                        <td>
                                                                            <form method="post" action="code.php" id="actForm-<?= $row['id'] ?>" class="d-inline">
                                                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                                                <div class="d-inline-flex gap-1 flex-wrap" role="group">
                                                                                    <button type="submit" name="set_status" value="Approved" class="btn btn-success btn-sm icon-action-btn" title="Approve">
                                                                                        <i class="bi bi-check-circle"></i>
                                                                                    </button>
                                                                                    <button type="submit" name="set_status" value="Rejected" class="btn btn-danger btn-sm icon-action-btn" title="Reject">
                                                                                        <i class="bi bi-x-circle"></i>
                                                                                    </button>
                                                                                    <button type="button"
                                                                                        class="btn btn-outline-primary btn-sm icon-action-btn btn-edit-ppmp"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#editPpmpModal"
                                                                                        data-id="<?= $row['id'] ?>"
                                                                                        data-ppmp_type="<?= htmlspecialchars($row['ppmp_type'] ?? '') ?>"
                                                                                        data-office="<?= htmlspecialchars($row['office'] ?? '') ?>"
                                                                                        data-unit="<?= htmlspecialchars($row['unit'] ?? '') ?>"
                                                                                        data-description="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                                                                        data-project_type="<?= htmlspecialchars($row['project_type'] ?? '') ?>"
                                                                                        data-quantity="<?= htmlspecialchars($row['quantity'] ?? '') ?>"
                                                                                        data-procurement_mode="<?= htmlspecialchars($row['procurement_mode'] ?? '') ?>"
                                                                                        data-preproc="<?= htmlspecialchars($row['preproc'] ?? '') ?>"
                                                                                        data-start_date="<?= htmlspecialchars($row['start_date'] ?? '') ?>"
                                                                                        data-end_date="<?= htmlspecialchars($row['end_date'] ?? '') ?>"
                                                                                        data-delivery_period="<?= htmlspecialchars($row['delivery_period'] ?? '') ?>"
                                                                                        data-source_funds="<?= htmlspecialchars($row['source_funds'] ?? '') ?>"
                                                                                        data-budget="<?= htmlspecialchars($row['budget'] ?? '') ?>"
                                                                                        data-remarks="<?= htmlspecialchars($row['remarks'] ?? '') ?>"
                                                                                        title="Edit PPMP"
                                                                                    ><i class="bi bi-pencil"></i></button>
                                                                                    <button type="button"
                                                                                        class="btn btn-outline-warning btn-sm icon-action-btn btn-reupload-ppmp"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#reuploadPpmpModal"
                                                                                        data-id="<?= $row['id'] ?>"
                                                                                        title="Reupload"
                                                                                    ><i class="bi bi-arrow-clockwise"></i></button>
                                                                                    <button type="button"
                                                                                        class="btn btn-outline-secondary btn-sm icon-action-btn btn-toggle-user-update"
                                                                                        data-id="<?= $row['id'] ?>"
                                                                                        data-allow="<?= (int)($row['allow_user_update'] ?? 0) ?>"
                                                                                        title="<?= !empty($row['allow_user_update']) ? 'Disable user update' : 'Enable user update' ?>"
                                                                                    ><i class="bi <?= !empty($row['allow_user_update']) ? 'bi-unlock-fill' : 'bi-unlock' ?>"></i></button>
                                                                                    <button type="button"
                                                                                        class="btn btn-danger btn-sm icon-action-btn btn-remove-ppmp"
                                                                                        data-id="<?= $row['id'] ?>"
                                                                                        title="Remove"
                                                                                    ><i class="bi bi-trash"></i></button>
                                                                                </div>
                                                                            </form>
                                                                        </td>
                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php $unitIdx++; endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $officeIdx++; endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="planning-tab-pane" id="tab-app">
                <div class="planning-toolbar planning-toolbar-app">
                    <div class="planning-toolbar-copy">
                        <h3>Annual Procurement Plan</h3>
                        <p><?= number_format($appRecordCount) ?> approved item<?= $appRecordCount === 1 ? '' : 's' ?> ready for APP export.</p>
                    </div>
                </div>
                <div class="planning-content">
                    <?php if (empty($app_by_office)): ?>
                        <div class="alert alert-info planning-empty-state">No approved PPMPs found.</div>
                    <?php else: ?>
                        <div class="accordion planning-accordion" id="appAccordion">
                            <?php $appOffIdx = 0; foreach ($app_by_office as $officeName => $unitGroups): ?>
                            <?php $appOffAccId = 'appOffice' . $appOffIdx;
                                  $appTotal = array_sum(array_map('count', $unitGroups)); ?>
                            <div class="accordion-item planning-office-item planning-office-item-app">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?= $appOffIdx > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $appOffAccId ?>">
                                        <span class="planning-accordion-title">
                                            <i class="bi bi-building me-2"></i>
                                            <?= htmlspecialchars($officeName) ?>
                                        </span>
                                        <span class="badge rounded-pill text-bg-success ms-2"><?= $appTotal ?> item<?= $appTotal > 1 ? 's' : '' ?></span>
                                    </button>
                                </h2>
                                <div id="<?= $appOffAccId ?>" class="accordion-collapse collapse <?= $appOffIdx === 0 ? 'show' : '' ?>" data-bs-parent="#appAccordion">
                                    <div class="accordion-body">
                                        <div class="accordion planning-unit-accordion" id="appUnitAccordion<?= $appOffIdx ?>">
                                            <?php $appUnitIdx = 0; foreach ($unitGroups as $unitName => $unitRows): ?>
                                            <?php $appUnitAccId = 'appUnit' . $appOffIdx . '_' . $appUnitIdx; ?>
                                            <div class="accordion-item planning-unit-item planning-unit-item-app">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button py-2 <?= $appUnitIdx > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $appUnitAccId ?>">
                                                        <i class="bi bi-diagram-3 me-2 text-success"></i>
                                                        <?= htmlspecialchars($unitName) ?>
                                                        <span class="badge text-bg-secondary ms-2"><?= count($unitRows) ?></span>
                                                    </button>
                                                </h2>
                                                <div id="<?= $appUnitAccId ?>" class="accordion-collapse collapse <?= $appUnitIdx === 0 ? 'show' : '' ?>" data-bs-parent="#appUnitAccordion<?= $appOffIdx ?>">
                                                    <div class="accordion-body p-0">
                                                        <div class="planning-section-actions planning-section-actions-app">
                                                            <a href="functions/generate_app.php?unit=<?= urlencode($unitName) ?>" class="btn btn-success btn-sm" title="Download APP Excel" target="_blank">
                                                                <i class="bi bi-file-earmark-excel"></i> Download APP
                                                            </a>
                                                        </div>
                                                        <div class="planning-table-wrap">
                                                            <table class="table planning-table planning-table-app table-striped mb-0">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>General Description &amp; Objective</th>
                                                                        <th>Mode of Procurement</th>
                                                                        <th>Early Procurement Activity?</th>
                                                                        <th>Criteria for Bid Evaluation</th>
                                                                        <th>Start of Procurement</th>
                                                                        <th>End of Procurement</th>
                                                                        <th>Source of Fund</th>
                                                                        <th>Estimated Budget (PhP)</th>
                                                                        <th>Procurement Strategy / Tools</th>
                                                                        <th>Remarks</th>
                                                                        <th>General Requirements</th>
                                                                        <th>Miscellaneous Items (Sec 32.2)</th>
                                                                        <th>CSE from PS-DBM</th>
                                                                        <th>Submitted At</th>
                                                                        <th></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($unitRows as $i => $row): ?>
                                                                    <tr>
                                                                        <td><?= $i + 1 ?></td>
                                                                        <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['procurement_mode'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['preproc'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['project_type'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['start_date'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['end_date'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['source_funds'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['budget'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['delivery_period'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['general_requirements'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['miscellaneous_items'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['cse_ps_dbm'] ?? '') ?></td>
                                                                        <td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
                                                                        <td>
                                                                            <button type="button" class="btn btn-warning btn-sm icon-action-btn btn-edit-app"
                                                                                title="Edit"
                                                                                data-id="<?= $row['id'] ?>"
                                                                                data-ppmp_type="<?= htmlspecialchars($row['ppmp_type'] ?? '') ?>"
                                                                                data-description="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                                                                data-procurement_mode="<?= htmlspecialchars($row['procurement_mode'] ?? '') ?>"
                                                                                data-preproc="<?= htmlspecialchars($row['preproc'] ?? '') ?>"
                                                                                data-project_type="<?= htmlspecialchars($row['project_type'] ?? '') ?>"
                                                                                data-start_date="<?= htmlspecialchars($row['start_date'] ?? '') ?>"
                                                                                data-end_date="<?= htmlspecialchars($row['end_date'] ?? '') ?>"
                                                                                data-source_funds="<?= htmlspecialchars($row['source_funds'] ?? '') ?>"
                                                                                data-budget="<?= htmlspecialchars($row['budget'] ?? '') ?>"
                                                                                data-delivery_period="<?= htmlspecialchars($row['delivery_period'] ?? '') ?>"
                                                                                data-remarks="<?= htmlspecialchars($row['remarks'] ?? '') ?>"
                                                                                data-general_requirements="<?= htmlspecialchars($row['general_requirements'] ?? '') ?>"
                                                                                data-miscellaneous_items="<?= htmlspecialchars($row['miscellaneous_items'] ?? '') ?>"
                                                                                data-cse_ps_dbm="<?= htmlspecialchars($row['cse_ps_dbm'] ?? '') ?>"
                                                                                data-bs-toggle="modal" data-bs-target="#editAppModal"
                                                                            ><i class="bi bi-pencil"></i></button>
                                                                        </td>
                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php $appUnitIdx++; endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $appOffIdx++; endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Edit PPMP Modal -->
<div class="modal fade planning-modal" id="editPpmpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable planning-modal-dialog">
    <div class="modal-content">
      <form method="post" action="code.php">
        <input type="hidden" name="edit_ppmp_id" id="editPpmpId">
        <div class="modal-header">
          <h5 class="modal-title">Edit PPMP Entry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">PPMP Type <span class="text-danger">*</span></label>
              <select name="ppmp_type" id="ep-ppmp_type" class="form-select" required>
                <option value="" disabled>Select type</option>
                <option value="Indicative">Indicative</option>
                <option value="Final">Final</option>
                <option value="Supplemental">Supplemental</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Office <span class="text-danger">*</span></label>
              <select
                name="office"
                id="ep-office"
                class="form-select planning-office-select"
                data-unit-target="editPpmpUnit"
                required
              >
                <option value="" selected disabled>Select office</option>
                <?php foreach ($planningOfficeUnits as $officeName => $units): ?>
                  <option value="<?= htmlspecialchars($officeName) ?>"><?= htmlspecialchars($officeName) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Unit <span class="text-danger">*</span></label>
              <select
                name="unit"
                id="editPpmpUnit"
                class="form-select planning-unit-select"
                disabled
                required
              >
                <option value="" selected disabled>Select office first</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">General Description &amp; Objective <span class="text-danger">*</span></label>
              <textarea name="description" id="ep-description" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Type of Project</label>
              <select name="project_type" id="ep-project_type" class="form-select">
                <option value="">Select</option>
                <option value="Goods">Goods</option>
                <option value="Infrastructure">Infrastructure</option>
                <option value="Consulting Services">Consulting Services</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantity &amp; Size</label>
              <input type="text" name="quantity" id="ep-quantity" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Mode of Procurement</label>
              <select name="procurement_mode" id="ep-procurement_mode" class="form-select">
                <option value="">Select mode</option>
                <?php foreach ($procurementModeOptions as $modeOption): ?>
                  <option value="<?= htmlspecialchars($modeOption) ?>"><?= htmlspecialchars($modeOption) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Pre-Procurement Conference</label>
              <select name="preproc" id="ep-preproc" class="form-select">
                <option value="">N/A</option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Start of Procurement</label>
              <input type="date" name="start_date" id="ep-start_date" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">End of Procurement</label>
              <input type="date" name="end_date" id="ep-end_date" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Delivery / Implementation Period</label>
              <input type="date" name="delivery_period" id="ep-delivery_period" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Source of Funds</label>
              <input type="text" name="source_funds" id="ep-source_funds" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Estimated Budget (PhP)</label>
              <input type="number" step="0.01" name="budget" id="ep-budget" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Remarks</label>
              <textarea name="remarks" id="ep-remarks" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_ppmp" value="1" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Create PPMP Modal -->
<div class="modal fade planning-modal" id="createPpmpModal" tabindex="-1" aria-labelledby="createPpmpLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable planning-modal-dialog">
    <div class="modal-content">
      <form method="post" action="code.php">
        <div class="modal-header">
          <h5 class="modal-title" id="createPpmpLabel">Create PPMP Entry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">PPMP Type <span class="text-danger">*</span></label>
              <select name="ppmp_type" class="form-select" required>
                <option value="" disabled selected>Select type</option>
                <option value="Indicative">Indicative</option>
                <option value="Final">Final</option>
                <option value="Supplemental">Supplemental</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Office <span class="text-danger">*</span></label>
              <select
                name="office"
                class="form-select planning-office-select"
                data-unit-target="createPpmpUnit"
                required
              >
                <option value="" selected disabled>Select office</option>
                <?php foreach ($planningOfficeUnits as $officeName => $units): ?>
                  <option value="<?= htmlspecialchars($officeName) ?>"><?= htmlspecialchars($officeName) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Unit <span class="text-danger">*</span></label>
              <select
                name="unit"
                id="createPpmpUnit"
                class="form-select planning-unit-select"
                disabled
                required
              >
                <option value="" selected disabled>Select office first</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">General Description &amp; Objective <span class="text-danger">*</span></label>
              <textarea name="description" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Type of Project</label>
              <select name="project_type" class="form-select">
                <option value=""> Select </option>
                <option value="Goods">Goods</option>
                <option value="Infrastructure">Infrastructure</option>
                <option value="Consulting Services">Consulting Services</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantity &amp; Size</label>
              <input type="text" name="quantity" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Mode of Procurement</label>
              <select name="procurement_mode" class="form-select">
                <option value="">Select mode</option>
                <?php foreach ($procurementModeOptions as $modeOption): ?>
                  <option value="<?= htmlspecialchars($modeOption) ?>"><?= htmlspecialchars($modeOption) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Pre-Procurement Conference</label>
              <select name="preproc" class="form-select">
                <option value=""> N/A </option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Start of Procurement</label>
              <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">End of Procurement</label>
              <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Delivery / Implementation Period</label>
              <input type="date" name="delivery_period" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Source of Funds</label>
              <input type="text" name="source_funds" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Estimated Budget (PhP)</label>
              <input type="number" step="0.01" name="budget" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Remarks</label>
              <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="create_ppmp" value="1" class="btn btn-primary">Save PPMP</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Upload PPMP Modal -->
<div class="modal fade planning-modal" id="uploadPpmpModal" tabindex="-1" aria-labelledby="uploadPpmpLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg planning-modal-dialog">
    <div class="modal-content">
      <form method="post" action="code.php" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="uploadPpmpLabel">Upload Project Procurement Management Plan (PPMP)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">PPMP Type</label>
                    <select name="ppmp_type" class="form-select" required>
                        <option value="" selected disabled>Select type</option>
                        <option value="Indicative">Indicative</option>
                        <option value="Final">Final</option>
                        <option value="Supplemental">Supplemental</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Office</label>
                    <select
                        name="office"
                        class="form-select planning-office-select"
                        data-unit-target="uploadPpmpUnit"
                        required
                    >
                        <option value="" selected disabled>Select office</option>
                        <?php foreach ($planningOfficeUnits as $officeName => $units): ?>
                            <option value="<?= htmlspecialchars($officeName) ?>"><?= htmlspecialchars($officeName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Unit</label>
                    <select
                        name="unit"
                        id="uploadPpmpUnit"
                        class="form-select planning-unit-select"
                        disabled
                        required
                    >
                        <option value="" selected disabled>Select office first</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">PPMP File</label>
                    <input type="file" name="ppmp_file" class="form-control" accept=".xlsx,.xls" required>
                    <div class="form-text">Allowed: xlsx, xls. Max 10MB.</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="upload_ppmp" value="1" class="btn btn-primary">Upload PPMP</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reupload PPMP Modal -->
<div class="modal fade planning-modal" id="reuploadPpmpModal" tabindex="-1" aria-labelledby="reuploadPpmpLabel" aria-hidden="true">
  <div class="modal-dialog planning-modal-dialog">
    <div class="modal-content">
      <form method="post" action="code.php" enctype="multipart/form-data">
        <input type="hidden" name="id" id="reuploadPpmpId">
        <div class="modal-header">
          <h5 class="modal-title" id="reuploadPpmpLabel">Reupload PPMP File</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">PPMP File</label>
                <input type="file" name="ppmp_file" class="form-control" accept=".xlsx,.xls" required>
                <div class="form-text">Allowed: xlsx, xls. Max 10MB.</div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="reupload_ppmp" value="1" class="btn btn-primary">Reupload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Details Modal -->
<div class="modal fade planning-modal" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable planning-modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">PPMP Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="overflow-x:auto;">
        <table class="table table-bordered table-sm text-center align-middle" style="min-width:1200px;font-size:.8rem;">
          <thead class="table-light">
            <tr>
              <th>Office</th>
              <th>Unit</th>
              <th>General Description &amp; Objective</th>
              <th>Type of Project<br>(Goods/Infra/Consulting)</th>
              <th>Quantity &amp; Size</th>
              <th>Mode of Procurement</th>
              <th>Pre-Procurement Conference</th>
              <th>Start of Procurement</th>
              <th>End of Procurement</th>
              <th>Delivery / Implementation Period</th>
              <th>Source of Funds</th>
              <th>Estimated Budget (PhP)</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td id="vd-office"></td>
              <td id="vd-unit"></td>
              <td id="vd-description"></td>
              <td id="vd-project_type"></td>
              <td id="vd-quantity"></td>
              <td id="vd-procurement_mode"></td>
              <td id="vd-preproc"></td>
              <td id="vd-start_date"></td>
              <td id="vd-end_date"></td>
              <td id="vd-delivery_period"></td>
              <td id="vd-source_funds"></td>
              <td id="vd-budget"></td>
              <td id="vd-remarks"></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit APP Modal -->
<div class="modal fade planning-modal" id="editAppModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg planning-modal-dialog" style="max-width:700px;">
    <div class="modal-content">
      <form method="post" action="code.php">
        <input type="hidden" name="edit_app_id" id="editAppId">
        <div class="modal-header">
          <h5 class="modal-title">Edit APP Entry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Project Title</label>
              <input type="text" name="ppmp_type" id="ea-ppmp_type" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Mode of Procurement</label>
              <select name="procurement_mode" id="ea-procurement_mode" class="form-select">
                <option value="">Select mode</option>
                <?php foreach ($procurementModeOptions as $modeOption): ?>
                  <option value="<?= htmlspecialchars($modeOption) ?>"><?= htmlspecialchars($modeOption) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">General Description</label>
              <textarea name="description" id="ea-description" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Early Procurement Activity?</label>
              <select name="preproc" id="ea-preproc" class="form-select">
                <option value="">— N/A —</option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Criteria for Bid Evaluation (Type of Project)</label>
              <select name="project_type" id="ea-project_type" class="form-select">
                <option value="Goods">Goods</option>
                <option value="Infrastructure">Infrastructure</option>
                <option value="Consulting Services">Consulting Services</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Start of Procurement</label>
              <input type="date" name="start_date" id="ea-start_date" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">End of Procurement</label>
              <input type="date" name="end_date" id="ea-end_date" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Source of Fund</label>
              <input type="text" name="source_funds" id="ea-source_funds" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Estimated Budget (PhP)</label>
              <input type="number" step="0.01" name="budget" id="ea-budget" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Procurement Strategy / Tools</label>
              <input type="date" name="delivery_period" id="ea-delivery_period" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">CSE from PS-DBM</label>
              <select name="cse_ps_dbm" id="ea-cse_ps_dbm" class="form-select">
                <option value="">N/A</option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Remarks</label>
              <textarea name="remarks" id="ea-remarks" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">General Requirements</label>
              <textarea name="general_requirements" id="ea-general_requirements" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Miscellaneous Items (Sec 32.2)</label>
              <textarea name="miscellaneous_items" id="ea-miscellaneous_items" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_app" value="1" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Excel Preview Modal -->
<div class="modal fade planning-modal" id="excelPreviewModal" tabindex="-1" aria-labelledby="excelPreviewLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable planning-modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="excelPreviewLabel">Excel Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="excelPreviewContent" style="overflow-x:auto;"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a
            href="#"
            class="btn btn-success icon-action-btn"
            id="downloadExcelBtn"
            target="_blank"
            title="Download"
            aria-label="Download"
        >
            <i class="bi bi-download"></i>
        </a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.planningOfficeUnits = <?= json_encode($planningOfficeUnits) ?>;
</script>
<script src="assets/js/planning.js?v=20260427-1"></script>
<?php if ($_swal): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: <?= json_encode($_swal['icon'] ?? 'info') ?>,
        title: <?= json_encode($_swal['title'] ?? '') ?>,
        text: <?= json_encode($_swal['text'] ?? '') ?>,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
});
</script>
<?php endif; ?>
