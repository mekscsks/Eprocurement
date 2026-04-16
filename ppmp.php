<?php
session_start();
?>
  <link rel="stylesheet" href="assets/css/index.css">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="assets/logo/CSDO.png" type="image/x-icon">

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
    }

    * { box-sizing: border-box; }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--bg);
        color: var(--text);
        margin: 0;
    }

    /* ── BANNER ── */
    .ppmp-banner {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
        padding: 2.25rem 0 3.5rem;
        position: relative;
        overflow: hidden;
    }
    .ppmp-banner::before {
        content: '';
        position: absolute; inset: 0;
        background: repeating-linear-gradient(
            -55deg, transparent, transparent 48px,
            rgba(255,255,255,.025) 48px, rgba(255,255,255,.025) 49px
        );
        pointer-events: none;
    }
    .ppmp-banner::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 40px;
        background: var(--bg);
        clip-path: ellipse(55% 100% at 50% 100%);
    }
    .ppmp-banner-inner {
        position: relative; z-index: 1;
        max-width: 1080px;
        margin: 0 auto;
        padding: 0 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .ppmp-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: rgba(245,166,35,.15);
        border: 1px solid rgba(245,166,35,.3);
        color: var(--sun);
        font-size: .72rem; font-weight: 700;
        letter-spacing: .7px; text-transform: uppercase;
        padding: .25rem .75rem;
        border-radius: 999px;
        margin-bottom: .65rem;
    }
    .ppmp-banner h1 {
        font-family: 'DM Serif Display', serif;
        font-size: 1.85rem;
        color: var(--white);
        margin: 0 0 .3rem;
        line-height: 1.2;
    }
    .ppmp-banner h1 span { color: var(--sun); }
    .ppmp-banner p { font-size: .88rem; color: rgba(255,255,255,.5); margin: 0; }
    .ppmp-banner-badge {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.15);
        color: rgba(255,255,255,.7);
        font-size: .78rem; font-weight: 600;
        padding: .6rem 1.1rem;
        border-radius: 12px;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .ppmp-banner-badge i { color: var(--sun); font-size: .9rem; }

    /* ── MAIN WRAPPER ── */
    .ppmp-main {
        max-width: 1080px;
        margin: -1.5rem auto 3.5rem;
        padding: 0 1.5rem;
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    /* ── CARD SHELL ── */
    .ppmp-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,.06);
    }
    .ppmp-card-head {
        padding: 1.1rem 1.75rem;
        background: linear-gradient(to right, var(--navy), var(--navy-mid));
        border-bottom: 3px solid var(--sun);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ppmp-card-title {
        font-family: 'DM Serif Display', serif;
        font-size: 1.08rem;
        color: var(--white);
        margin: 0;
    }
    .ppmp-card-sub { font-size: .75rem; color: rgba(255,255,255,.45); margin-top: .15rem; }
    .ppmp-card-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: rgba(245,166,35,.15);
        border: 1px solid rgba(245,166,35,.3);
        color: var(--sun);
        font-size: .7rem; font-weight: 700;
        letter-spacing: .5px; text-transform: uppercase;
        padding: .25rem .7rem;
        border-radius: 999px;
        flex-shrink: 0;
    }
    .ppmp-card-body { padding: 1.75rem; }

    /* ── TYPE CARDS ROW ── */
    .ppmp-type-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.1rem;
    }
    .ppmp-type-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,.04);
        transition: box-shadow .2s, transform .2s;
        display: flex;
        flex-direction: column;
    }
    .ppmp-type-card:hover {
        box-shadow: 0 8px 28px rgba(13,43,85,.12);
        transform: translateY(-3px);
    }
    .ppmp-type-icon {
        width: 48px; height: 48px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: .95rem;
        flex-shrink: 0;
    }
    .ppmp-type-tag {
        display: inline-block;
        font-size: .68rem; font-weight: 700;
        letter-spacing: .7px; text-transform: uppercase;
        padding: .22rem .65rem;
        border-radius: 999px;
        margin-bottom: .6rem;
        width: fit-content;
    }
    .ppmp-type-tag.navy  { background: var(--navy-light); color: var(--navy-mid); }
    .ppmp-type-tag.green { background: var(--green-light); color: var(--green); }
    .ppmp-type-tag.gold  { background: var(--sun-light); color: #B45309; }
    .ppmp-type-card h4 {
        font-family: 'DM Serif Display', serif;
        font-size: 1.05rem;
        color: var(--navy);
        margin: 0 0 .8rem;
    }
    .ppmp-type-list {
        list-style: none;
        padding: 0; margin: 0;
        display: flex;
        flex-direction: column;
        gap: .55rem;
        flex: 1;
    }
    .ppmp-type-list li {
        font-size: .83rem;
        color: var(--muted);
        line-height: 1.55;
        padding-left: 1.15rem;
        position: relative;
    }
    .ppmp-type-list li::before {
        content: '';
        position: absolute;
        left: 0; top: .55rem;
        width: 5px; height: 5px;
        border-radius: 50%;
        background: var(--border);
    }

    /* ── PREP SECTION ── */
    .ppmp-prep-intro {
        font-size: .9rem;
        color: var(--muted);
        line-height: 1.75;
        margin: 0 0 1.4rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid var(--border);
    }
    .ppmp-prep-label {
        font-size: .72rem; font-weight: 700;
        letter-spacing: .8px; text-transform: uppercase;
        color: var(--muted);
        margin-bottom: .9rem;
    }
    .ppmp-prep-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .ppmp-prep-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        transition: background .2s, border-color .2s;
    }
    .ppmp-prep-item:hover {
        background: var(--navy-light);
        border-color: var(--navy-mid);
    }
    .ppmp-prep-num {
        font-family: 'DM Serif Display', serif;
        font-size: 1.5rem;
        color: var(--border);
        line-height: 1;
        flex-shrink: 0;
        min-width: 30px;
    }
    .ppmp-prep-item strong {
        display: block;
        font-size: .88rem; font-weight: 700;
        color: var(--navy);
        margin-bottom: .3rem;
    }
    .ppmp-prep-item p {
        font-size: .81rem;
        color: var(--muted);
        margin: 0;
        line-height: 1.6;
    }

    @media (max-width: 860px) {
        .ppmp-type-grid { grid-template-columns: 1fr; }
        .ppmp-prep-grid { grid-template-columns: 1fr; }
        .ppmp-banner-badge { display: none; }
    }
</style>
<!-- ━━━━ NAVBAR ━━━━ -->
<header class="nav-wrap">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">
      <img src="assets/logo/CSDO.png" style="width: 50px;" alt="DepEd Logo">
      <div class="nav-brand-text">
        <div class="nav-brand-title">DepEd – SDO Dasmariñas</div>
        <div class="nav-brand-sub">eProcurement Portal</div>
      </div>
    </a>

    <ul class="nav-links">
      <li><a href="bidding.php" class="nav-link-page">Bidding &amp; Procurement</a></li>
      <li><a href="ppmp.php" class="nav-link-page active-page">PPMP Tool</a></li>
    </ul>

    <div class="nav-cta">
      <a href="/login.php" class="btn-login">Login</a>
      
    </div>
  </div>
</header>
<!-- ── BANNER ── -->
<div class="ppmp-banner">
    <div class="ppmp-banner-inner">
        <div>
            <div class="ppmp-eyebrow"><i class="bi bi-file-earmark-check"></i> Section 7 – RA 9184</div>
            <h1>Project Procurement <span>Management Plan</span></h1>
            <p>Schools Division Office of Dasmariñas &nbsp;·&nbsp; eProcurement Portal &nbsp;·&nbsp; FY 2026</p>
        </div>
        <div class="ppmp-banner-badge">
            <i class="bi bi-calendar-check"></i> Submission Deadline: March 31, 2026
        </div>
    </div>
</div>

<!-- ── MAIN ── -->
<div class="ppmp-main">

    <!-- ── PPMP TYPES ── -->
    <div class="ppmp-type-grid">

        <!-- Indicative PPMP -->
        <div class="ppmp-type-card">
            <div class="ppmp-type-icon" style="background:var(--navy-light);color:var(--navy-mid);">
                <i class="bi bi-calendar-range"></i>
            </div>
            <span class="ppmp-type-tag navy">Type 01</span>
            <h4>Indicative PPMP</h4>
            <ul class="ppmp-type-list">
                <li>Planned procurements for next year</li>
                <li>Non-Common Supplies and Equipment (NCSE) and Infrastructure Projects</li>
                <li>Submitted by the end-users to the Budget/Accounting Offices for evaluation and approval</li>
            </ul>
        </div>

        <!-- Final PPMP -->
        <div class="ppmp-type-card">
            <div class="ppmp-type-icon" style="background:var(--green-light);color:var(--green);">
                <i class="bi bi-patch-check"></i>
            </div>
            <span class="ppmp-type-tag green">Type 02</span>
            <h4>Final PPMP</h4>
            <ul class="ppmp-type-list">
                <li>Revised/Updated IPPMP</li>
                <li>Submitted by the end-users after the approval of the GAA</li>
                <li>Separate form for NCSE and Infrastructure Projects</li>
                <li>Submitted to the Government Procurement Policy Board – January of the succeeding year</li>
            </ul>
        </div>

        <!-- Supplemental PPMP -->
        <div class="ppmp-type-card">
            <div class="ppmp-type-icon" style="background:var(--sun-light);color:#B45309;">
                <i class="bi bi-plus-circle"></i>
            </div>
            <span class="ppmp-type-tag gold">Type 03</span>
            <h4>Supplemental PPMP</h4>
            <ul class="ppmp-type-list">
                <li>Items to be procured that are not included in the Final PPMP</li>
                <li>Separate form for NCSE and Infrastructure Projects</li>
                <li>Submitted as needed within the FY</li>
            </ul>
        </div>

    </div>

    <!-- ── PPMP PREPARATION ── -->
    <div class="ppmp-card">
        <div class="ppmp-card-head">
            <div>
                <div class="ppmp-card-title">PPMP Preparation</div>
                <div class="ppmp-card-sub">Administrative reminders from the BAC Secretariat</div>
            </div>
            <div class="ppmp-card-pill"><i class="bi bi-book"></i> RA 9184</div>
        </div>
        <div class="ppmp-card-body">

            <p class="ppmp-prep-intro">
                With the returned LIB and the list of suggested modes from the UP Procurement Office, the Administrative Officer shall prepare the PPMP or supplemental PPMP, whichever is applicable.
            </p>

            <div class="ppmp-prep-label">Reminders</div>

            <div class="ppmp-prep-grid">
                <div class="ppmp-prep-item">
                    <div class="ppmp-prep-num">01</div>
                    <div>
                        <strong>Use the required format</strong>
                        <p>Use the required format which will be provided by the UP Procurement Office.</p>
                    </div>
                </div>
                <div class="ppmp-prep-item">
                    <div class="ppmp-prep-num">02</div>
                    <div>
                        <strong>Identify specific items</strong>
                        <p>Items or services not covered by R.A. No. 9184 shall not be included in the PPMP or the supplemental PPMP.</p>
                    </div>
                </div>
                <div class="ppmp-prep-item">
                    <div class="ppmp-prep-num">03</div>
                    <div>
                        <strong>Identify the timeline</strong>
                        <p>Identify the timeline for the items to be procured.</p>
                    </div>
                </div>
                <div class="ppmp-prep-item">
                    <div class="ppmp-prep-num">04</div>
                    <div>
                        <strong>Indicate the budget</strong>
                        <p>Budget must be within the approved LIB or IOB.</p>
                    </div>
                </div>
                <div class="ppmp-prep-item">
                    <div class="ppmp-prep-num">05</div>
                    <div>
                        <strong>Review and approval</strong>
                        <p>The prepared PPMP shall be reviewed and approved by the Head of College or Office.</p>
                    </div>
                </div>
                <div class="ppmp-prep-item">
                    <div class="ppmp-prep-num">06</div>
                    <div>
                        <strong>Submit to Procurement Office</strong>
                        <p>Once signed and approved, the PPMP or the supplemental PPMP shall be submitted to the UP Procurement Office.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>