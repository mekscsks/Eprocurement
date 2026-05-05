<?php 
if (!isset($_SESSION)) {
    session_start();
}
$current_page = basename(path: $_SERVER['PHP_SELF']);
?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/sidebar.css">

<!-- Mobile hamburger toggle -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
    <i class="bi bi-list"></i>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="admin-sidebar">

    <!-- Logo -->
    <div class="admin-sidebar-logo">
        <img src="/assets/logo/CSDO.png" alt="SDO Logo">
        <div class="admin-sidebar-logo-text">
            SDO <span>Dasmariñas</span><br>eProcurement Portal
        </div>
    </div>

    <!-- Nav -->
    <div class="admin-nav-label">Main Menu</div>

    <a class="admin-nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a class="admin-nav-item <?= ($current_page == 'planning.php') ? 'active' : '' ?>" href="planning.php">
        <i class="bi bi-file-earmark-text"></i> Planning
    </a>
    

    <a class="admin-nav-item <?= ($current_page == 'purchase_requests.php') ? 'active' : '' ?>" href="purchase_requests.php">
        <i class="bi bi-card-checklist"></i> Purchase Request
    </a>
    <a class="admin-nav-item <?= ($current_page == 'suppliers.php') ? 'active' : '' ?>" href="suppliers.php">
        <i class="bi bi-cart"></i> Supplier
    </a>
    <a class="admin-nav-item <?= ($current_page == 'bacreso.php') ? 'active' : '' ?>" href="bacreso.php">
        <i class="bi bi-people"></i> BAC Reso
    </a>
    <a class="admin-nav-item <?= ($current_page == 'rfq.php') ? 'active' : '' ?>" href="rfq.php">
        <i class="bi bi-file-earmark"></i>Request for quotation 
    </a>
    <a class="admin-nav-item <?= ($current_page == 'rta.php') ? 'active' : '' ?>" href="rta.php">
        <i class="bi bi-file-earmark"></i>Resolution to award 
    </a>
      <a class="admin-nav-item <?= ($current_page == 'nta.php') ? 'active' : '' ?>" href="nta.php">
        <i class="bi bi-file-earmark"></i>Notice of Award
    </a>
    <a class="admin-nav-item <?= ($current_page == 'po.php') ? 'active' : '' ?>" href="po.php">
        <i class="bi bi-file-earmark"></i>Purchase Order
    </a>
    <a class="admin-nav-item <?= ($current_page == 'ntp.php') ? 'active' : '' ?>" href="ntp.php">
        <i class="bi bi-file-earmark-check"></i>Notice to Proceed
    </a>

  
    <!-- Logout -->
    <div class="admin-nav-logout">
        <a href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>


</aside>

<script>
(function(){
    var btn = document.getElementById('sidebarToggle');
    var overlay = document.getElementById('sidebarOverlay');
    var sidebar = document.querySelector('.admin-sidebar');
    function close() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    }
    btn.addEventListener('click', function(){
        var isOpen = sidebar.classList.toggle('open');
        overlay.classList.toggle('active', isOpen);
    });
    overlay.addEventListener('click', close);
    document.querySelectorAll('.admin-nav-item').forEach(function(a){
        a.addEventListener('click', close);
    });
})();
</script>
