<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/localdb.php';
include 'functions/authorization.php';
include 'functions/functions.php';
include 'includes/sidebar.php';
include 'includes/header.php';  

// Pagination settings
$limit = 5; // items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Total procurements
$total_procurements = $con->query("SELECT COUNT(*) as total FROM procurements")->fetch_assoc()['total'];
$total_pages = ceil($total_procurements / $limit);

// Fetch procurements for current page
$procurements = $con->query("SELECT * FROM procurements WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
?>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="assets/css/admin.css">
<div id="pageLoader" aria-hidden="false">
  <div class="loader-card">
    <div class="spinner-border text-primary" role="status" aria-label="Loading"></div>
    <div>
      <div class="loader-title">Loading!!!!</div>
      <div class="loader-sub">Please wait</div>
    </div>
  </div>
</div>

<script>
  (function () {
    var loader = document.getElementById('pageLoader');
    if (!loader) return;

    window.showPageLoader = function () {
      loader.classList.remove('hidden');
      loader.setAttribute('aria-hidden', 'false');
    };

    window.hidePageLoader = function () {
      loader.classList.add('hidden');
      loader.setAttribute('aria-hidden', 'true');
    };

    window.addEventListener('load', function () {
      window.hidePageLoader();
    });

    document.addEventListener('submit', function () {
      window.showPageLoader();
    }, true);

    document.addEventListener('click', function (e) {
      var a = e.target && e.target.closest ? e.target.closest('a') : null;
      if (!a) return;
      if (a.hasAttribute('download')) return;
      if (a.getAttribute('target') === '_blank') return;
      if (a.getAttribute('href') && a.getAttribute('href').startsWith('#')) return;
      if (a.getAttribute('data-bs-toggle')) return;
      window.showPageLoader();
    }, true);
  })();
</script>

<div class="container py-5"></div>
<div class="admin-main">
    <div class="admin-card">
        <div class="admin-card-head">
            <div>
                <div class="admin-card-title">Bidding & Procurement Management</div>
                <div class="admin-card-sub">Manage active opportunities, schedules, and documents</div>
            </div>
            <div class="d-flex align-items-center gap-2 admin-card-actions">
                <input type="text" class="form-control form-control-sm" id="procSearch" placeholder="Search title or ID">
                <select class="form-select form-select-sm" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
                <button type="button" class="admin-btn sm" data-bs-toggle="modal" data-bs-target="#addProcurementModal">
                    <i class="bi bi-plus-lg"></i> Add Procurement
                </button>
            </div>
        </div>
        <div class="admin-card-body">
            <div class="table-responsive">
                <table class="admin-table" id="procTable">
                    <thead>
                        <tr>
                            <th>Bidding ID</th>
                            <th>Title</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Date Published</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(!empty($procurements)) : ?>
                        <?php foreach($procurements as $proc): ?>
                            <?php $rowStatus = strtolower($proc['status']); ?>
                            <tr data-title="<?= strtolower(htmlspecialchars($proc['title'])) ?>" data-status="<?= $rowStatus ?>">
                                <td><?= htmlspecialchars($proc['reference_no'] ?: $proc['id']) ?></td>
                                <td><?= htmlspecialchars($proc['title']) ?></td>
                                <td><?= htmlspecialchars($proc['mode']) ?></td>
                                <td>
                                    <?php if($rowStatus === 'open'): ?>
                                        <span class="status-pill sp-approved">Open</span>
                                    <?php elseif($rowStatus === 'closed'): ?>
                                        <span class="status-pill sp-rejected">Closed</span>
                                    <?php else: ?>
                                        <span class="status-pill sp-pending"><?= htmlspecialchars($proc['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('Y-m-d', strtotime($proc['start_date'] ?: $proc['created_at'])) ?></td>
                                <td>
                                    <div class="d-inline-flex gap-1">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewProcModal<?= $proc['id'] ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProcModal<?= $proc['id'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $proc['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                    <!-- VIEW MODAL -->
                    <div class="modal fade" id="viewProcModal<?= $proc['id'] ?>" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?= htmlspecialchars($proc['title']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Description:</strong> <?= htmlspecialchars($proc['description']) ?></p>
                                    <p><strong>Mode of Procurement:</strong> <?= htmlspecialchars($proc['mode']) ?></p>
                                    <p><strong>Nature of Procurement:</strong> <?= htmlspecialchars($proc['nature']) ?></p>
                                    <p><strong>Approved Budget:</strong> ₱<?= number_format($proc['approved_budget'],2) ?></p>
                                    <p><strong>Date Published:</strong> <?= date('Y-m-d', strtotime($proc['start_date'] ?: $proc['created_at'])) ?></p>

                                    <p><strong>Schedule:</strong></p>
                                    <ul>
                                        <li>Pre-Bid Conference: <?= $proc['prebid_datetime'] ? date('Y-m-d H:i', strtotime($proc['prebid_datetime'])) : '-' ?></li>
                                        <li>Deadline for Submission: <?= $proc['deadline_datetime'] ? date('Y-m-d H:i', strtotime($proc['deadline_datetime'])) : '-' ?></li>
                                        <li>Bid Opening: <?= $proc['bid_opening_datetime'] ? date('Y-m-d H:i', strtotime($proc['bid_opening_datetime'])) : '-' ?></li>
                                    </ul>

                                    <p><strong>Lot Table Reference:</strong></p>
                                    <ul>
                                        <?php
                                        $lots = $con->query("SELECT * FROM procurement_lots WHERE procurement_id = ".$proc['id'])->fetch_all(MYSQLI_ASSOC);
                                        if(!empty($lots)):
                                            foreach($lots as $lot):
                                                echo "<li>".htmlspecialchars($lot['lot_name'])." - ₱".number_format($lot['amount_per_lot'],2)."</li>";
                                            endforeach;
                                        else:
                                            echo "<li>No lots found.</li>";
                                        endif;
                                        ?>
                                    </ul>

                                    <p><strong>Bidding Document:</strong> 
                                        <?= $proc['document_file'] ? "<a href='uploads/".$proc['document_file']."' target='_blank'>View</a>" : 'None' ?>
                                    </p>
                                    <p><strong>Additional Document:</strong> <?= $proc['additional_doc'] ?? 'None' ?></p>
                                    <p><strong>Winning Bidder:</strong> <?= htmlspecialchars($proc['winning_bidder'] ?? '-') ?></p>
                                    <p><strong>Submitted By / Office:</strong> <?= htmlspecialchars($proc['submitted_by'] .' / '. $proc['office']) ?></p>
                                    <p><strong>Status:</strong> <?= htmlspecialchars($proc['status']) ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EDIT MODAL -->
                    <div class="modal fade" id="editProcModal<?= $proc['id'] ?>" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <form action="functions/procurementfunctions.php" method="POST" enctype="multipart/form-data">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Procurement</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?= $proc['id'] ?>">
                                        <input type="hidden" name="existing_document_file" value="<?= htmlspecialchars($proc['document_file'] ?? '') ?>">
                                        <input type="hidden" name="existing_additional_doc" value="<?= htmlspecialchars($proc['additional_doc'] ?? '') ?>">

                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" class="form-control" name="proc_title" value="<?= htmlspecialchars($proc['title']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Mode of Procurement</label>
                                            <select class="form-select" name="proc_mode" required>
                                                <?php $modes = ['Public Bidding','Direct Contracting','Limited Source Bidding','Alternative Methods']; ?>
                                                <option value="" disabled>Select Mode</option>
                                                <?php foreach($modes as $m): ?>
                                                    <option value="<?= $m ?>" <?= ($proc['mode']===$m)?'selected':'' ?>><?= $m ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nature of Procurement</label>
                                            <select class="form-select" name="proc_nature" required>
                                                <?php $natures = ['Goods','Infrastructure','Consulting Services','Other Services']; ?>
                                                <option value="" disabled>Select Nature</option>
                                                <?php foreach($natures as $n): ?>
                                                    <option value="<?= $n ?>" <?= ($proc['nature']===$n)?'selected':'' ?>><?= $n ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Approved Budget for the Contract (ABC)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"></span>
                                                <input type="number" step="0.01" class="form-control" name="proc_budget" value="<?= htmlspecialchars($proc['approved_budget']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Pre-Bid Conference</label>
                                                <input type="datetime-local" class="form-control" name="proc_prebid" value="<?= $proc['prebid_datetime'] ? date('Y-m-d\TH:i', strtotime($proc['prebid_datetime'])) : '' ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Deadline for Submission</label>
                                                <input type="datetime-local" class="form-control" name="proc_deadline" value="<?= $proc['deadline_datetime'] ? date('Y-m-d\TH:i', strtotime($proc['deadline_datetime'])) : '' ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Bid Opening</label>
                                                <input type="datetime-local" class="form-control" name="proc_bid_opening" value="<?= $proc['bid_opening_datetime'] ? date('Y-m-d\TH:i', strtotime($proc['bid_opening_datetime'])) : '' ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Lot Table Reference</label>
                                            <input type="text" class="form-control" name="proc_lot_table" value="<?= htmlspecialchars($proc['reference_no'] ?? '') ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="proc_description" rows="3"><?= htmlspecialchars($proc['description'] ?? '') ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Winning Bidder</label>
                                            <input type="text" class="form-control" name="proc_winner" value="<?= htmlspecialchars($proc['winning_bidder'] ?? '') ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="proc_status" required>
                                                <option value="Open" <?= ($proc['status']==='Open')?'selected':'' ?>>Open</option>
                                                <option value="Closed" <?= ($proc['status']==='Closed')?'selected':'' ?>>Closed</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Bidding Document (PDF only)</label>
                                            <input type="file" class="form-control" name="proc_bidding_doc" accept=".pdf">
                                            <?php if(!empty($proc['document_file'])): ?>
                                                <div class="form-text">Current: <?= htmlspecialchars($proc['document_file']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Additional Document (PDF only)</label>
                                            <input type="file" class="form-control" name="proc_additional_doc" accept=".pdf">
                                            <?php if(!empty($proc['additional_doc'])): ?>
                                                <div class="form-text">Current: <?= htmlspecialchars($proc['additional_doc']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="update_procurement" class="btn btn-primary">Update</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-cell">No procurements found.</td>
                    </tr>
                <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <span>Showing <?= count($procurements) ?> of <?= $total_procurements ?> opportunities</span>
                <div class="btn-group" role="group" aria-label="Pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for($p = 1; $p <= $total_pages; $p++): ?>
                        <a href="?page=<?= $p ?>" class="btn btn-sm <?= $p==$page ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD PROCUREMENT MODAL -->
<?php include 'partials/add_procurement_modal.php'; ?>

<!-- DELETE SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('procSearch')?.addEventListener('input', function(){
    const q = this.value.trim().toLowerCase();
    const statusVal = document.getElementById('statusFilter')?.value || '';
    document.querySelectorAll('#procTable tbody tr').forEach(tr => {
        const t = tr.getAttribute('data-title') || '';
        const s = tr.getAttribute('data-status') || '';
        const matchText = !q || t.includes(q);
        const matchStatus = !statusVal || s === statusVal;
        tr.style.display = (matchText && matchStatus) ? '' : 'none';
    });
});
document.getElementById('statusFilter')?.addEventListener('change', function(){
    const statusVal = this.value;
    const q = document.getElementById('procSearch')?.value.trim().toLowerCase() || '';
    document.querySelectorAll('#procTable tbody tr').forEach(tr => {
        const t = tr.getAttribute('data-title') || '';
        const s = tr.getAttribute('data-status') || '';
        const matchText = !q || t.includes(q);
        const matchStatus = !statusVal || s === statusVal;
        tr.style.display = (matchText && matchStatus) ? '' : 'none';
    });
});
</script>
<script>
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const procurementId = this.dataset.id;
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if(result.isConfirmed){
                if (window.showPageLoader) window.showPageLoader();
                fetch('functions/procurementfunctions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'fetch'
                    },
                    body: `action=deleteProcurement&id=${encodeURIComponent(String(procurementId || ''))}`
                })
                .then(async (response) => {
                    let data = null;
                    try {
                        data = await response.json();
                    } catch (e) {
                        data = null;
                    }

                    if (!response.ok || !data || data.status !== 'success') {
                        const msg = (data && data.message) ? String(data.message) : 'Something went wrong!';
                        throw new Error(msg);
                    }

                    return data;
                })
                .then(() => {
                    if (window.hidePageLoader) window.hidePageLoader();
                    Swal.fire('Deleted!', 'The record has been deleted.', 'success')
                        .then(() => location.reload());
                })
                .catch(err => {
                    if (window.hidePageLoader) window.hidePageLoader();
                    Swal.fire('Error', err && err.message ? String(err.message) : 'Something went wrong!', 'error');
                });
            }
        });
    });
});

</script>

<!-- BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
.modal-backdrop { --bs-backdrop-opacity: .18; }
</style>
