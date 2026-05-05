<?php
include __DIR__ . '/../../config/localdb.php';

// ── STATS ────────────────────────────────────────────────────
function getBresoStats($con) {
    $sql = "SELECT
        COUNT(*) AS total,
        SUM(status='Pending')   AS pending,
        SUM(status='Ongoing')   AS ongoing,
        SUM(status='Completed') AS completed,
        IFNULL(SUM(l.amount_per_lot),0) AS total_amount
        FROM procurements p
        LEFT JOIN procurement_lots l ON l.procurement_id = p.id
        WHERE p.deleted_at IS NULL";
    $r = mysqli_fetch_assoc(mysqli_query($con, $sql));
    return $r ?: ['total'=>0,'pending'=>0,'ongoing'=>0,'completed'=>0,'total_amount'=>0];
}

// ── FETCH ALL ────────────────────────────────────────────────
function getBresoRecords($con) {
    $sql = "SELECT p.id, p.reference_no, p.title, p.description, p.mode, p.nature,
                   p.winning_bidder, p.status, p.start_date, p.end_date, p.created_at,
                   IFNULL(SUM(l.amount_per_lot),0) AS total_amount,
                   GROUP_CONCAT(l.lot_name SEPARATOR ', ') AS lot_names
            FROM procurements p
            LEFT JOIN procurement_lots l ON l.procurement_id = p.id
            WHERE p.deleted_at IS NULL
            GROUP BY p.id
            ORDER BY p.created_at DESC";
    $res = mysqli_query($con, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
    return $rows;
}

// ── FETCH SUPPLIERS ──────────────────────────────────────────
function getSupplierOptions($con) {
    // Try suppliers table first, fall back to distinct winning_bidder values
    $suppliers = [];
    if (mysqli_query($con, "SHOW TABLES LIKE 'suppliers'") && mysqli_num_rows(mysqli_query($con, "SHOW TABLES LIKE 'suppliers'")) > 0) {
        $res = mysqli_query($con, "SELECT name FROM suppliers ORDER BY name ASC");
        if ($res) while ($r = mysqli_fetch_assoc($res)) $suppliers[] = $r['name'];
    }
    if (empty($suppliers)) {
        $res = mysqli_query($con, "SELECT DISTINCT winning_bidder FROM procurements WHERE winning_bidder != '' AND deleted_at IS NULL ORDER BY winning_bidder ASC");
        if ($res) while ($r = mysqli_fetch_assoc($res)) $suppliers[] = $r['winning_bidder'];
    }
    return $suppliers;
}

// ── PURCHASE REQUESTS FOR BAC MONITORING (all statuses) ─────
function getPurchaseRequestsForBac($con) {
    $sql = "SELECT pr.id, pr.pr_number, pr.requested_by, pr.office, pr.fund_source, pr.total_amount, pr.status, pr.created_at,
                   ts.procurement_mode, ts.ppmp_type
            FROM purchase_requests pr
            LEFT JOIN tool_sub ts ON pr.tool_sub_id = ts.id
            WHERE pr.deleted = 0
            ORDER BY pr.created_at DESC";
    $res = mysqli_query($con, $sql);
    $rows = [];
    if ($res) while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
    return $rows;
}

// ── AJAX HANDLER ─────────────────────────────────────────────
if (isset($_POST['bacreso_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['bacreso_action'];

    if ($action === 'save') {
        $title  = trim($_POST['title'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $mode   = trim($_POST['mode'] ?? '');
        $nature = trim($_POST['nature'] ?? '');
        $status = trim($_POST['status'] ?? 'Pending');
        $winner = trim($_POST['winning_bidder'] ?? '');
        $start  = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end    = !empty($_POST['end_date'])   ? $_POST['end_date']   : null;
        $amount = floatval($_POST['amount'] ?? 0);

        if (!$title || !$mode || !$status) {
            echo json_encode(['ok'=>false,'msg'=>'Title, Mode, and Status are required.']);
            exit;
        }

        // Auto-generate reference_no
        $year = date('Y');
        $last = mysqli_fetch_assoc(mysqli_query($con,
            "SELECT reference_no FROM procurements WHERE reference_no LIKE 'PROC-$year-%' ORDER BY id DESC LIMIT 1"));
        $num = $last ? intval(substr($last['reference_no'], -4)) + 1 : 1;
        $ref = 'PROC-' . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);

        $stmt = $con->prepare("INSERT INTO procurements (reference_no,title,description,mode,nature,winning_bidder,status,start_date,end_date) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssss', $ref,$title,$desc,$mode,$nature,$winner,$status,$start,$end);

        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            // Insert lot if amount provided
            if ($amount > 0) {
                $lotStmt = $con->prepare("INSERT INTO procurement_lots (procurement_id,lot_name,amount_per_lot) VALUES (?,?,?)");
                $lotName = 'Lot 1';
                $lotStmt->bind_param('isd', $id, $lotName, $amount);
                $lotStmt->execute();
            }
            echo json_encode(['ok'=>true,'ref'=>$ref,'id'=>$id]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>$stmt->error]);
        }
        exit;
    }

    if ($action === 'update') {
        $id     = intval($_POST['id'] ?? 0);
        $title  = trim($_POST['title'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $mode   = trim($_POST['mode'] ?? '');
        $nature = trim($_POST['nature'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $winner = trim($_POST['winning_bidder'] ?? '');
        $start  = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end    = !empty($_POST['end_date'])   ? $_POST['end_date']   : null;
        $amount = floatval($_POST['amount'] ?? 0);

        if (!$id || !$title || !$mode || !$status) {
            echo json_encode(['ok'=>false,'msg'=>'Missing required fields.']);
            exit;
        }

        $stmt = $con->prepare("UPDATE procurements SET title=?,description=?,mode=?,nature=?,winning_bidder=?,status=?,start_date=?,end_date=? WHERE id=? AND deleted_at IS NULL");
        $stmt->bind_param('ssssssssi', $title,$desc,$mode,$nature,$winner,$status,$start,$end,$id);

        if ($stmt->execute()) {
            // Update lot amount
            $check = mysqli_fetch_assoc(mysqli_query($con, "SELECT id FROM procurement_lots WHERE procurement_id=$id LIMIT 1"));
            if ($check) {
                $con->prepare("UPDATE procurement_lots SET amount_per_lot=? WHERE procurement_id=?")->execute() ||
                (function() use ($con,$amount,$id){
                    $s=$con->prepare("UPDATE procurement_lots SET amount_per_lot=? WHERE procurement_id=?");
                    $s->bind_param('di',$amount,$id); $s->execute();
                })();
                $s = $con->prepare("UPDATE procurement_lots SET amount_per_lot=? WHERE procurement_id=?");
                $s->bind_param('di', $amount, $id); $s->execute();
            } elseif ($amount > 0) {
                $s = $con->prepare("INSERT INTO procurement_lots (procurement_id,lot_name,amount_per_lot) VALUES (?,?,?)");
                $ln = 'Lot 1'; $s->bind_param('isd', $id, $ln, $amount); $s->execute();
            }
            echo json_encode(['ok'=>true]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>$stmt->error]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $con->prepare("UPDATE procurements SET deleted_at=NOW() WHERE id=?");
        $stmt->bind_param('i', $id);
        echo json_encode(['ok' => $stmt->execute()]);
        exit;
    }

    if ($action === 'approve_pr') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $con->prepare("UPDATE purchase_requests SET status='Approved', updated_at=NOW() WHERE id=? AND deleted=0");
        $stmt->bind_param('i', $id);
        echo json_encode(['ok' => $stmt->execute()]);
        exit;
    }

    if ($action === 'update_pr') {
        $id           = intval($_POST['id']           ?? 0);
        $pr_number    = trim($_POST['pr_number']    ?? '');
        $requested_by = trim($_POST['requested_by'] ?? '');
        $office       = trim($_POST['office']       ?? '');
        $fund_source  = trim($_POST['fund_source']  ?? '');
        $total_amount = floatval($_POST['total_amount'] ?? 0);
        $status       = trim($_POST['status']       ?? 'Pending');

        if (!$id || !$pr_number || !$office) {
            echo json_encode(['ok'=>false,'msg'=>'PR Number and Office are required.']);
            exit;
        }
        if (!in_array($status, ['Pending','Approved','Rejected','PO Generated'])) {
            echo json_encode(['ok'=>false,'msg'=>'Invalid status.']);
            exit;
        }

        $stmt = $con->prepare(
            "UPDATE purchase_requests
             SET pr_number=?, requested_by=?, office=?, fund_source=?, total_amount=?, status=?, updated_at=NOW()
             WHERE id=? AND deleted=0"
        );
        $stmt->bind_param('ssssdsi', $pr_number, $requested_by, $office, $fund_source, $total_amount, $status, $id);
        echo json_encode(['ok' => $stmt->execute(), 'msg' => $stmt->error]);
        exit;
    }

    if ($action === 'delete_pr') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $con->prepare("UPDATE purchase_requests SET deleted=1, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('i', $id);
        echo json_encode(['ok' => $stmt->execute()]);
        exit;
    }

    if ($action === 'get_tool_sub') {
        $res = mysqli_query($con, "SELECT id, procurement_mode, description, unit FROM tool_sub WHERE status IN ('approved','pending') ORDER BY id DESC");
        $rows = [];
        if ($res) while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'id'               => (int)$r['id'],
                'procurement_mode' => $r['procurement_mode'],
                'description'      => mb_strimwidth(trim($r['description']), 0, 60, '…'),
                'unit'             => $r['unit'],
            ];
        }
        echo json_encode($rows);
        exit;
    }

    if ($action === 'link_tool_sub') {
        $pr_id      = intval($_POST['pr_id']      ?? 0);
        $tool_sub_id = intval($_POST['tool_sub_id'] ?? 0);
        if (!$pr_id || !$tool_sub_id) { echo json_encode(['ok'=>false]); exit; }
        $stmt = $con->prepare("UPDATE purchase_requests SET tool_sub_id=? WHERE id=? AND deleted=0");
        $stmt->bind_param('ii', $tool_sub_id, $pr_id);
        echo json_encode(['ok' => $stmt->execute()]);
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Unknown action']);
    exit;
}
?>
