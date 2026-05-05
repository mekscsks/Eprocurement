<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/localdb.php';
include 'functions/authorization.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'functions/purchasefunctions.php';
include 'functions/po_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_po':
                $data = [
                    'pr_id' => $_POST['pr_id'] ?? null,
                    'supplier_name' => $_POST['supplier_name'],
                    'supplier_address' => $_POST['supplier_address'],
                    'po_date' => $_POST['po_date'],
                    'delivery_date' => $_POST['delivery_date'],
                    'delivery_terms' => $_POST['delivery_terms'],
                    'total_amount' => $_POST['total_amount'],
                    'items' => [],
                ];

                if (isset($_POST['item_description']) && is_array($_POST['item_description'])) {
                    for ($i = 0; $i < count($_POST['item_description']); $i++) {
                        if (!empty($_POST['item_description'][$i])) {
                            $data['items'][] = [
                                'description' => $_POST['item_description'][$i],
                                'quantity' => $_POST['quantity'][$i],
                                'unit' => $_POST['unit'][$i],
                                'unit_cost' => $_POST['unit_cost'][$i],
                                'total_cost' => $_POST['quantity'][$i] * $_POST['unit_cost'][$i],
                            ];
                        }
                    }
                }

                createPO($con, $data);
                break;

            case 'edit_po':
                $editData = [
                    'po_number' => $_POST['po_number'],
                    'supplier_name' => $_POST['supplier_name'],
                    'supplier_address' => $_POST['supplier_address'],
                    'tin' => $_POST['tin'] ?? '',
                    'po_date' => $_POST['po_date'],
                    'mode_of_procurement' => $_POST['mode_of_procurement'] ?? '',
                    'place_of_delivery' => $_POST['place_of_delivery'] ?? '',
                    'delivery_date' => $_POST['delivery_date'] ?? '',
                    'delivery_terms' => $_POST['delivery_terms'] ?? '',
                    'payment_term' => $_POST['payment_term'] ?? '',
                    'fund_cluster' => $_POST['fund_cluster'] ?? '',
                    'ors_burs_number' => $_POST['ors_burs_number'] ?? '',
                    'fund_available' => $_POST['fund_available'] ?? '',
                    'date_ors_burs' => $_POST['date_ors_burs'] ?? '',
                    'conforme_name' => $_POST['conforme_name'] ?? '',
                    'total_amount' => $_POST['total_amount'],
                    'items' => [],
                ];

                if (isset($_POST['item_description']) && is_array($_POST['item_description'])) {
                    for ($i = 0; $i < count($_POST['item_description']); $i++) {
                        if (!empty($_POST['item_description'][$i])) {
                            $editData['items'][] = [
                                'stock_property_no' => $_POST['stock_property_no'][$i] ?? '',
                                'description' => $_POST['item_description'][$i],
                                'quantity' => $_POST['quantity'][$i],
                                'unit' => $_POST['unit'][$i],
                                'unit_cost' => $_POST['unit_cost'][$i],
                            ];
                        }
                    }
                }

                updatePO($con, (int)$_POST['po_id'], $editData);
                break;

            case 'update_status':
                updatePOStatus($con, (int)$_POST['po_id'], $_POST['status']);
                $redirect = ($_POST['status'] === 'approved') ? 'ntp.php' : 'po.php';
                header('Location: ' . $redirect);
                exit;

            case 'delete_po':
                deletePO($con, $_POST['po_id']);
                break;
        }

        header('Location: po.php');
        exit;
    }
}

$purchaseOrders = getAllPO($con);
$suppliers = getAllSuppliers($con);
$purchaseRequests = getAllPR($con);
$readyPurchaseRequests = getApprovedPRsReadyForPO($con);
$readyPurchaseRequestDetails = [];
foreach ($readyPurchaseRequests as $readyPr) {
    $prId = (int)($readyPr['id'] ?? 0);
    if ($prId <= 0) continue;
    $fullPr = getPRWithItems($con, $prId);
    if (!$fullPr) continue;
    $items = [];
    foreach (($fullPr['items'] ?? []) as $item) {
        $description = trim((string)($item['item_description'] ?? $item['description'] ?? $item['item_name'] ?? ''));
        $quantity = (float)($item['quantity'] ?? 0);
        $unit = trim((string)($item['unit'] ?? ''));
        $unitCost = (float)($item['unit_cost'] ?? 0);
        if ($unitCost <= 0 && isset($item['total_cost']) && $quantity > 0) {
            $unitCost = (float)$item['total_cost'] / $quantity;
        }
        if ($unitCost <= 0 && isset($item['estimated_cost']) && $quantity > 0) {
            $unitCost = (float)$item['estimated_cost'] / $quantity;
        }
        $items[] = [
            'description' => $description,
            'quantity' => $quantity > 0 ? $quantity : '',
            'unit' => $unit,
            'unit_cost' => $unitCost > 0 ? $unitCost : '',
        ];
    }
    $readyPurchaseRequestDetails[] = [
        'id' => $prId,
        'pr_number' => (string)($fullPr['pr_number'] ?? ''),
        'requested_by' => (string)($fullPr['requested_by'] ?? ''),
        'office' => (string)($fullPr['office'] ?? ''),
        'fund_source' => (string)($fullPr['fund_source'] ?? ''),
        'total_amount' => (float)($fullPr['total_amount'] ?? 0),
        'items' => $items,
    ];
}

$totalPOs = count($purchaseOrders);
$pendingPOs = count(array_filter($purchaseOrders, fn($po) => ($po['status'] ?? '') === 'pending'));
$approvedPOs = count(array_filter($purchaseOrders, fn($po) => ($po['status'] ?? '') === 'approved'));
$deliveredPOs = count(array_filter($purchaseOrders, fn($po) => ($po['status'] ?? '') === 'delivered'));
$totalPOValue = array_sum(array_map(fn($po) => (float)($po['total_amount'] ?? 0), $purchaseOrders));
?>

<style>
    .po-main {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .po-hero {
        background:
            radial-gradient(circle at top right, rgba(245, 166, 35, 0.16), transparent 32%),
            linear-gradient(135deg, #0d2b55 0%, #1a4080 100%);
        border-radius: 18px;
        padding: 1.6rem 1.75rem;
        color: var(--white);
        position: relative;
        overflow: hidden;
        box-shadow: 0 14px 36px rgba(13, 43, 85, 0.16);
    }

    .po-hero::after {
        content: '';
        position: absolute;
        inset: auto -40px -60px auto;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
    }

    .po-hero-top,
    .po-toolbar {
        position: relative;
        z-index: 1;
    }

    .po-hero-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
    }

    .po-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: rgba(245, 166, 35, 0.16);
        border: 1px solid rgba(245, 166, 35, 0.3);
        color: var(--sun);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
        padding: .28rem .75rem;
        border-radius: 999px;
        margin-bottom: .75rem;
    }

    .po-title {
        font-family: 'DM Serif Display', serif;
        font-size: 2rem;
        line-height: 1.1;
        margin: 0;
        color: var(--white);
    }

    .po-subtitle {
        margin: .45rem 0 0;
        color: rgba(255, 255, 255, 0.72);
        max-width: 700px;
    }

    .po-hero-note {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.86);
        border-radius: 12px;
        padding: .7rem .9rem;
        font-size: .82rem;
        white-space: nowrap;
    }

    .po-stat-label {
        font-size: .78rem;
        color: var(--muted);
        margin-bottom: .2rem;
        font-weight: 600;
    }

    .po-stat-value {
        font-family: 'DM Serif Display', serif;
        font-size: 1.85rem;
        color: var(--navy);
        line-height: 1;
    }

    .po-stat-value.small {
        font-size: 1.45rem;
    }

    .po-card {
        border-radius: 18px;
    }

    .po-ready-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1rem;
    }

    .po-ready-item {
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1rem;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        box-shadow: 0 4px 16px rgba(13, 43, 85, 0.05);
    }

    .po-ready-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .75rem;
        margin-bottom: .75rem;
    }

    .po-ready-pr {
        font-weight: 700;
        color: var(--navy);
        margin-bottom: .2rem;
    }

    .po-ready-meta {
        color: var(--muted);
        font-size: .84rem;
        line-height: 1.5;
    }

    .po-ready-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: var(--green-light);
        color: var(--green);
        border: 1px solid #b7e4ce;
        border-radius: 999px;
        padding: .22rem .65rem;
        font-size: .7rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .po-ready-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .po-ready-amount {
        font-weight: 700;
        color: var(--green);
    }

    .po-ready-trigger {
        width: 100%;
    }

    .po-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .po-search {
        flex: 1;
        min-width: 260px;
        max-width: 420px;
        position: relative;
    }

    .po-search i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
    }

    .po-search input {
        width: 100%;
        border: 1px solid var(--border);
        background: #f9fbfe;
        color: var(--text);
        border-radius: 999px;
        padding: .82rem 1rem .82rem 2.7rem;
        font-size: .92rem;
        transition: border-color .2s, box-shadow .2s, background .2s;
    }

    .po-search input:focus {
        outline: none;
        border-color: var(--navy-mid);
        box-shadow: 0 0 0 4px rgba(26, 64, 128, 0.08);
        background: var(--white);
    }

    .po-btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: .84rem;
        padding: .72rem 1.1rem;
        text-decoration: none;
        transition: transform .18s, box-shadow .18s, background .18s, color .18s;
        background: linear-gradient(135deg, var(--green), #0b8f58);
        color: var(--white);
        box-shadow: 0 8px 18px rgba(0, 107, 60, 0.18);
    }

    .po-btn-primary:hover {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #075f37, #0b8f58);
        color: var(--white);
    }

    .po-table-wrap {
        overflow-x: auto;
    }

    .po-table th {
        white-space: nowrap;
    }

    .po-number {
        font-weight: 700;
        color: var(--navy);
    }

    .po-supplier {
        display: flex;
        flex-direction: column;
        gap: .18rem;
    }

    .po-supplier strong {
        color: var(--text);
    }

    .po-supplier small {
        color: var(--muted);
    }

    .po-amount {
        font-weight: 700;
        color: var(--green);
        white-space: nowrap;
    }

    .status-pill.sp-delivered {
        background: #e0f2fe;
        color: #0c4a6e;
    }

    .status-pill.sp-delivered::before {
        background: #0284c7;
    }

    .status-pill.sp-cancelled {
        background: #fef2f2;
        color: var(--red);
    }

    .status-pill.sp-cancelled::before {
        background: var(--red);
    }

    .po-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }

    .po-action-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        transition: transform .18s, filter .18s, box-shadow .18s;
    }

    .po-action-btn:hover {
        transform: translateY(-1px);
        filter: brightness(0.98);
        color: var(--white);
    }

    .po-action-download { background: var(--navy-mid); }
    .po-action-edit { background: #b45309; }
    .po-action-approve { background: var(--green); }
    .po-action-deliver { background: #0284c7; }
    .po-action-cancel { background: #d97706; }
    .po-action-delete { background: var(--red); }

    .po-empty {
        text-align: center;
        padding: 3.25rem 1.5rem;
    }

    .po-empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        margin: 0 auto 1rem;
        background: var(--navy-light);
        color: var(--navy-mid);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }

    .po-empty h3 {
        margin: 0 0 .45rem;
        font-size: 1.1rem;
        color: var(--navy);
    }

    .po-empty p {
        margin: 0 0 1.25rem;
        color: var(--muted);
    }

    .modal-content {
        border: 1px solid var(--border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 22px 50px rgba(13, 43, 85, 0.18);
    }

    .modal-dialog {
        margin: 1rem auto;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--navy), var(--navy-mid));
        color: var(--white);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 1rem 1.25rem;
    }

    .modal-body {
        background: #f6f9fd;
        overflow-y: auto;
    }

    .modal-footer {
        background: #f6f9fd;
        border-top: 1px solid var(--border);
        padding: 1rem 1.25rem;
        position: sticky;
        bottom: 0;
        z-index: 2;
    }

    #createPOModal .modal-dialog,
    #editPOModal .modal-dialog {
        height: calc(100vh - 2rem);
    }

    #createPOModal .modal-content,
    #editPOModal .modal-content {
        max-height: 100%;
    }

    #createPOModal .modal-body,
    #editPOModal .modal-body {
        max-height: calc(100vh - 180px);
    }

    .po-form-section {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1rem;
        box-shadow: 0 2px 10px rgba(13, 43, 85, 0.04);
    }

    .po-form-section + .po-form-section {
        margin-top: 1rem;
    }

    .po-form-section-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: 0 0 1rem;
        color: var(--navy);
        font-size: .95rem;
        font-weight: 700;
    }

    .form-control,
    .form-select,
    .input-group-text {
        border-color: var(--border);
        border-radius: 10px;
        min-height: 44px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--navy-mid);
        box-shadow: 0 0 0 4px rgba(26, 64, 128, 0.08);
    }

    textarea.form-control {
        min-height: auto;
    }

    .input-group .form-control {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .item-row {
        background: #fbfdff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1rem;
        margin-bottom: .75rem;
    }

    .po-items-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .btn-add-item,
    .btn-remove-item {
        border: none;
        border-radius: 10px;
        color: var(--white);
        font-size: .82rem;
        font-weight: 700;
        padding: .66rem .95rem;
        transition: transform .18s, filter .18s;
    }

    .btn-add-item {
        background: var(--navy-mid);
    }

    .btn-remove-item {
        background: var(--red);
    }

    .btn-add-item:hover,
    .btn-remove-item:hover {
        transform: translateY(-1px);
        filter: brightness(0.98);
    }

    .po-total-box {
        background: linear-gradient(135deg, #ecfdf5, #f8fafc);
        border: 1px solid #cce9db;
        border-radius: 14px;
        padding: 1rem;
    }

    @media (max-width: 991px) {
        .po-hero {
            padding: 1.25rem;
        }

        .po-title {
            font-size: 1.65rem;
        }

        .po-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .po-search {
            max-width: none;
        }

        #createPOModal .modal-dialog,
        #editPOModal .modal-dialog {
            max-width: calc(100vw - 1rem);
            margin: .5rem auto;
            height: calc(100vh - 1rem);
        }

        #createPOModal .modal-body,
        #editPOModal .modal-body {
            padding: 1rem;
            max-height: calc(100vh - 170px);
        }

        .po-form-section {
            padding: .9rem;
        }
    }

    @media (max-width: 600px) {
        .po-actions {
            min-width: 160px;
        }

        .po-action-btn {
            width: 32px;
            height: 32px;
        }

        #createPOModal .modal-dialog,
        #editPOModal .modal-dialog {
            max-width: calc(100vw - .5rem);
            margin: .25rem auto;
            height: calc(100vh - .5rem);
        }

        #createPOModal .modal-header,
        #createPOModal .modal-footer,
        #editPOModal .modal-header,
        #editPOModal .modal-footer {
            padding: .85rem 1rem;
        }

        #createPOModal .modal-title,
        #editPOModal .modal-title {
            font-size: 1rem;
            line-height: 1.3;
            padding-right: .5rem;
        }

        #createPOModal .modal-body,
        #editPOModal .modal-body {
            padding: .85rem;
            max-height: calc(100vh - 155px);
        }

        .po-form-section {
            padding: .8rem;
            border-radius: 12px;
        }

        .po-form-section-title {
            font-size: .88rem;
            align-items: flex-start;
        }

        .po-items-head {
            flex-direction: column;
            align-items: stretch;
        }

        .po-items-head .btn-add-item {
            width: 100%;
            justify-content: center;
        }

        #createPOModal .item-row,
        #editPOModal .item-row {
            padding: .85rem;
        }

        #createPOModal .item-row .row,
        #editPOModal .item-row .row {
            row-gap: .75rem !important;
        }

        #createPOModal .item-row [class*="col-"],
        #editPOModal .item-row [class*="col-"] {
            width: 100%;
            flex: 0 0 100%;
            max-width: 100%;
        }

        #createPOModal .btn-remove-item,
        #editPOModal .btn-remove-item {
            width: 100%;
            justify-content: center;
        }

        #createPOModal .modal-footer,
        #editPOModal .modal-footer {
            flex-direction: column;
        }

        #createPOModal .modal-footer > *,
        #editPOModal .modal-footer > * {
            width: 100%;
            justify-content: center;
        }

        #createPOModal .input-group,
        #editPOModal .input-group {
            flex-wrap: nowrap;
        }
    }
</style>

<main class="admin-main po-main">
    <?php showAlert(); ?>

    <?php if (!empty($readyPurchaseRequests)): ?>
    <section class="admin-card po-card">
        <div class="admin-card-head">
            <div>
                <h2 class="admin-card-title">Approved BAC Resolutions Ready for PO</h2>
                <div class="admin-card-sub">These approved purchase requests can now be converted into purchase orders.</div>
            </div>
            <div class="admin-card-pill">
                <i class="bi bi-arrow-right-circle"></i>
                <?= count($readyPurchaseRequests) ?> Ready
            </div>
        </div>
        <div class="admin-card-body">
            <div class="po-ready-list">
                <?php foreach ($readyPurchaseRequests as $readyPr): ?>
                    <div class="po-ready-item">
                        <div class="po-ready-top">
                            <div>
                                <div class="po-ready-pr"><?= htmlspecialchars($readyPr['pr_number'] ?? 'PR') ?></div>
                                <div class="po-ready-meta">
                                    <?= htmlspecialchars($readyPr['office'] ?? '-') ?><br>
                                    Requested by <?= htmlspecialchars($readyPr['requested_by'] ?? '-') ?>
                                </div>
                            </div>
                            <span class="po-ready-pill">
                                <i class="bi bi-check-circle-fill"></i>
                                Approved
                            </span>
                        </div>
                        <div class="po-ready-meta">Fund source: <?= htmlspecialchars($readyPr['fund_source'] ?? '-') ?></div>
                        <div class="po-ready-bottom">
                            <div class="po-ready-amount">&#8369;<?= number_format((float)($readyPr['total_amount'] ?? 0), 2) ?></div>
                            <button
                                type="button"
                                class="po-btn-primary po-ready-trigger"
                                onclick="openCreatePOFromPR(<?= (int)$readyPr['id'] ?>)"
                            >
                                <i class="bi bi-file-earmark-plus"></i>
                                Create PO From This PR
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <section class="admin-card po-card">
        <div class="admin-card-head">
            <div>
                <h2 class="admin-card-title">PO Registry</h2>
                <div class="admin-card-sub">Search, download, edit, approve, or archive purchase orders from one table.</div>
            </div>
            <div class="admin-card-pill">
                <i class="bi bi-journal-check"></i>
                <?= $approvedPOs ?> Approved
            </div>
        </div>
        <div class="admin-card-body">
            <div class="po-toolbar">
                <div class="po-search">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search by PO number or supplier..." id="searchInput">
                </div>
                <button type="button" class="po-btn-primary" data-bs-toggle="modal" data-bs-target="#createPOModal">
                    <i class="bi bi-plus-circle"></i>
                    Create New PO
                </button>
            </div>

            <div class="po-table-wrap mt-4">
                <table class="admin-table po-table">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>PO Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="poTableBody">
                        <?php if (empty($purchaseOrders)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="po-empty">
                                        <div class="po-empty-icon"><i class="bi bi-inbox"></i></div>
                                        <h3>No Purchase Orders Yet</h3>
                                        <p>Start the registry by creating the first purchase order record.</p>
                                        <button type="button" class="po-btn-primary" data-bs-toggle="modal" data-bs-target="#createPOModal">
                                            <i class="bi bi-plus-circle"></i>
                                            Create First PO
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($purchaseOrders as $po): ?>
                                <?php
                                    $status = strtolower((string)($po['status'] ?? 'pending'));
                                    $statusClass = match ($status) {
                                        'approved' => 'sp-approved',
                                        'delivered' => 'sp-delivered',
                                        'cancelled' => 'sp-cancelled',
                                        'rejected' => 'sp-rejected',
                                        default => 'sp-pending',
                                    };
                                ?>
                                <tr class="po-row" data-search="<?= strtolower(($po['po_number'] ?? '') . ' ' . ($po['supplier_name'] ?? '')) ?>">
                                    <td><span class="po-number"><?= htmlspecialchars($po['po_number']) ?></span></td>
                                    <td>
                                        <div class="po-supplier">
                                            <strong><?= htmlspecialchars($po['supplier_name']) ?></strong>
                                            <?php if (!empty($po['supplier_address'])): ?>
                                                <small><?= htmlspecialchars(mb_strimwidth($po['supplier_address'], 0, 60, '...')) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($po['po_date'])) ?></td>
                                    <td><span class="po-amount">&#8369;<?= number_format((float)$po['total_amount'], 2) ?></span></td>
                                    <td>
                                        <span class="status-pill <?= $statusClass ?>">
                                            <?= htmlspecialchars(ucfirst($status)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="po-actions">
                                            <a href="functions/generate_po.php?po_id=<?= $po['id'] ?>" class="po-action-btn po-action-download" target="_blank" title="Download PO">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <button type="button" class="po-action-btn po-action-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($po), ENT_QUOTES) ?>)" title="Edit PO">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if (($po['status'] ?? '') === 'pending'): ?>
                                                <button type="button" class="po-action-btn po-action-approve" onclick="updateStatus(<?= $po['id'] ?>, 'approved')" title="Approve PO">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="po-action-btn po-action-deliver" onclick="updateStatus(<?= $po['id'] ?>, 'delivered')" title="Mark Delivered">
                                                <i class="bi bi-truck"></i>
                                            </button>
                                            <button type="button" class="po-action-btn po-action-cancel" onclick="updateStatus(<?= $po['id'] ?>, 'cancelled')" title="Cancel PO">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            <button type="button" class="po-action-btn po-action-delete" onclick="deletePO(<?= $po['id'] ?>)" title="Delete PO">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="editPOModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Purchase Order</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editPOForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_po">
                    <input type="hidden" name="po_id" id="edit_po_id">

                    <div class="po-form-section">
                        <h6 class="po-form-section-title"><i class="bi bi-file-earmark-text"></i> PO Details</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">PO Number *</label>
                                <input type="text" name="po_number" id="edit_po_number" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">PO Date *</label>
                                <input type="date" name="po_date" id="edit_po_date" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Mode of Procurement</label>
                                <select name="mode_of_procurement" id="edit_mode_of_procurement" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="Section 27 Competitive Bidding">Section 27 Competitive Bidding</option>
                                    <option value="Section 28 Limited Source Bidding">Section 28 Limited Source Bidding</option>
                                    <option value="Section 29 Competitive Dialogue">Section 29 Competitive Dialogue</option>
                                    <option value="Section 30 Unsolicited Offer with Bid Matching">Section 30 Unsolicited Offer with Bid Matching</option>
                                    <option value="Section 31 Direct Contracting">Section 31 Direct Contracting</option>
                                    <option value="Section 35 Negotiated Procurement">Section 35 Negotiated Procurement</option>
                                    <option value="Section 32 Direct Acquisition">Section 32 Direct Acquisition</option>
                                    <option value="Section 33 Repeat Order">Section 33 Repeat Order</option>
                                    <option value="Section 34 Small Value Procurement">Section 34 Small Value Procurement</option>
                                    <option value="Section 36 Direct Sales">Section 36 Direct Sales</option>
                                    <option value="Section 37 Direct Procurement for Science, Technology and Innovation">Section 37 Direct Procurement for Science, Technology and Innovation</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="po-form-section">
                        <h6 class="po-form-section-title"><i class="bi bi-building"></i> Supplier and Delivery</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Supplier Name *</label>
                                <input type="text" name="supplier_name" id="edit_supplier_name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">TIN</label>
                                <input type="text" name="tin" id="edit_tin" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Place of Delivery</label>
                                <input type="text" name="place_of_delivery" id="edit_place_of_delivery" class="form-control">
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Supplier Address *</label>
                                <textarea name="supplier_address" id="edit_supplier_address" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Delivery Date</label>
                                <input type="date" name="delivery_date" id="edit_delivery_date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Delivery Terms</label>
                                <input type="text" name="delivery_terms" id="edit_delivery_terms" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Payment Term</label>
                                <input type="text" name="payment_term" id="edit_payment_term" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="po-form-section">
                        <h6 class="po-form-section-title"><i class="bi bi-journal-text"></i> Funding and Compliance</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fund Cluster</label>
                                <input type="text" name="fund_cluster" id="edit_fund_cluster" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">ORS/BURS Number</label>
                                <input type="text" name="ors_burs_number" id="edit_ors_burs_number" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fund Available</label>
                                <input type="text" name="fund_available" id="edit_fund_available" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Date of ORS/BURS</label>
                                <input type="date" name="date_ors_burs" id="edit_date_ors_burs" class="form-control">
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Conforme Name</label>
                                <input type="text" name="conforme_name" id="edit_conforme_name" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="po-form-section">
                        <div class="po-items-head">
                            <h6 class="po-form-section-title mb-0"><i class="bi bi-list-ul"></i> Items</h6>
                            <button type="button" class="btn-add-item" onclick="addEditItem()">
                                <i class="bi bi-plus"></i> Add Item
                            </button>
                        </div>
                        <div id="editItemsContainer"></div>
                    </div>

                    <div class="po-form-section">
                        <h6 class="po-form-section-title"><i class="bi bi-cash-stack"></i> Total Amount</h6>
                        <div class="row g-3">
                            <div class="col-md-6 ms-auto">
                                <label class="form-label fw-semibold">Total Amount *</label>
                                <div class="po-total-box">
                                    <div class="input-group">
                                        <span class="input-group-text">&#8369;</span>
                                        <input type="number" name="total_amount" id="edit_total_amount" class="form-control" step="0.01" required readonly style="background:#f8fafc;font-weight:600;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="submit" class="po-btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createPOModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Create New Purchase Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="createPOForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_po">
                    <div id="createPoSourceNotice" class="po-form-section" style="display:none;">
                        <h6 class="po-form-section-title"><i class="bi bi-link-45deg"></i> Source Purchase Request</h6>
                        <div class="po-ready-meta" id="createPoSourceText"></div>
                    </div>

                    <div class="po-form-section">
                        <h6 class="po-form-section-title"><i class="bi bi-file-earmark-text"></i> PO Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-file-text me-1"></i>
                                    Purchase Request (Optional)
                                </label>
                                <select name="pr_id" class="form-select">
                                    <option value="">Select PR (Optional)</option>
                                    <?php foreach ($purchaseRequests as $pr): ?>
                                        <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['pr_number'] ?? 'PR-' . $pr['id']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-calendar me-1"></i>
                                    PO Date *
                                </label>
                                <input type="date" name="po_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="po-form-section">
                        <h6 class="po-form-section-title"><i class="bi bi-building"></i> Supplier and Delivery</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-building me-1"></i>
                                    Supplier Name *
                                </label>
                                <select name="supplier_name" class="form-select" required onchange="updateSupplierAddress(this)">
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= htmlspecialchars($supplier['name']) ?>" data-address="<?= htmlspecialchars($supplier['location'] ?? '') ?>">
                                            <?= htmlspecialchars($supplier['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-truck me-1"></i>
                                    Delivery Date
                                </label>
                                <input type="date" name="delivery_date" class="form-control">
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Supplier Address *
                                </label>
                                <textarea name="supplier_address" class="form-control" rows="2" required placeholder="Enter supplier address"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-clipboard-check me-1"></i>
                                    Delivery Terms
                                </label>
                                <input type="text" name="delivery_terms" class="form-control" placeholder="e.g., FOB Destination">
                            </div>
                        </div>
                    </div>

                    <div class="po-form-section">
                        <div class="po-items-head">
                            <h6 class="po-form-section-title mb-0">
                                <i class="bi bi-list-ul"></i>
                                Purchase Order Items
                            </h6>
                            <button type="button" class="btn-add-item" onclick="addItem()">
                                <i class="bi bi-plus"></i> Add Item
                            </button>
                        </div>

                        <div id="itemsContainer">
                            <div class="item-row">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Description *</label>
                                        <input type="text" name="item_description[]" class="form-control" placeholder="Item Description" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Quantity *</label>
                                        <input type="number" name="quantity[]" class="form-control" placeholder="0" step="0.01" required oninput="calculateGrandTotal()">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Unit *</label>
                                        <input type="text" name="unit[]" class="form-control" placeholder="pcs, kg, etc." required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Unit Cost *</label>
                                        <input type="number" name="unit_cost[]" class="form-control" placeholder="0.00" step="0.01" required oninput="calculateGrandTotal()">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn-remove-item w-100" onclick="removeItem(this)">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="po-form-section">
                        <h6 class="po-form-section-title"><i class="bi bi-cash-stack"></i> Total Amount</h6>
                        <div class="row g-3">
                            <div class="col-md-6 ms-auto">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-currency-dollar me-1"></i>
                                    Total Amount *
                                </label>
                                <div class="po-total-box">
                                    <div class="input-group">
                                        <span class="input-group-text">&#8369;</span>
                                        <input type="number" name="total_amount" class="form-control" step="0.01" required readonly style="background:#f8fafc;font-weight:600;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancel
                    </button>
                    <button type="submit" class="po-btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        Create Purchase Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const readyPRData = <?= json_encode($readyPurchaseRequestDetails, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function openEditModal(po) {
    document.getElementById('edit_po_id').value = po.id;
    document.getElementById('edit_po_number').value = po.po_number ?? '';
    document.getElementById('edit_po_date').value = po.po_date ?? '';
    document.getElementById('edit_mode_of_procurement').value = po.mode_of_procurement ?? '';
    document.getElementById('edit_supplier_name').value = po.supplier_name ?? '';
    document.getElementById('edit_tin').value = po.tin ?? '';
    document.getElementById('edit_supplier_address').value = po.supplier_address ?? '';
    document.getElementById('edit_place_of_delivery').value = po.place_of_delivery ?? '';
    document.getElementById('edit_delivery_date').value = po.delivery_date ?? '';
    document.getElementById('edit_delivery_terms').value = po.delivery_terms ?? '';
    document.getElementById('edit_payment_term').value = po.payment_term ?? '';
    document.getElementById('edit_fund_cluster').value = po.fund_cluster ?? '';
    document.getElementById('edit_ors_burs_number').value = po.ors_burs_number ?? '';
    document.getElementById('edit_fund_available').value = po.fund_available ?? '';
    document.getElementById('edit_date_ors_burs').value = po.date_ors_burs ?? '';
    document.getElementById('edit_conforme_name').value = po.conforme_name ?? '';
    document.getElementById('edit_total_amount').value = po.total_amount ?? 0;

    fetch(`functions/get_po_items.php?po_id=${po.id}`)
        .then(r => r.json())
        .then(items => {
            const container = document.getElementById('editItemsContainer');
            container.innerHTML = '';
            items.forEach(item => addEditItem(item));
            if (!items.length) addEditItem();
            calcEditTotal();
        });

    new bootstrap.Modal(document.getElementById('editPOModal')).show();
}

function openCreatePOFromPR(prId) {
    const pr = readyPRData.find(item => Number(item.id) === Number(prId));
    if (!pr) return;

    resetCreatePOForm();

    const form = document.getElementById('createPOForm');

    const prSelect = form.querySelector('select[name="pr_id"]');
    const supplierSelect = form.querySelector('select[name="supplier_name"]');
    const poDateInput = form.querySelector('input[name="po_date"]');
    const deliveryDateInput = form.querySelector('input[name="delivery_date"]');
    const supplierAddressInput = form.querySelector('textarea[name="supplier_address"]');
    const deliveryTermsInput = form.querySelector('input[name="delivery_terms"]');
    const totalAmountInput = form.querySelector('input[name="total_amount"]');
    const notice = document.getElementById('createPoSourceNotice');
    const noticeText = document.getElementById('createPoSourceText');
    const itemsContainer = document.getElementById('itemsContainer');

    if (prSelect) prSelect.value = String(pr.id);
    if (supplierSelect) supplierSelect.value = '';
    if (poDateInput) poDateInput.value = new Date().toISOString().slice(0, 10);
    if (deliveryDateInput) deliveryDateInput.value = '';
    if (supplierAddressInput) supplierAddressInput.value = '';
    if (deliveryTermsInput) deliveryTermsInput.value = '';

    if (notice && noticeText) {
        notice.style.display = 'block';
        noticeText.innerHTML = `
            <strong>${escapeHtml(pr.pr_number || 'PR')}</strong> from ${escapeHtml(pr.office || '-')}
            <br>Requested by ${escapeHtml(pr.requested_by || '-')}
            <br>Fund source: ${escapeHtml(pr.fund_source || '-')}
        `;
    }

    itemsContainer.innerHTML = '';
    const items = Array.isArray(pr.items) && pr.items.length ? pr.items : [{
        description: '',
        quantity: '',
        unit: '',
        unit_cost: ''
    }];

    items.forEach(item => addItem(item));
    if (totalAmountInput) {
        totalAmountInput.value = Number(pr.total_amount || 0).toFixed(2);
    }
    calculateGrandTotal();

    new bootstrap.Modal(document.getElementById('createPOModal')).show();
}

function addEditItem(item = {}) {
    const container = document.getElementById('editItemsContainer');
    const div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-semibold">Stock No.</label>
                <input type="text" name="stock_property_no[]" class="form-control" value="${item.stock_property_no ?? ''}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Description *</label>
                <input type="text" name="item_description[]" class="form-control" value="${item.item_description ?? item.description ?? ''}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Qty *</label>
                <input type="number" name="quantity[]" class="form-control" value="${item.quantity ?? ''}" step="0.01" required oninput="calcEditTotal()">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Unit *</label>
                <input type="text" name="unit[]" class="form-control" value="${item.unit ?? ''}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Unit Cost *</label>
                <input type="number" name="unit_cost[]" class="form-control" value="${item.unit_cost ?? ''}" step="0.01" required oninput="calcEditTotal()">
            </div>
            <div class="col-12">
                <button type="button" class="btn-remove-item" onclick="removeEditItem(this)">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
        </div>`;
    container.appendChild(div);
}

function removeEditItem(btn) {
    const rows = document.querySelectorAll('#editItemsContainer .item-row');
    if (rows.length > 1) {
        btn.closest('.item-row').remove();
        calcEditTotal();
    } else {
        alert('At least one item is required.');
    }
}

function calcEditTotal() {
    let total = 0;
    document.querySelectorAll('#editItemsContainer .item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
        const cost = parseFloat(row.querySelector('input[name="unit_cost[]"]').value) || 0;
        total += qty * cost;
    });
    document.getElementById('edit_total_amount').value = total.toFixed(2);
}

document.getElementById('editPOForm').addEventListener('submit', function(e) {
    if (parseFloat(document.getElementById('edit_total_amount').value) <= 0) {
        e.preventDefault();
        alert('Please add items with valid quantities and costs.');
    }
});

document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.po-row');

    rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        row.style.display = searchData.includes(searchTerm) ? '' : 'none';
    });
});

function updateSupplierAddress(select) {
    const selectedOption = select.options[select.selectedIndex];
    const address = selectedOption.getAttribute('data-address');
    document.querySelector('#createPOForm textarea[name="supplier_address"]').value = address || '';
}

function addItem(item = {}) {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Description *</label>
                <input type="text" name="item_description[]" class="form-control" placeholder="Item Description" value="${escapeAttr(item.description ?? '')}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Quantity *</label>
                <input type="number" name="quantity[]" class="form-control" placeholder="0" step="0.01" value="${escapeAttr(item.quantity ?? '')}" required oninput="calculateGrandTotal()">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Unit *</label>
                <input type="text" name="unit[]" class="form-control" placeholder="pcs, kg, etc." value="${escapeAttr(item.unit ?? '')}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Unit Cost *</label>
                <input type="number" name="unit_cost[]" class="form-control" placeholder="0.00" step="0.01" value="${escapeAttr(item.unit_cost ?? '')}" required oninput="calculateGrandTotal()">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn-remove-item w-100" onclick="removeItem(this)">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
}

function removeItem(button) {
    const itemRows = document.querySelectorAll('#itemsContainer .item-row');
    if (itemRows.length > 1) {
        button.closest('.item-row').remove();
        calculateGrandTotal();
    } else {
        alert('At least one item is required.');
    }
}

function calculateGrandTotal() {
    let total = 0;
    document.querySelectorAll('#itemsContainer .item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
        const unitCost = parseFloat(row.querySelector('input[name="unit_cost[]"]').value) || 0;
        total += qty * unitCost;
    });
    document.querySelector('#createPOForm input[name="total_amount"]').value = total.toFixed(2);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeAttr(value) {
    return escapeHtml(value);
}

function updateStatus(poId, status) {
    const statusText = status.charAt(0).toUpperCase() + status.slice(1);
    if (confirm(`Are you sure you want to ${statusText.toLowerCase()} this purchase order?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="po_id" value="${poId}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deletePO(poId) {
    if (confirm('Are you sure you want to delete this purchase order? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_po">
            <input type="hidden" name="po_id" value="${poId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

document.getElementById('createPOForm').addEventListener('submit', function(e) {
    const totalAmount = parseFloat(document.querySelector('#createPOForm input[name="total_amount"]').value);
    if (totalAmount <= 0) {
        e.preventDefault();
        alert('Please add items with valid quantities and costs.');
    }
});

function resetCreatePOForm() {
    const form = document.getElementById('createPOForm');
    const notice = document.getElementById('createPoSourceNotice');
    const noticeText = document.getElementById('createPoSourceText');
    const itemsContainer = document.getElementById('itemsContainer');

    form.reset();
    if (notice) notice.style.display = 'none';
    if (noticeText) noticeText.textContent = '';

    itemsContainer.innerHTML = '';
    addItem();
    calculateGrandTotal();
}

document.addEventListener('DOMContentLoaded', function() {
    resetCreatePOForm();
    const createModalEl = document.getElementById('createPOModal');
    if (createModalEl) {
        createModalEl.addEventListener('hidden.bs.modal', resetCreatePOForm);
    }
});
</script>

</body>
</html>
