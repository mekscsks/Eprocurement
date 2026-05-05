<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/localdb.php';
include 'functions/authorization.php';
include 'functions/userfunctions.php';
include 'includes/header.php';
include 'includes/sidebar.php';



$searchTerm   = $_GET['search'] ?? '';
$searchOffice = $_GET['office'] ?? '';


$ppmp = getPPMP($con, [
    'office' => $_GET['office'] ?? '',
    'search' => $_GET['search'] ?? '',
    'page'   => $_GET['page'] ?? 1,
    'limit'  => 10
]);

$query_run   = $ppmp['result'];
$total_pages = $ppmp['total_pages'];
$page        = $ppmp['page'];

?>
<div class="admin-page">
  <div class="card shadow">

    <!-- HEADER -->
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">PPMP Submissions</h5>
    </div>

    <!-- FILTERS -->
    <div class="card-body">

      <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
          <input type="text" name="search" class="form-control"
                 placeholder="Search email or PPMP type"
                 value="<?= htmlspecialchars($searchTerm); ?>">
        </div>

        <div class="col-md-3">
          <select name="office" class="form-select">
            <option value="">All Offices</option>
            <option value="Admin" <?= ($searchOffice=='Admin')?'selected':''; ?>>Admin</option>
            <option value="Finance" <?= ($searchOffice=='Finance')?'selected':''; ?>>Finance</option>
            <option value="HR" <?= ($searchOffice=='HR')?'selected':''; ?>>HR</option>
          </select>
        </div>

        <div class="col-md-2">
          <button class="btn btn-dark w-100">Filter</button>
        </div>
      </form>

      <!-- TABLE -->
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>Email</th>
              <th>PPMP Type</th>
              <th>Office</th>
              <th>File</th>
              <th>Status</th>
              <th>Action</th>
              <th>Created At</th>
            </tr>
          </thead>

          <tbody>
          <?php if ($query_run && mysqli_num_rows($query_run) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query_run)): ?>
              <tr>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= htmlspecialchars($row['ppmp_type']); ?></td>
                <td><?= htmlspecialchars($row['office']); ?></td>
                <td>
                    <a href="#"
                      class="btn btn-sm btn-primary viewExcelBtn"
                      data-file="<?= urlencode($row['file_path']); ?>"
                      data-bs-toggle="modal"
                      data-bs-target="#excelModal">
                      View
                    </a>
                    <a href="/<?= htmlspecialchars($row['file_path']); ?>" download class="btn btn-sm btn-success">Download</a>
                </td>
                <td>
                  <?php
                    $status = $row['status'] ?? 'pending';
                    $badge  = match($status) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'warning',
                    };
                  ?>
                  <span class="badge bg-<?= $badge; ?>">
                    <?= ucfirst($status); ?>
                  </span>
                </td>
                <td>
                    <?php
                      $status = $row['status'] ?? 'pending';
                      $badge  = match($status) {
                          'approved'     => 'success',
                          'rejected'     => 'danger',
                          'supplemental' => 'info',
                          default        => 'warning',
                      };
                    ?>
                    <span class="badge bg-<?= $badge; ?>">
                      <?= ucfirst($status); ?>
                    </span>
                    </td>

                    <td>
                    <?php if ($status !== 'approved'): ?>
                      <form method="POST" action="code.php" class="d-inline">
                        <input type="hidden" name="ppmp_id" value="<?= $row['id']; ?>">
                        <input type="hidden" name="status" value="approved">
                        <button type="button" class="btn btn-sm btn-success approveBtn">
                            Approve
                        </button>
                      </form>

                      <form method="POST" action="code.php" class="d-inline">
                        <input type="hidden" name="ppmp_id" value="<?= $row['id']; ?>">
                        <input type="hidden" name="status" value="rejected">
                        <button type="button" class="btn btn-sm btn-danger rejectBtn">
                          Disapprove
                        </button>
                      </form>

                      <form method="POST" action="code.php" class="d-inline">
                        <input type="hidden" name="ppmp_id" value="<?= $row['id']; ?>">
                        <input type="hidden" name="status" value="supplemental">
                        <button type="button" class="btn btn-sm btn-info supplementalBtn">
                          Needs Supplemental
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted fst-italic">Locked</span>
                    <?php endif; ?>
                    </td>

                <td><?= date('M d, Y', strtotime($row['created_at'])); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center">No records found</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION -->
      <?php if ($total_pages > 1): ?>
      <nav>
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
              <a class="page-link"
                 href="?page=<?= $i; ?>&search=<?= urlencode($searchTerm); ?>&office=<?= urlencode($searchOffice); ?>">
                <?= $i; ?>
              </a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
      <?php endif; ?>

    </div>
  </div>
</div>
<!-- Excel Preview Modal -->
<div class="modal fade" id="excelModal" tabindex="-1" aria-labelledby="excelModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="excelModalLabel">PPMP Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <iframe id="excelPreviewFrame" src="" width="100%" height="600px" style="border: none;"></iframe>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('excelModal');
    const iframe = document.getElementById('excelPreviewFrame');

    document.querySelectorAll('.viewExcelBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const filePath = btn.dataset.file;

            // Use Google Docs Viewer to preview Excel
            iframe.src = "https://docs.google.com/gview?url=" + encodeURIComponent(window.location.origin + '/' + filePath) + "&embedded=true";
        });
    });

    // Clear iframe on modal close
    modal.addEventListener('hidden.bs.modal', function () {
        iframe.src = '';
    });
});
</script>
<script>
document.querySelectorAll('.approveBtn').forEach(btn => {
    btn.addEventListener('click', function () {
        const form = this.closest('form');

        Swal.fire({
            title: 'Approve PPMP?',
            text: 'Once approved, this PPMP will be locked.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, approve it'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
// Reject confirmation
document.querySelectorAll('.rejectBtn').forEach(btn => {
    btn.addEventListener('click', function () {
        const form = this.closest('form');

        Swal.fire({
            title: 'Reject PPMP?',
            text: 'This PPMP will be marked as rejected.',
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, reject it'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
// Supplemental confirmation
document.querySelectorAll('.supplementalBtn').forEach(btn => {
    btn.addEventListener('click', function () {
        const form = this.closest('form');

        Swal.fire({
            title: 'Needs Supplemental?',
            text: 'This PPMP will require additional documents.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, request supplemental'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($_GET['success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Success!',
    text: 'PPMP status updated successfully.',
    timer: 2000,
    showConfirmButton: false
});
</script>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'locked'): ?>
<script>
Swal.fire({
    icon: 'warning',
    title: 'Locked!',
    text: 'This PPMP is already approved and cannot be modified.',
});
</script>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Invalid Action',
    text: 'Invalid status selected.',
});
</script>
<?php endif; ?>
