<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../config/localdb.php';
include 'includes/auth.php';
include '../functions/documentsactions.php';
// Get current user account_id
$account_id = $_SESSION['auth_user']['account_id'] ?? ($_SESSION['account_id'] ?? null);
// Fetch PPMP submissions for this user
$ppmp = $account_id ? getPPMPByUser($con, $account_id) : null;
// Fetch PR submissions for this user
$prs = $account_id ? getPRByUser($con, $account_id) : null;

$ppmpOfficeUnits = [
    'Office of the Schools Division Superintendent' => [
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
    'Curriculum Implementation Division' => [
        'LR',
        'EPS',
        'PSDS',
        'ALS',
    ],
    'School Governance and Operations Division' => [
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
];

include 'includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['alert'])):
    $alert = $_SESSION['alert'];
    $icon  = $alert['type'] ?? 'info';
    $title = $alert['title'] ?? ucfirst($icon);
    $text  = $alert['msg'] ?? ($alert['message'] ?? '');
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: <?= json_encode($icon) ?>,
        title: <?= json_encode($title) ?>,
        text: <?= json_encode($text) ?>,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
});
</script>
<?php unset($_SESSION['alert']); endif; ?>

    
<link rel="stylesheet" href="assets/css/css.css">
<!-- -- LAYOUT -- -->
<div class="db-layout">
    <?php include 'includes/sidebar.php'; ?>
    <!-- MAIN -->
    <main class="db-main">
        <div class="db-card" id="tab-submissions">
            <div class="db-card-head">
                <div>
                    <div class="db-card-title">My PPMP Submissions</div>
                    <div class="db-card-subtitle">History of all your submitted PPMPs</div>
                </div>
                <div class="db-card-badge"><i class="bi bi-shield-check"></i> RA 9184</div>
            </div>
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Office</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Download</th>
                        <th>Reupload</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Helper to map status to pill class and label
                    $statusPill = function($status) {
                        $s = strtolower(trim($status ?? 'pending'));
                        if ($s === 'approved') return ['sp-approved', 'Approved'];
                        if ($s === 'rejected') return ['sp-rejected', 'Rejected'];
                        if ($s === 'under review' || $s === 'review') return ['sp-review', 'Under Review'];
                        return ['sp-pending', ucfirst($s) ?: 'Pending'];
                    };

                    if ($ppmp && $ppmp->num_rows > 0):
                        while($row = $ppmp->fetch_assoc()):
                            $ref = 'PPMP-' . date('Y', strtotime($row['created_at'] ?? 'now')) . '-' . str_pad((string)($row['id'] ?? 0), 3, '0', STR_PAD_LEFT);
                            [$cls, $label] = $statusPill($row['status'] ?? '');
                            $filePath = (string)($row['file_path'] ?? '');
                            $downloadHref = '';
                            if ($filePath !== '') {
                                if (str_starts_with($filePath, '/')) {
                                    $downloadHref = $filePath;
                                } else {
                                    $downloadHref = '/uploads/ppmp/' . basename($filePath);
                                }
                            }
                            $rawStatus = strtolower(trim((string)($row['status'] ?? '')));
                            $canReupload = $rawStatus !== 'approved';
                            $canEdit = $rawStatus !== 'approved' || (int)($row['allow_user_update'] ?? 0) === 1;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ref) ?></strong></td>
                        <td><?= htmlspecialchars($row['ppmp_type'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['office'] ?? '-') ?></td>
                        <td><?= isset($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '-' ?></td>
                        <td><span class="status-pill <?= $cls ?>"><?= htmlspecialchars($label) ?></span></td>
                        <td><?= htmlspecialchars(trim((string)($row['notes'] ?? '')) ?: '-') ?></td>
                        <td>
                            <?php if ($downloadHref !== ''): ?>
                                <a href="<?= htmlspecialchars($downloadHref) ?>" download class="btn btn-sm btn-outline-primary" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['id']) && $canReupload): ?>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary reupload-btn"
                                    data-id="<?= htmlspecialchars((string)$row['id']) ?>"
                                    data-ref="<?= htmlspecialchars($ref) ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reuploadModal"
                                    title="Reupload"
                                >
                                    <i class="bi bi-upload"></i>
                                </button>
                            <?php elseif (!empty($row['id'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Approved">
                                    <i class="bi bi-check2-circle"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['id']) && $canEdit): ?>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary edit-ppmp-btn"
                                    data-id="<?= htmlspecialchars((string)$row['id']) ?>"
                                    data-ref="<?= htmlspecialchars($ref) ?>"
                                    data-ppmp_type="<?= htmlspecialchars($row['ppmp_type'] ?? '') ?>"
                                    data-office="<?= htmlspecialchars($row['office'] ?? '') ?>"
                                    data-unit="<?= htmlspecialchars($row['unit'] ?? '') ?>"
                                    data-description="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                    data-project_type="<?= htmlspecialchars($row['project_type'] ?? '') ?>"
                                    data-quantity="<?= htmlspecialchars($row['quantity'] ?? '') ?>"
                                    data-procurement_mode="<?= htmlspecialchars($row['procurement_mode'] ?? '') ?>"
                                    data-preproc="<?= (string)($row['preproc'] ?? '') === '1' ? 'Yes' : ((string)($row['preproc'] ?? '') === '0' ? 'No' : '') ?>"
                                    data-start_date="<?= htmlspecialchars($row['start_date'] ?? '') ?>"
                                    data-end_date="<?= htmlspecialchars($row['end_date'] ?? '') ?>"
                                    data-delivery_period="<?= htmlspecialchars($row['delivery_period'] ?? '') ?>"
                                    data-source_funds="<?= htmlspecialchars($row['source_funds'] ?? '') ?>"
                                    data-budget="<?= htmlspecialchars($row['budget'] ?? '') ?>"
                                    data-remarks="<?= htmlspecialchars($row['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editPpmpModal"
                                    title="Update PPMP"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Locked">
                                    <i class="bi bi-lock"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No submissions found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Purchase Requests -->
        <div class="db-card" style="margin-top:1.5rem;">
            <div class="db-card-head">
                <div>
                    <div class="db-card-title">My Purchase Requests</div>
                    <div class="db-card-subtitle">History of all your submitted purchase requests</div>
                </div>
                <div class="db-card-badge"><i class="bi bi-cart-check"></i> PR</div>
            </div>
            <table class="db-table">
                <thead>
                    <tr>
                        <th>PR No.</th>
                        <th>Office</th>
                        <th>Requested By</th>
                        <th>Total Amount</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($prs && $prs->num_rows > 0):
                        while ($pr = $prs->fetch_assoc()):
                            $s = strtolower(trim($pr['status'] ?? 'pending'));
                            if ($s === 'approved') $cls = 'sp-approved';
                            elseif ($s === 'rejected') $cls = 'sp-rejected';
                            elseif ($s === 'under review' || $s === 'review') $cls = 'sp-review';
                            else $cls = 'sp-pending';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pr['pr_number'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($pr['office'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($pr['requested_by'] ?? '-') ?></td>
                        <td>&#8369;<?= number_format((float)($pr['total_amount'] ?? 0), 2) ?></td>
                        <td><?= isset($pr['created_at']) ? date('M d, Y', strtotime($pr['created_at'])) : '-' ?></td>
                        <td><span class="status-pill <?= $cls ?>"><?= htmlspecialchars(ucfirst($pr['status'] ?? 'Pending')) ?></span></td>
                        <td><?= htmlspecialchars(trim((string)($pr['remarks'] ?? '')) ?: '-') ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No purchase requests found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<div class="modal fade" id="reuploadModal" tabindex="-1" aria-labelledby="reuploadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="code.php" enctype="multipart/form-data" id="reupload_ppmp">
        <input type="hidden" name="action" value="reupload_ppmp">
        <input type="hidden" name="id" id="reuploadId">
        <input type="hidden" name="redirect" value="submissions.php">
        <div class="modal-header">
          <h5 class="modal-title" id="reuploadModalLabel">Reupload PPMP</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="small text-muted mb-3" id="reuploadRef"></div>
          <div class="mb-2">
            <label class="form-label" for="reuploadFile">PPMP File</label>
            <input type="file" name="ppmp_file" id="reuploadFile" class="form-control" accept=".xlsx,.xls" required>
            <div class="form-text">Allowed: xlsx, xls. Max 10MB.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="reupload_ppmp" value="1" class="btn btn-primary" id="reuploadSubmitBtn">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editPpmpModal" tabindex="-1" aria-labelledby="editPpmpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="code.php" enctype="multipart/form-data" id="edit_ppmp_form">
        <input type="hidden" name="action" value="update_ppmp">
        <input type="hidden" name="id" id="editPpmpId">
        <input type="hidden" name="redirect" value="submissions.php">
        <div class="modal-header bg-light border-bottom">
          <div>
            <h5 class="modal-title" id="editPpmpModalLabel">Update PPMP</h5>
            <div class="small text-muted mt-1" id="editPpmpRef"></div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="max-height: calc(100vh - 200px); overflow-y: auto;">
          <div class="alert alert-info small" role="alert">
            <i class="bi bi-info-circle"></i> Loading PPMP details...
          </div>
          
          <!-- Section 1: PPMP Information -->
          <div class="card mb-3 border-0 bg-light">
            <div class="card-header bg-primary text-white py-2">
              <h6 class="mb-0"><i class="bi bi-file-earmark"></i> PPMP Information</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-600">PPMP Type</label>
                  <select name="ppmp_type" id="edit_ppmp_type" class="form-select" required>
                    <option value="Indicative">Indicative</option>
                    <option value="Final">Final</option>
                    <option value="Supplemental">Supplemental</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">Type of Project</label>
                  <select name="project_type" id="edit_project_type" class="form-select">
                    <option value="">Select Type</option>
                    <option value="Goods">Goods</option>
                    <option value="Infrastructure">Infrastructure</option>
                    <option value="Consulting Services">Consulting Services</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2: Office & Unit -->
          <div class="card mb-3 border-0 bg-light">
            <div class="card-header bg-primary text-white py-2">
              <h6 class="mb-0"><i class="bi bi-building"></i> Office & Unit</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-600">Office</label>
                  <select name="office" id="editOfficeSelect" class="form-select" required>
                    <option value="">Select Office</option>
                    <?php foreach ($ppmpOfficeUnits as $officeName => $units): ?>
                        <option value="<?= htmlspecialchars($officeName) ?>"><?= htmlspecialchars($officeName) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">Unit / Section</label>
                  <select name="unit" id="editUnitSelect" class="form-select" required>
                    <option value="">Select Unit</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3: Description -->
          <div class="card mb-3 border-0 bg-light">
            <div class="card-header bg-primary text-white py-2">
              <h6 class="mb-0"><i class="bi bi-file-text"></i> Description</h6>
            </div>
            <div class="card-body">
              <div>
                <label class="form-label fw-600">General Description and Objective</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3" required placeholder="Enter description and objectives..."></textarea>
              </div>
            </div>
          </div>

          <!-- Section 4: Project Details -->
          <div class="card mb-3 border-0 bg-light">
            <div class="card-header bg-primary text-white py-2">
              <h6 class="mb-0"><i class="bi bi-gear"></i> Project Details</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-600">Quantity / Size of Project</label>
                  <input type="text" name="quantity" id="edit_quantity" class="form-control" placeholder="e.g., 100 units">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">Source of Funds</label>
                  <input type="text" name="source_funds" id="edit_source_funds" class="form-control" placeholder="e.g., GAA, Savings">
                </div>
              </div>
            </div>
          </div>

          <!-- Section 5: Procurement -->
          <div class="card mb-3 border-0 bg-light">
            <div class="card-header bg-primary text-white py-2">
              <h6 class="mb-0"><i class="bi bi-cart"></i> Procurement Details</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-600">Recommended Mode of Procurement</label>
                  <select name="procurement_mode" id="edit_procurement_mode" class="form-select">
                    <option value="">Select Mode</option>
                    <option value="Section 27 - Competitive Bidding">Section 27 - Competitive Bidding</option>
                    <option value="Section 28 - Limited Source Bidding">Section 28 - Limited Source Bidding</option>
                    <option value="Section 29 - Competitive Dialogue">Section 29 - Competitive Dialogue</option>
                    <option value="Section 30 - Unsolicited Offer with Bid Matching">Section 30 - Unsolicited Offer with Bid Matching</option>
                    <option value="Section 31 - Direct Contracting">Section 31 - Direct Contracting</option>
                    <option value="Section 32 - Direct Acquisition">Section 32 - Direct Acquisition</option>
                    <option value="Section 33 - Repeat Order">Section 33 - Repeat Order</option>
                    <option value="Section 34 - Small Value Procurement">Section 34 - Small Value Procurement</option>
                    <option value="Section 35 - Negotiated Procurement">Section 35 - Negotiated Procurement</option>
                    <option value="Section 36 - Direct Sales">Section 36 - Direct Sales</option>
                    <option value="Section 37 - Direct Procurement for Science, Technology and Innovation">Section 37 - DPSTTI</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">Pre-Procurement Conference</label>
                  <select name="preproc" id="edit_preproc" class="form-select">
                    <option value="">N/A</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 6: Timeline -->
          <div class="card mb-3 border-0 bg-light">
            <div class="card-header bg-primary text-white py-2">
              <h6 class="mb-0"><i class="bi bi-calendar3"></i> Timeline</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label fw-600">Start of Procurement</label>
                  <input type="date" name="start_date" id="edit_start_date" class="form-control">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-600">End of Procurement</label>
                  <input type="date" name="end_date" id="edit_end_date" class="form-control">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-600">Expected Delivery / Implementation</label>
                  <input type="date" name="delivery_period" id="edit_delivery_period" class="form-control">
                </div>
              </div>
            </div>
          </div>

          <!-- Section 7: Budget & Documents -->
          <div class="card mb-3 border-0 bg-light">
            <div class="card-header bg-primary text-white py-2">
              <h6 class="mb-0"><i class="bi bi-cash-coin"></i> Budget & Documents</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-600">Estimated Budget</label>
                  <div class="input-group">
                    <span class="input-group-text">&#8369;</span>
                    <input type="number" step="0.01" name="budget" id="edit_budget" class="form-control" placeholder="0.00">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">Replace Supporting Document</label>
                  <input type="file" name="supporting_doc" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx" id="supporting_doc">
                  <div class="form-text">Optional. PDF, DOC, DOCX, XLS, XLSX up to 10MB.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 8: Remarks -->
          <div class="card mb-3 border-0 bg-light">
            <div class="card-header bg-primary text-white py-2">
              <h6 class="mb-0"><i class="bi bi-chat-left-text"></i> Additional Information</h6>
            </div>
            <div class="card-body">
              <label class="form-label fw-600">Remarks</label>
              <textarea name="remarks" id="edit_remarks" class="form-control" rows="2" placeholder="Enter any additional remarks..."></textarea>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update_ppmp" value="1" class="btn btn-primary" id="editPpmpSubmitBtn">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background:rgba(255,255,255,.7);z-index:2000;">
  <div class="position-absolute top-50 start-50 translate-middle text-center">
    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
    <div class="mt-2 fw-semibold text-muted">Uploading...</div>
  </div>
</div>

<!-- Mark My Submissions as active in sidebar -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.ppmpOfficeUnits = <?= json_encode($ppmpOfficeUnits) ?>;
</script>
<script src="assets/js/submissions.js"></script>
