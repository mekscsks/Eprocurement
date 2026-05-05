<div class="bg-dark text-white vh-100 p-3 position-fixed" style="width:260px;">
    <h5 class="text-center">SUPERADMIN</h5>
    <hr class="bg-white">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="index.php" class="nav-link text-white">📊 Dashboard</a>
        </li>
        <li class="nav-item">
            <a href="admins.php" class="nav-link text-white">🛠 Manage Admins</a>
        </li>
        <li class="nav-item">
            <a href="user.php" class="nav-link text-white">👥 Manage Users</a>
        </li>
        <li class="nav-item">
            <a href="system_config.php" class="nav-link text-white">⚙️ System Config</a>
        </li>
        <li class="nav-item">
            <a href="audit_logs.php" class="nav-link text-white">📋 Audit Logs</a>
        </li>
        <li class="nav-item">
            <a href="support.php" class="nav-link text-white d-flex justify-content-between align-items-center">🎧 Support
                <span id="supportBadge" class="badge bg-danger rounded-pill" style="display:none;"></span>
            </a>
        </li>
        <li class="nav-item mt-3">
            <a href="../logout.php" class="nav-link text-danger">🚪 Logout</a>
        </li>
    </ul>
</div>
<script>
function checkSupportUnread() {
    fetch('support/fetch_threads.php')
        .then(r => r.json())
        .then(data => {
            const total = (data.threads || []).reduce((s, t) => s + parseInt(t.unread_count || 0), 0);
            const badge = document.getElementById('supportBadge');
            if (!badge) return;
            if (total > 0) { badge.textContent = total > 99 ? '99+' : total; badge.style.display = ''; }
            else { badge.style.display = 'none'; }
        }).catch(() => {});
}
checkSupportUnread();
setInterval(checkSupportUnread, 10000);
</script>
