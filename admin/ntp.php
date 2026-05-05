<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../config/localdb.php';
include 'functions/authorization.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$pending   = $con->query("SELECT * FROM notice_to_proceed WHERE status='Pending' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$completed = $con->query("SELECT * FROM notice_to_proceed WHERE status='Completed' ORDER BY updated_at DESC")->fetch_all(MYSQLI_ASSOC);

// NTAs that are Completed but don't have an NTP yet
$readyNtas = $con->query("
    SELECT id, nta_number, supplier, project, amount,
           contact_name, contact_position, company_name,
           company_location, company_city, salutation
    FROM notice_to_award
    WHERE status = 'Completed'
      AND id NOT IN (SELECT IFNULL(nta_id,0) FROM notice_to_proceed WHERE nta_id IS NOT NULL)
    ORDER BY updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

$alert = $_SESSION['ntp_alert'] ?? null;
unset($_SESSION['ntp_alert']);
?>

<div class="admin-main">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0" style="font-family:'DM Serif Display',serif;color:var(--navy);">
            <i class="bi bi-file-earmark-check me-2" style="color:var(--sun)"></i>Notice to Proceed
        </h4>
        <button class="admin-btn sm" data-bs-toggle="modal" data-bs-target="#addNtpModal">
            <i class="bi bi-plus-lg"></i> Add NTP Manually
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

    <!-- NTAs READY FOR NTP -->
    <?php if (!empty($readyNtas)): ?>
    <div class="admin-card mb-4" style="border-left:4px solid var(--sun);">
        <div class="admin-card-head">
            <div>
                <div class="admin-card-title">⚡ Awarded — Ready for NTP</div>
                <small style="color:rgba(255,255,255,.5);">Click "Create NTP" to issue a Notice to Proceed.</small>
            </div>
            <span class="admin-card-pill"><?= count($readyNtas) ?> records</span>
        </div>
        <div class="admin-card-body p-0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>NTA No.</th>
                        <th>Supplier</th>
                        <th>Project</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($readyNtas as $i => $nta): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($nta['nta_number']) ?></td>
                        <td><?= htmlspecialchars($nta['supplier']) ?></td>
                        <td><?= htmlspecialchars($nta['project']) ?></td>
                        <td>₱ <?= number_format((float)$nta['amount'], 2) ?></td>
                        <td>
                            <button type="button" class="admin-btn sm" style="background:var(--sun);color:#fff;"
                                onclick="openCreateNtp(<?= $nta['id'] ?>, '<?= addslashes($nta['nta_number']) ?>', '<?= addslashes($nta['supplier']) ?>', '<?= addslashes($nta['project']) ?>', <?= (float)$nta['amount'] ?>, '<?= addslashes($nta['salutation']) ?>', '<?= addslashes($nta['contact_name']) ?>', '<?= addslashes($nta['contact_position']) ?>', '<?= addslashes($nta['company_name']) ?>', '<?= addslashes($nta['company_location']) ?>', '<?= addslashes($nta['company_city']) ?>')">
                                <i class="bi bi-file-earmark-plus"></i> Create NTP
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- PENDING TABLE -->
    <div class="admin-card mb-4">
        <div class="admin-card-head">
            <div><div class="admin-card-title">🟡 Pending</div></div>
            <span class="admin-card-pill"><?= count($pending) ?> records</span>
        </div>
        <div class="admin-card-body p-0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>NTP No.</th>
                        <th>Supplier</th>
                        <th>Project</th>
                        <th>Amount</th>
                        <th>Delivery Days</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending)): ?>
                        <tr><td colspan="8" class="empty-cell">No pending NTPs.</td></tr>
                    <?php else: foreach ($pending as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['ntp_number']) ?></td>
                            <td><?= htmlspecialchars($row['supplier']) ?></td>
                            <td><?= htmlspecialchars($row['project']) ?></td>
                            <td>₱ <?= number_format((float)$row['amount'], 2) ?></td>
                            <td><?= $row['delivery_days'] ?> days</td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td class="d-flex gap-1 flex-wrap">
                                <a href="functions/generate_ntp.php?id=<?= $row['id'] ?>" class="admin-btn sm" target="_blank">
                                    <i class="bi bi-file-earmark-word"></i> Generate Word
                                </a>
                                <button type="button" class="admin-btn sm" style="background:#b45309;" onclick="openEditNtp(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form method="POST" action="functions/ntp_actions.php" style="display:inline;">
                                    <input type="hidden" name="action" value="mark_complete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="admin-btn sm" style="background:var(--green);">
                                        <i class="bi bi-check-circle"></i> Mark Complete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- COMPLETED TABLE -->
    <div class="admin-card mb-4">
        <div class="admin-card-head">
            <div><div class="admin-card-title">🟢 Completed</div></div>
            <span class="admin-card-pill"><?= count($completed) ?> records</span>
        </div>
        <div class="admin-card-body p-0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>NTP No.</th>
                        <th>Supplier</th>
                        <th>Project</th>
                        <th>Amount</th>
                        <th>Delivery Days</th>
                        <th>Date Completed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($completed)): ?>
                        <tr><td colspan="8" class="empty-cell">No completed NTPs.</td></tr>
                    <?php else: foreach ($completed as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['ntp_number']) ?></td>
                            <td><?= htmlspecialchars($row['supplier']) ?></td>
                            <td><?= htmlspecialchars($row['project']) ?></td>
                            <td>₱ <?= number_format((float)$row['amount'], 2) ?></td>
                            <td><?= $row['delivery_days'] ?> days</td>
                            <td><?= date('M d, Y', strtotime($row['updated_at'])) ?></td>
                            <td class="d-flex gap-1 flex-wrap">
                                <a href="functions/generate_ntp.php?id=<?= $row['id'] ?>" class="admin-btn sm" target="_blank">
                                    <i class="bi bi-file-earmark-word"></i> Download Word
                                </a>
                                <button type="button" class="admin-btn sm" style="background:#b45309;" onclick="openEditNtp(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- EDIT NTP MODAL -->
<div class="modal fade" id="editNtpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="functions/ntp_actions.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_ntp_id">
            <div class="modal-content" style="border-radius:16px;border:none;">
                <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun);">
                    <h5 class="modal-title" style="font-family:'DM Serif Display',serif;color:#fff;">
                        <i class="bi bi-pencil me-2"></i>Edit Notice to Proceed
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">NTP Number</label>
                            <input type="text" name="ntp_number" id="edit_ntp_number" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Delivery Days</label>
                            <input type="number" name="delivery_days" id="edit_delivery_days" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supplier</label>
                        <input type="text" name="supplier" id="edit_supplier" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Project / Title</label>
                        <input type="text" name="project" id="edit_project" class="form-control">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold">Amount (₱)</label>
                            <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <p class="fw-semibold mb-2" style="font-size:13px;color:var(--navy);">Addressee Details</p>
                    <div class="row g-2">
                        <div class="col-3">
                            <label class="form-label fw-semibold">Salutation</label>
                            <select name="salutation" id="edit_salutation" class="form-select">
                                <option value="Mr.">Mr.</option>
                                <option value="Ms.">Ms.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Engr.">Engr.</option>
                            </select>
                        </div>
                        <div class="col-9">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" name="contact_name" id="edit_contact_name" class="form-control" placeholder="e.g. Dela Cruz">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Position / Designation</label>
                            <input type="text" name="contact_position" id="edit_contact_position" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Company Name</label>
                            <input type="text" name="company_name" id="edit_company_name" class="form-control">
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold">Company Address</label>
                            <input type="text" name="company_location" id="edit_company_location" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="company_city" id="edit_company_city" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="admin-btn"><i class="bi bi-save"></i> Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- CREATE NTP FROM NTA MODAL -->
<div class="modal fade" id="createNtpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="functions/ntp_actions.php">
            <input type="hidden" name="action" value="create_from_nta">
            <input type="hidden" name="nta_id" id="cntp_nta_id">
            <div class="modal-content" style="border-radius:16px;border:none;">
                <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun);">
                    <h5 class="modal-title" style="font-family:'DM Serif Display',serif;color:#fff;">
                        <i class="bi bi-file-earmark-plus me-2"></i>Create Notice to Proceed
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-muted" style="font-size:12px;">NTA REFERENCE</label>
                        <div id="cntp_ref" class="fw-bold"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="supplier" id="cntp_supplier" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Project / Title</label>
                        <input type="text" name="project" id="cntp_project" class="form-control">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold">Amount (₱) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="cntp_amount" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Delivery Days <span class="text-danger">*</span></label>
                            <input type="number" name="delivery_days" id="cntp_delivery_days" class="form-control" min="1" value="30" required>
                        </div>
                    </div>
                    <hr>
                    <p class="fw-semibold mb-2" style="font-size:13px;color:var(--navy);">Addressee Details</p>
                    <div class="row g-2">
                        <div class="col-3">
                            <label class="form-label fw-semibold">Salutation</label>
                            <select name="salutation" id="cntp_salutation" class="form-select">
                                <option value="Mr.">Mr.</option>
                                <option value="Ms.">Ms.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Engr.">Engr.</option>
                            </select>
                        </div>
                        <div class="col-9">
                            <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="contact_name" id="cntp_contact_name" class="form-control" placeholder="e.g. Dela Cruz" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Position / Designation</label>
                            <input type="text" name="contact_position" id="cntp_contact_position" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Company Name</label>
                            <input type="text" name="company_name" id="cntp_company_name" class="form-control">
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold">Company Address</label>
                            <input type="text" name="company_location" id="cntp_company_location" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="company_city" id="cntp_company_city" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="admin-btn"><i class="bi bi-save"></i> Save NTP</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ADD NTP MANUALLY MODAL -->
<div class="modal fade" id="addNtpModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="functions/ntp_actions.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-content" style="border-radius:16px;border:none;">
                <div class="modal-header" style="background:linear-gradient(to right,var(--navy),var(--navy-mid));border-bottom:3px solid var(--sun);">
                    <h5 class="modal-title" style="font-family:'DM Serif Display',serif;color:#fff;">
                        <i class="bi bi-plus-circle me-2"></i>Add Notice to Proceed
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="supplier" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Project / Description <span class="text-danger">*</span></label>
                        <input type="text" name="project" class="form-control" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label fw-semibold">Amount (₱) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Delivery Days <span class="text-danger">*</span></label>
                            <input type="number" name="delivery_days" class="form-control" min="1" value="30" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="admin-btn"><i class="bi bi-save"></i> Save NTP</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openEditNtp(row) {
    document.getElementById('edit_ntp_id').value           = row.id;
    document.getElementById('edit_ntp_number').value       = row.ntp_number ?? '';
    document.getElementById('edit_supplier').value         = row.supplier ?? '';
    document.getElementById('edit_project').value          = row.project ?? '';
    document.getElementById('edit_amount').value           = row.amount ?? '';
    document.getElementById('edit_delivery_days').value    = row.delivery_days ?? 30;
    document.getElementById('edit_status').value           = row.status ?? 'Pending';
    document.getElementById('edit_salutation').value       = row.salutation ?? 'Mr.';
    document.getElementById('edit_contact_name').value     = row.contact_name ?? '';
    document.getElementById('edit_contact_position').value = row.contact_position ?? '';
    document.getElementById('edit_company_name').value     = row.company_name ?? '';
    document.getElementById('edit_company_location').value = row.company_location ?? '';
    document.getElementById('edit_company_city').value     = row.company_city ?? '';
    new bootstrap.Modal(document.getElementById('editNtpModal')).show();
}

function openCreateNtp(id, ntaNumber, supplier, project, amount, salutation, contactName, contactPosition, companyName, companyLocation, companyCity) {
    document.getElementById('cntp_nta_id').value          = id;
    document.getElementById('cntp_ref').textContent        = ntaNumber;
    document.getElementById('cntp_supplier').value         = supplier;
    document.getElementById('cntp_project').value          = project;
    document.getElementById('cntp_amount').value           = amount > 0 ? amount : '';
    document.getElementById('cntp_salutation').value       = salutation;
    document.getElementById('cntp_contact_name').value     = contactName;
    document.getElementById('cntp_contact_position').value = contactPosition;
    document.getElementById('cntp_company_name').value     = companyName;
    document.getElementById('cntp_company_location').value = companyLocation;
    document.getElementById('cntp_company_city').value     = companyCity;
    new bootstrap.Modal(document.getElementById('createNtpModal')).show();
}
</script>

</body>
</html>
