<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || !isset($_SESSION['auth_user'])) {
    header('Location: index.php');
    exit();
}
include 'includes/header.php';
include 'includes/navbar.php';
include 'config/dbcon.php';
include 'functions/profileFunctions.php';


$accountId = $_SESSION['auth_user']['account_id'] ?? 0;

$profile = getProfile($accountId);
$company = getCompany($accountId);

?>


<div class="container py-5">
  <div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Personal Information</h5>
    </div>

    <div class="card-body">
      <div class="row">

        <!-- Left: Avatar + Name -->
        <div class="col-md-3 text-center border-end position-relative">
          <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto avatar-circle"
               style="width:130px;height:130px;font-size:28px;">
            <span id="avatar-text"><?= strtoupper(substr($profile['name'] ?? '?', 0, 1)); ?></span>
          </div>

          <!-- Avatar Edit Icon -->
          <i class="bi bi-pencil-square text-white position-absolute" 
             style="top:100px; left:110px; cursor:pointer; font-size:18px; background:#0d6efd; border-radius:50%; padding:4px;"
             id="edit-avatar"></i>

          <h5 class="mt-3 mb-0 editable-name">
            <span id="name-text"><?= $profile['name'] ?? '-'; ?></span>
            <i class="bi bi-pencil-square ms-2 text-primary edit-icon-name" style="cursor:pointer;"></i>
          </h5>
          <small class="text-muted"><?= $profile['email'] ?? '-'; ?></small>
        </div>

        <!-- Right: Editable Info Fields -->
        <div class="col-md-9">
          <div class="row g-3">

            <?php
            $fields = [
              'department' => 'Department',
              'phone'      => 'Phone',
              'username'   => 'Username',
              'google_id'  => 'Google ID',
              'provider'   => 'Provider'
            ];

            foreach ($fields as $key => $label) :
              $value = $profile[$key] ?? '-';
            ?>
            <div class="col-md-6 d-flex justify-content-between align-items-center mb-2">
              <strong><?= $label ?>:</strong>
              <span class="editable-field" data-field="<?= $key ?>" data-value="<?= $value ?>">
                <?= $value ?>
                <i class="bi bi-pencil-square ms-2 text-primary edit-icon" style="cursor:pointer;"></i>
              </span>
            </div>
            <?php endforeach; ?>

            <!-- Non-editable fields -->
            <div class="col-md-6 d-flex justify-content-between align-items-center mb-2">
              <strong>Role:</strong>
              <span><?= ucfirst($profile['role'] ?? '-'); ?></span>
            </div>
            <div class="col-md-6 d-flex justify-content-between align-items-center mb-2">
              <strong>Status:</strong>
              <span><?= ucfirst($profile['status'] ?? '-'); ?></span>
            </div>
            <div class="col-md-6 d-flex justify-content-between align-items-center mb-2">
              <strong>Created At:</strong>
              <span><?= $profile['created_at'] ?? '-'; ?></span>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>



<div class="container pb-5">
  <div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Company Information</h5>
      <a href="edit-company.php" class="btn btn-link fw-semibold">
        <i class="bi bi-pencil-square me-1"></i> Edit
      </a>
    </div>

    <div class="card-body">
      <div class="row">
        <!-- Left: Avatar -->
        <div class="col-md-3 text-center border-end">
          <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto"
               style="width:130px;height:130px;font-size:28px;">
            <?= strtoupper(substr($company['company_name'] ?? '?', 0, 1)); ?>
          </div>
          <h5 class="mt-3 mb-0"><?= $company['company_name'] ?? '-'; ?></h5>
          <small class="text-muted"><?= $company['company_email'] ?? '-'; ?></small>
        </div>

        <!-- Right: Company Info -->
        <div class="col-md-9">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="d-flex justify-content-between">
                <strong>Name:</strong>
                <span><?= $company['company_name'] ?? '-'; ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <strong>Email:</strong>
                <span><?= $company['company_email'] ?? '-'; ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <strong>Owner:</strong>
                <span><?= $profile['name'] ?? '-'; ?></span>
              </div>
            </div>

            <div class="col-md-6">
              <div class="d-flex justify-content-between">
                <strong>Mobile Number:</strong>
                <span><?= $company['mobile'] ?? '-'; ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <strong>Website:</strong>
                <span>
                  <a href="<?= $company['website'] ?? '#'; ?>" target="_blank">
                    <?= $company['website'] ?? '-'; ?>
                  </a>
                </span>
              </div>
              <div class="d-flex justify-content-between">
                <strong>Address:</strong>
                <span><?= $company['address'] ?? '-'; ?></span>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Name / Avatar Inline Edit ---
const avatarText = document.getElementById('avatar-text');
const nameText = document.getElementById('name-text');
const editAvatarBtn = document.getElementById('edit-avatar');

function makeNameEditable() {
  const currentName = nameText.textContent.trim();
  const input = document.createElement('input');
  input.type = 'text';
  input.value = currentName;
  input.classList.add('form-control', 'form-control-sm');
  input.style.width = 'auto';

  const saveBtn = document.createElement('button');
  saveBtn.textContent = 'Save';
  saveBtn.classList.add('btn', 'btn-sm', 'btn-success', 'ms-2');

  nameText.parentElement.innerHTML = '';
  nameText.parentElement.appendChild(input);
  nameText.parentElement.appendChild(saveBtn);

  saveBtn.addEventListener('click', function() {
    const newName = input.value.trim();
    if (newName === '') return alert('Name cannot be empty');

    fetch('functions/update-profile.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `field=name&value=${encodeURIComponent(newName)}`
    })
    .then(res => res.text())
    .then(data => {
      avatarText.textContent = newName.charAt(0).toUpperCase();
      nameText.textContent = newName;
      nameText.parentElement.innerHTML = newName + 
        ' <i class="bi bi-pencil-square ms-2 text-primary edit-icon-name" style="cursor:pointer;"></i>';
      nameText.parentElement.querySelector('.edit-icon-name').addEventListener('click', makeNameEditable);
    })
    .catch(err => alert('Error updating name'));
  });
}

editAvatarBtn.addEventListener('click', makeNameEditable);
document.querySelectorAll('.edit-icon-name').forEach(icon => {
  icon.addEventListener('click', makeNameEditable);
});

// --- Other Editable Fields ---
document.querySelectorAll('.edit-icon').forEach(icon => {
  icon.addEventListener('click', function() {
    const span = this.parentElement;
    const field = span.dataset.field;
    const value = span.dataset.value;

    const input = document.createElement('input');
    input.type = 'text';
    input.value = value;
    input.classList.add('form-control', 'form-control-sm');
    input.style.width = 'auto';

    const saveBtn = document.createElement('button');
    saveBtn.textContent = 'Save';
    saveBtn.classList.add('btn', 'btn-sm', 'btn-success', 'ms-2');

    span.innerHTML = '';
    span.appendChild(input);
    span.appendChild(saveBtn);

    saveBtn.addEventListener('click', function() {
      const newValue = input.value.trim();
      if (newValue === '') return alert('Value cannot be empty');

      fetch('functions/update-profile.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `field=${field}&value=${encodeURIComponent(newValue)}`
      })
      .then(res => res.text())
      .then(data => {
        span.innerHTML = newValue + ' <i class="bi bi-pencil-square ms-2 text-primary edit-icon" style="cursor:pointer;"></i>';
        span.querySelector('.edit-icon').addEventListener('click', arguments.callee);
      })
      .catch(err => alert('Error updating field'));
    });
  });
});
</script>
