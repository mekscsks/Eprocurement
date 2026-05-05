<?php
session_start();
include '../../config/localdb.php';
include 'purchasefunctions.php';

header('Content-Type: text/html; charset=utf-8');

$dateFrom   = isset($_GET['date_from'])  ? trim($_GET['date_from'])  : '';
$dateTo     = isset($_GET['date_to'])    ? trim($_GET['date_to'])    : '';
$office     = isset($_GET['office'])     ? trim($_GET['office'])     : '';
$fundSource = isset($_GET['fund_source'])? trim($_GET['fund_source']): '';
$reportType = isset($_GET['report_type'])? trim($_GET['report_type']): 'purchase_requests';

// Build query
$where = ['pr.deleted = 0'];
$params = [];
$types  = '';

if ($dateFrom !== '') {
    $where[] = 'DATE(pr.created_at) >= ?';
    $params[] = $dateFrom; $types .= 's';
}
if ($dateTo !== '') {
    $where[] = 'DATE(pr.created_at) <= ?';
    $params[] = $dateTo; $types .= 's';
}
if ($office !== '') {
    $where[] = 'pr.office = ?';
    $params[] = $office; $types .= 's';
}
if ($fundSource !== '') {
    $where[] = 'pr.fund_source = ?';
    $params[] = $fundSource; $types .= 's';
}

$sql = "SELECT pr.*, COALESCE(SUM(i.total_cost), pr.total_amount) AS computed_total
        FROM purchase_requests pr
        LEFT JOIN purchase_requests_items i ON i.pr_id = pr.id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY pr.id
        ORDER BY pr.created_at DESC";

$stmt = $con->prepare($sql);
$rows = [];
if ($stmt) {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    $stmt->close();
}

$grandTotal = array_sum(array_column($rows, 'computed_total'));
$reportTitle = 'Purchase Requests Report';
$generatedDate = date('F d, Y');
$periodLabel = ($dateFrom || $dateTo)
    ? 'Period: ' . ($dateFrom ?: 'Start') . ' to ' . ($dateTo ?: 'Present')
    : 'All Dates';
?>
<div style="font-family:'DM Sans',sans-serif;color:#1A2B40;">

    <!-- Report Header -->
    <div style="text-align:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:2px solid #0D2B55;">
        <div style="font-size:.8rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#5A6A80;margin-bottom:.25rem;">
            Schools Division Office — Dasmariñas City
        </div>
        <h2 style="font-family:'DM Serif Display',serif;font-size:1.4rem;color:#0D2B55;margin:0 0 .25rem;">
            <?= htmlspecialchars($reportTitle) ?>
        </h2>
        <div style="font-size:.82rem;color:#5A6A80;"><?= htmlspecialchars($periodLabel) ?></div>
        <div style="font-size:.78rem;color:#9CA3AF;margin-top:.2rem;">Generated: <?= $generatedDate ?></div>
    </div>

    <?php if (empty($rows)): ?>
        <div style="text-align:center;padding:3rem 1rem;color:#9CA3AF;">
            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
            No records found for the selected filters.
        </div>
    <?php else: ?>

    <!-- Summary Badges -->
    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.25rem;">
        <div style="background:#E8EFF9;border:1px solid #D6E1EF;border-radius:10px;padding:.6rem 1rem;font-size:.82rem;">
            <span style="color:#5A6A80;">Total Records</span>
            <strong style="display:block;font-size:1.1rem;color:#0D2B55;"><?= count($rows) ?></strong>
        </div>
        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:.6rem 1rem;font-size:.82rem;">
            <span style="color:#5A6A80;">Grand Total</span>
            <strong style="display:block;font-size:1.1rem;color:#006B3C;">₱<?= number_format($grandTotal, 2) ?></strong>
        </div>
        <?php if ($office): ?>
        <div style="background:#FFF8E7;border:1px solid #FDE68A;border-radius:10px;padding:.6rem 1rem;font-size:.82rem;">
            <span style="color:#5A6A80;">Office</span>
            <strong style="display:block;font-size:1rem;color:#92400E;"><?= htmlspecialchars($office) ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.83rem;">
            <thead>
                <tr style="background:#0D2B55;color:rgba(255,255,255,.85);">
                    <th style="padding:.7rem .9rem;text-align:left;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;">#</th>
                    <th style="padding:.7rem .9rem;text-align:left;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;">PR No.</th>
                    <th style="padding:.7rem .9rem;text-align:left;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;">Office</th>
                    <th style="padding:.7rem .9rem;text-align:left;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;">End-Use</th>
                    <th style="padding:.7rem .9rem;text-align:left;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;">Fund Source</th>
                    <th style="padding:.7rem .9rem;text-align:right;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;">Amount (PHP)</th>
                    <th style="padding:.7rem .9rem;text-align:left;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;">Status</th>
                    <th style="padding:.7rem .9rem;text-align:left;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $row):
                    $status = $row['status'] ?? 'Pending';
                    $statusColor = match(strtolower($status)) {
                        'approved'     => '#006B3C',
                        'rejected'     => '#C0272D',
                        'po generated' => '#1A4080',
                        default        => '#B45309'
                    };
                    $statusBg = match(strtolower($status)) {
                        'approved'     => '#ECFDF5',
                        'rejected'     => '#FEF2F2',
                        'po generated' => '#E8EFF9',
                        default        => '#FFF8E7'
                    };
                    $amount = is_numeric($row['computed_total']) ? number_format((float)$row['computed_total'], 2) : '0.00';
                    $date   = $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : '-';
                    $bg     = $i % 2 === 0 ? '#fff' : '#F8FAFC';
                ?>
                <tr style="background:<?= $bg ?>;border-bottom:1px solid #E5E7EB;">
                    <td style="padding:.65rem .9rem;color:#9CA3AF;"><?= $i + 1 ?></td>
                    <td style="padding:.65rem .9rem;font-family:'DM Mono',monospace;font-size:.8rem;font-weight:600;color:#0D2B55;"><?= htmlspecialchars($row['pr_number'] ?? ('PR #' . $row['id'])) ?></td>
                    <td style="padding:.65rem .9rem;"><?= htmlspecialchars($row['office'] ?? '-') ?></td>
                    <td style="padding:.65rem .9rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($row['end_use'] ?? '') ?>"><?= htmlspecialchars($row['end_use'] ?? '-') ?></td>
                    <td style="padding:.65rem .9rem;"><?= htmlspecialchars($row['fund_source'] ?? '-') ?></td>
                    <td style="padding:.65rem .9rem;text-align:right;font-family:'DM Mono',monospace;font-weight:600;">₱<?= $amount ?></td>
                    <td style="padding:.65rem .9rem;">
                        <span style="display:inline-block;padding:.2rem .6rem;border-radius:999px;font-size:.7rem;font-weight:700;background:<?= $statusBg ?>;color:<?= $statusColor ?>;">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </td>
                    <td style="padding:.65rem .9rem;font-size:.78rem;color:#5A6A80;"><?= $date ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#F4F7FC;border-top:2px solid #0D2B55;">
                    <td colspan="5" style="padding:.75rem .9rem;font-weight:700;font-size:.83rem;color:#0D2B55;">GRAND TOTAL</td>
                    <td style="padding:.75rem .9rem;text-align:right;font-family:'DM Mono',monospace;font-weight:700;font-size:.9rem;color:#006B3C;">₱<?= number_format($grandTotal, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php endif; ?>

    <!-- Footer -->
    <div style="margin-top:2rem;padding-top:1rem;border-top:1px solid #E5E7EB;display:flex;justify-content:space-between;font-size:.75rem;color:#9CA3AF;">
        <span>SDO Dasmariñas — eProcurement Portal</span>
        <span>Printed: <?= date('Y-m-d H:i') ?></span>
    </div>
</div>
