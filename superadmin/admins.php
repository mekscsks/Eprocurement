<?php
include __DIR__ . '/../config/localdb.php';
include __DIR__ . '/includes/auth.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
include __DIR__ . '/classes/Admin.php';

$adminObj = new Admin($con);
$result = $adminObj->getAll(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admins</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}
</style>
</head>

<body>

<div class="container-fluid">
<div class="row">

<!-- Sidebar -->
<div class="col-md-2 bg-dark text-white min-vh-100 p-3">
    <h5 class="mb-4">Admin</h5>
    <ul class="nav nav-pills flex-column">
        <li class="nav-item"><a class="nav-link active" href="#">Admins</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#">Dashboard</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="col-md-10 p-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>All Admins</h3>
    <input type="text" id="searchInput" class="form-control w-25" placeholder="Search admin...">
</div>

<div class="card shadow-sm">
<div class="card-body">
<div class="table-responsive">

<table class="table table-hover align-middle" id="usersTable">
<thead class="table-light">
<tr>
    <th>#</th>
    <th>User</th>
    <th>Email</th>
    <th>Role</th>
    <th>Status</th>
    <th>Joined</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php
$i = 1;

if ($result && mysqli_num_rows($result) > 0):
while ($row = mysqli_fetch_assoc($result)):
?>
<tr>
    <td><?= $i++; ?></td>

    <td>
        <div class="d-flex align-items-center gap-2">
            <img src="<?= !empty($row['image']) ? '../uploads/'.$row['image'] : 'https://via.placeholder.com/40'; ?>" class="avatar">
            <strong><?= htmlspecialchars($row['name']); ?></strong>
        </div>
    </td>

    <td><?= htmlspecialchars($row['email']); ?></td>

    <td>
        <span class="badge bg-primary text-capitalize">
            <?= htmlspecialchars($row['role']); ?>
        </span>
    </td>

    <td>
        <span class="badge <?= $row['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
            <?= htmlspecialchars($row['status']); ?>
        </span>
    </td>

    <td><?= date('M d, Y', strtotime($row['created_at'])); ?></td>

    <td>
        <a href="user_view.php?id=<?= $row['account_id']; ?>" class="btn btn-sm btn-outline-info">View</a>
        <a href="user_edit.php?id=<?= $row['account_id']; ?>" class="btn btn-sm btn-outline-warning">Edit</a>
    </td>
</tr>
<?php
endwhile;
else:
?>
<tr>
    <td colspan="7" class="text-center text-muted">No admins found</td>
</tr>
<?php endif; ?>
</tbody>

</table>
</div>
</div>
</div>

</div>
</div>
</div>

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">

<form method="POST" action="adminaction.php">
<div class="modal-header">
    <h5 class="modal-title">Create New Admin</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Username</label>
        <input type="text" name="username" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" name="save_admin" class="btn btn-success">Save Admin</button>
</div>
</form>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
