<?php 
ob_start();
if (!isset($_SESSION)) {
    session_start();
}
?>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --sun: #F5A623;
        --sun-light: #FFF8E7;
        --navy: #0D2B55;
        --navy-mid: #1A4080;
        --navy-light: #E8EFF9;
        --green: #006B3C;
        --green-light: #ECFDF5;
        --red: #C0272D;
        --white: #FFFFFF;
        --text: #1A2B40;
        --muted: #5A6A80;
        --border: #D6E1EF;
        --bg: #F4F7FC;
        --sidebar-w: 260px;
    }

    * { box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); margin: 0; }

    /* ── BANNER ── */
    .db-banner {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
        padding: 2.25rem 0 3.5rem;
        position: relative;
        overflow: hidden;
        margin-left: var(--sidebar-w);
    }
    .db-banner::before {
        content: '';
        position: absolute; inset: 0;
        background: repeating-linear-gradient(
            -55deg, transparent, transparent 48px,
            rgba(255,255,255,.025) 48px, rgba(255,255,255,.025) 49px
        );
        pointer-events: none;
    }
    .db-banner::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 40px;
        background: var(--bg);
    }
    .db-banner-inner {
        position: relative; z-index: 1;
        max-width: 1200px; margin: 0 auto;
        padding: 0 1.5rem;
        display: flex; align-items: center;
        justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
    }
    .db-greeting-eyebrow {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(245,166,35,.15);
        border: 1px solid rgba(245,166,35,.3);
        color: var(--sun);
        font-size: .72rem; font-weight: 700;
        letter-spacing: .7px; text-transform: uppercase;
        padding: .25rem .75rem; border-radius: 999px;
        margin-bottom: .65rem;
    }
    .db-greeting h1 {
        font-family: 'DM Serif Display', serif;
        font-size: 1.75rem; color: var(--white);
        margin: 0 0 .3rem; line-height: 1.2;
    }
    .db-greeting h1 span { color: var(--sun); }
    .db-greeting p { font-size: .85rem; color: rgba(255,255,255,.55); margin: 0; }
    .db-banner-meta {
        display: flex; align-items: center; gap: .6rem;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 12px; padding: .65rem 1rem;
    }
    .db-banner-meta .avatar {
        width: 38px; height: 38px; border-radius: 50%;
        background: var(--sun); color: var(--navy);
        font-size: .9rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .db-banner-meta .uname { font-size: .85rem; font-weight: 600; color: var(--white); }
    .db-banner-meta .urole { font-size: .72rem; color: rgba(255,255,255,.45); }
    .db-banner-meta .urole-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        background: rgba(245,166,35,.2);
        border: 1px solid rgba(245,166,35,.35);
        color: var(--sun);
        font-size: .65rem; font-weight: 700;
        letter-spacing: .5px; text-transform: uppercase;
        padding: .15rem .5rem; border-radius: 999px;
        margin-top: .15rem;
    }

    /* ── ADMIN LAYOUT ── */
    .admin-main {
        margin-left: var(--sidebar-w);
        padding: 0 1.5rem 3rem;
        margin-top: -1.5rem;
        position: relative; z-index: 2;
    }

    /* ── STAT CARDS ── */
    .admin-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .admin-stat {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.25rem 1.35rem;
        display: flex; align-items: center; gap: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,.04);
        transition: box-shadow .2s, transform .2s;
        position: relative;
        overflow: hidden;
    }
    .admin-stat::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0;
        height: 3px;
    }
    .admin-stat.blue::before  { background: var(--navy-mid); }
    .admin-stat.green::before { background: var(--green); }
    .admin-stat.gold::before  { background: var(--sun); }
    .admin-stat.red::before   { background: var(--red); }
    .admin-stat:hover { box-shadow: 0 6px 20px rgba(13,43,85,.1); transform: translateY(-2px); }
    .admin-stat-icon {
        width: 48px; height: 48px; border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; flex-shrink: 0;
    }
    .admin-stat-icon.blue  { background: var(--navy-light); color: var(--navy-mid); }
    .admin-stat-icon.green { background: var(--green-light); color: var(--green); }
    .admin-stat-icon.gold  { background: var(--sun-light); color: #B45309; }
    .admin-stat-icon.red   { background: #FEF2F2; color: var(--red); }
    .admin-stat-lbl { font-size: .78rem; color: var(--muted); margin-bottom: .25rem; font-weight: 500; }
    .admin-stat-val { font-family: 'DM Serif Display', serif; font-size: 2rem; color: var(--navy); line-height: 1; }
    .admin-stat-val.green { color: var(--green); }
    .admin-stat-val.gold  { color: #B45309; }
    .admin-stat-val.red   { color: var(--red); }

    /* ── CARD ── */
    .admin-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        margin-bottom: 1.25rem;
    }
    .admin-card-head {
        padding: 1.1rem 1.5rem;
        background: linear-gradient(to right, var(--navy), var(--navy-mid));
        border-bottom: 3px solid var(--sun);
        display: flex; align-items: center; justify-content: space-between;
    }
    .admin-card-title { font-family: 'DM Serif Display', serif; font-size: 1.05rem; color: var(--white); margin: 0; }
    .admin-card-sub   { font-size: .75rem; color: rgba(255,255,255,.45); margin-top: .15rem; }
    .admin-card-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        background: rgba(245,166,35,.15);
        border: 1px solid rgba(245,166,35,.3);
        color: var(--sun);
        font-size: .7rem; font-weight: 700;
        letter-spacing: .5px; text-transform: uppercase;
        padding: .25rem .7rem; border-radius: 999px; white-space: nowrap;
    }
    .admin-card-body { padding: 1.35rem 1.5rem; }

    /* ── TABLE ── */
    .admin-table { width: 100%; border-collapse: collapse; font-size: .87rem; }
    .admin-table thead th {
        background: var(--navy);
        color: rgba(255,255,255,.75);
        font-size: .72rem; font-weight: 700;
        letter-spacing: .6px; text-transform: uppercase;
        padding: .85rem 1.1rem; text-align: left;
    }
    .admin-table tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
    .admin-table tbody tr:last-child { border-bottom: none; }
    .admin-table tbody tr:hover { background: var(--navy-light); }
    .admin-table td { padding: .9rem 1.1rem; vertical-align: middle; color: var(--text); }
    .admin-table .empty-cell { text-align: center; color: var(--muted); padding: 2.5rem 1rem; font-size: .88rem; }

    /* ── STATUS PILLS ── */
    .status-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .71rem; font-weight: 700;
        padding: .25rem .65rem; border-radius: 999px;
        text-transform: uppercase; letter-spacing: .4px;
    }
    .status-pill::before { content:''; width:6px; height:6px; border-radius:50%; flex-shrink:0; }
    .sp-pending  { background:#FFF8E7; color:#B45309; }
    .sp-pending::before  { background: var(--sun); }
    .sp-approved { background: var(--green-light); color: var(--green); }
    .sp-approved::before { background:#22C55E; box-shadow: 0 0 5px #22C55E; }
    .sp-rejected { background:#FEF2F2; color: var(--red); }
    .sp-rejected::before { background: var(--red); }

    /* ── ALERT ITEMS ── */
    .admin-alert-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: .65rem; }
    .admin-alert-item {
        display: flex; align-items: flex-start; gap: .7rem;
        padding: .8rem 1rem; border-radius: 10px;
        font-size: .85rem; line-height: 1.55;
    }
    .admin-alert-item i { font-size: 1rem; flex-shrink: 0; margin-top: .05rem; }
    .admin-alert-item.danger  { background:#FEF2F2; color:var(--red); border:1px solid #FECACA; }
    .admin-alert-item.warning { background:var(--sun-light); color:#92400E; border:1px solid #FDE68A; }
    .admin-alert-item.info    { background:var(--navy-light); color:var(--navy-mid); border:1px solid var(--border); }

    /* ── BUTTON ── */
    .admin-btn {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .6rem 1.35rem; border-radius: 10px;
        background: var(--navy); border: none; color: var(--white);
        font-family: 'DM Sans', sans-serif;
        font-size: .87rem; font-weight: 700;
        cursor: pointer; text-decoration: none;
        transition: all .2s;
    }
    .admin-btn:hover { background: var(--navy-mid); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(13,43,85,.2); color: var(--white); }
    .admin-btn.sm { padding: .45rem 1rem; font-size: .82rem; }

    /* ── TWO COL GRID ── */
    .admin-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }

    /* ── SWAL ── */
    .db-swal-popup   { border-radius: 18px !important; font-family: 'DM Sans', sans-serif !important; }
    .db-swal-confirm { font-family: 'DM Sans', sans-serif !important; font-weight: 700 !important; border-radius: 10px !important; }

    @media (max-width: 991px) {
        .db-banner, .admin-main { margin-left: 0; }
        .admin-stats { grid-template-columns: repeat(2,1fr); }
        .admin-two-col { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
        .admin-stats { grid-template-columns: 1fr 1fr; }
        .db-banner-meta { display: none; }
    }
</style>

<?php if (!defined('CHAT_WIDGET_LOADED')) { define('CHAT_WIDGET_LOADED', true); include_once __DIR__ . '/../../support/chat_widget.php'; } ?>

<!-- ── BANNER ── -->
<div class="db-banner">
    <div class="db-banner-inner">
        <div class="db-greeting">
            <div class="db-greeting-eyebrow">
                <i class="bi bi-speedometer2"></i> Admin Panel
            </div>
            <h1>Welcome back, <span><?= htmlspecialchars($_SESSION['auth_user']['name'] ?? 'Admin'); ?></span></h1>
            <p>Schools Division Office of Dasmariñas &nbsp;·&nbsp; eProcurement Portal &nbsp;·&nbsp; Administrator</p>
        </div>
        <div class="db-banner-meta">
            <div class="avatar"><?= strtoupper(substr($_SESSION['auth_user']['name'] ?? 'A', 0, 1)); ?></div>
            <div>
                <div class="uname"><?= htmlspecialchars($_SESSION['auth_user']['username'] ?? $_SESSION['auth_user']['name'] ?? 'Admin'); ?></div>
                <div class="urole-badge"><i class="bi bi-shield-fill"></i> Administrator</div>
            </div>
        </div>
    </div>
</div>