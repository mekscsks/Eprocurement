<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('../config/localdb.php');
include('includes/auth.php');
include('includes/sidebar.php');
include('includes/header.php');
include_once '../functions/presence.php';

ensurePresenceColumns($con);
// Fetch all users
$query = "SELECT * FROM accounts WHERE role IN ('user','admin') ORDER BY created_at DESC";$result = mysqli_query($con, $query);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<div class="container" style="margin-left: 260px; padding-top: 20px;">
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-info alert-dismissible fade show col-md-10" role="alert">
            <?= $_GET['msg']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="col-md-10">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">User</h4>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                + Add User
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Presence</th>
                            <th>Last Seen</th>
                            <th>Created</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php foreach($result as $row): ?>
                                <?php
                                    $lastSeenRaw = $row['last_seen'] ?? null;
                                    $lastSeenTs = $lastSeenRaw ? strtotime($lastSeenRaw) : false;
                                    $isOnline = (int)($row['is_online'] ?? 0) === 1
                                        && $lastSeenTs
                                        && (time() - $lastSeenTs) <= 300;
                                ?>
                                <tr class="text-center">                                    
                                    <td><?= $row['name']; ?></td>
                                    <td><?= $row['email']; ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-info dropdown-toggle text-dark"
                                                type="button"
                                                data-bs-toggle="dropdown">
                                                <?= ucfirst($row['role']); ?>
                                            </button>

                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item"
                                                    href="userfunctions.php?role=admin&id=<?= $row['account_id']; ?>">
                                                    Admin
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                    href="userfunctions.php?role=user&id=<?= $row['account_id']; ?>">
                                                    User
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($isOnline): ?>
                                            <span class="badge bg-success">Online</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Offline</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $lastSeenTs ? date("M d, Y h:i A", $lastSeenTs) : 'Never'; ?>
                                    </td>
                                    <td>
                                    <?= date("M d, Y", strtotime($row['created_at'])); ?></td>
                                    <td>
                                    <button class="btn btn-sm btn-warning changePasswordBtn"
                                        data-id="<?= $row['account_id']; ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#changePasswordModal">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No Users Found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </div>
             </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="userfunctions.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="account_id" id="account_id">
                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8" placeholder="Enter new password">
                    </div>
                    <div class="mb-3">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8" placeholder="Confirm new password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="userfunctions.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="Enter username">
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="Enter email address">
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8" placeholder="Enter password">
                    </div>
                    <div class="mb-3">
                        <label>Role</label>
                        <select name="role" class="form-select" required>
                            <option value="" disabled selected>Select Role</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const changePasswordBtns = document.querySelectorAll('.changePasswordBtn');
        const accountIdInput = document.getElementById('account_id');

        changePasswordBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                accountIdInput.value = id;
            });
        });
    });
</script>
<?php include('includes/footer.php'); ?>
