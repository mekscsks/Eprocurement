<?php

// --- Number to Words (PHP peso amounts) --------------------------------------
function number_to_words(float $amount): string {
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
             'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
             'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $convert = function(int $n) use (&$convert, $ones, $tens): string {
        if ($n < 20)   return $ones[$n];
        if ($n < 100)  return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
        if ($n < 1000) return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . $convert($n % 100) : '');
        if ($n < 1_000_000)    return $convert((int)($n / 1000)) . ' Thousand' . ($n % 1000 ? ' ' . $convert($n % 1000) : '');
        if ($n < 1_000_000_000) return $convert((int)($n / 1_000_000)) . ' Million' . ($n % 1_000_000 ? ' ' . $convert($n % 1_000_000) : '');
        return $convert((int)($n / 1_000_000_000)) . ' Billion' . ($n % 1_000_000_000 ? ' ' . $convert($n % 1_000_000_000) : '');
    };

    $pesos    = (int) floor($amount);
    $centavos = (int) round(($amount - $pesos) * 100);

    $words = ($pesos === 0 ? 'Zero' : $convert($pesos)) . ' Pesos';
    if ($centavos > 0) {
        $words .= ' and ' . $convert($centavos) . ' Centavos';
    }
    return $words . ' Only';
}

// --- Generate PO Number -------------------------------------------------------
function generatePONumber(mysqli $con): string {
    $year   = date('Y');
    $result = $con->query("SELECT COUNT(*) AS cnt FROM purchase_orders WHERE YEAR(created_at) = $year");
    $count  = ($result ? (int)$result->fetch_assoc()['cnt'] : 0) + 1;
    return "PO-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// --- Get All POs --------------------------------------------------------------
function getAllPO(mysqli $con): array {
    $result = $con->query("SELECT * FROM purchase_orders WHERE deleted = 0 ORDER BY created_at DESC");
    $rows   = [];
    if ($result) while ($row = $result->fetch_assoc()) $rows[] = $row;
    return $rows;
}

function getApprovedPRsReadyForPO(mysqli $con): array {
    $sql = "
        SELECT pr.*
        FROM purchase_requests pr
        LEFT JOIN purchase_orders po
            ON po.pr_id = pr.id
           AND po.deleted = 0
        WHERE pr.deleted = 0
          AND pr.status = 'Approved'
          AND po.id IS NULL
        ORDER BY pr.created_at DESC
    ";

    $result = $con->query($sql);
    $rows = [];
    if ($result) while ($row = $result->fetch_assoc()) $rows[] = $row;
    return $rows;
}

// --- Get All Suppliers --------------------------------------------------------
function getAllSuppliers(mysqli $con): array {
    $result = $con->query("SELECT id, name, location, company_name FROM suppliers WHERE status = 'active' ORDER BY name ASC");
    $rows   = [];
    if ($result) while ($row = $result->fetch_assoc()) $rows[] = $row;
    return $rows;
}

// --- Create PO ----------------------------------------------------------------
function createPO(mysqli $con, array $data): int|false {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $user_id   = $_SESSION['user_id'] ?? 1;
    $po_number = generatePONumber($con);

    $stmt = $con->prepare("
        INSERT INTO purchase_orders
            (po_number, pr_id, supplier_name, supplier_address, tin,
             po_date, mode_of_procurement, place_of_delivery, delivery_date,
             delivery_terms, payment_term, fund_cluster, ors_burs_number,
             fund_available, date_ors_burs, accountant_name, conforme_name,
             signatory_name, signatory_position, total_amount, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        'sisssssssssssssssssdi',
        $po_number,
        $data['pr_id'],
        $data['supplier_name'],
        $data['supplier_address'],
        $data['tin'],
        $data['po_date'],
        $data['mode_of_procurement'],
        $data['place_of_delivery'],
        $data['delivery_date'],
        $data['delivery_terms'],
        $data['payment_term'],
        $data['fund_cluster'],
        $data['ors_burs_number'],
        $data['fund_available'],
        $data['date_ors_burs'],
        $data['accountant_name'],
        $data['conforme_name'],
        $data['signatory_name'],
        $data['signatory_position'],
        $data['total_amount'],
        $user_id
    );

    if (!$stmt->execute()) {
        setAlert('error', 'Failed to create PO: ' . $con->error);
        return false;
    }

    $po_id = $con->insert_id;

    if (!empty($data['items']) && is_array($data['items'])) {
        $iStmt = $con->prepare("
            INSERT INTO purchase_order_items
                (po_id, stock_property_no, item_description, quantity, unit, unit_cost, total_cost)
            VALUES (?,?,?,?,?,?,?)
        ");
        foreach ($data['items'] as $item) {
            $total = (float)$item['quantity'] * (float)$item['unit_cost'];
            $iStmt->bind_param(
                'issdsdd',
                $po_id,
                $item['stock_property_no'],
                $item['description'],
                $item['quantity'],
                $item['unit'],
                $item['unit_cost'],
                $total
            );
            $iStmt->execute();
        }
    }

    $prId = isset($data['pr_id']) ? (int)$data['pr_id'] : 0;
    if ($prId > 0) {
        $prStmt = $con->prepare("UPDATE purchase_requests SET status = 'PO Generated', updated_at = NOW() WHERE id = ? AND deleted = 0");
        if ($prStmt) {
            $prStmt->bind_param('i', $prId);
            $prStmt->execute();
            $prStmt->close();
        }
    }

    setAlert('success', "Purchase Order '$po_number' created successfully!");
    return $po_id;
}

// --- Get PO with Items --------------------------------------------------------
function getPOWithItems(mysqli $con, int $po_id): ?array {
    $stmt = $con->prepare("SELECT * FROM purchase_orders WHERE id = ? AND deleted = 0 LIMIT 1");
    $stmt->bind_param('i', $po_id);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();
    if (!$po) return null;

    $iStmt = $con->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
    $iStmt->bind_param('i', $po_id);
    $iStmt->execute();
    $items = [];
    $res   = $iStmt->get_result();
    while ($row = $res->fetch_assoc()) $items[] = $row;

    $po['items'] = $items;
    return $po;
}

// --- Update PO (full edit) ---------------------------------------------------
function updatePO(mysqli $con, int $po_id, array $data): bool {
    $stmt = $con->prepare("
        UPDATE purchase_orders SET
            po_number           = ?,
            supplier_name       = ?,
            supplier_address    = ?,
            tin                 = ?,
            po_date             = ?,
            mode_of_procurement = ?,
            place_of_delivery   = ?,
            delivery_date       = ?,
            delivery_terms      = ?,
            payment_term        = ?,
            fund_cluster        = ?,
            ors_burs_number     = ?,
            fund_available      = ?,
            date_ors_burs       = ?,
            conforme_name       = ?,
            total_amount        = ?
        WHERE id = ? AND deleted = 0
    ");
    $stmt->bind_param(
        'sssssssssssssssdi',
        $data['po_number'],
        $data['supplier_name'],
        $data['supplier_address'],
        $data['tin'],
        $data['po_date'],
        $data['mode_of_procurement'],
        $data['place_of_delivery'],
        $data['delivery_date'],
        $data['delivery_terms'],
        $data['payment_term'],
        $data['fund_cluster'],
        $data['ors_burs_number'],
        $data['fund_available'],
        $data['date_ors_burs'],
        $data['conforme_name'],
        $data['total_amount'],
        $po_id
    );
    if (!$stmt->execute()) {
        setAlert('error', 'Failed to update PO: ' . $con->error);
        return false;
    }

    // Replace all items: delete old, insert new
    $con->prepare("DELETE FROM purchase_order_items WHERE po_id = ?")->bind_param('i', $po_id);
    $delStmt = $con->prepare("DELETE FROM purchase_order_items WHERE po_id = ?");
    $delStmt->bind_param('i', $po_id);
    $delStmt->execute();

    if (!empty($data['items'])) {
        $iStmt = $con->prepare("
            INSERT INTO purchase_order_items
                (po_id, stock_property_no, item_description, quantity, unit, unit_cost, total_cost)
            VALUES (?,?,?,?,?,?,?)
        ");
        foreach ($data['items'] as $item) {
            $total = (float)$item['quantity'] * (float)$item['unit_cost'];
            $iStmt->bind_param('issdsdd',
                $po_id,
                $item['stock_property_no'],
                $item['description'],
                $item['quantity'],
                $item['unit'],
                $item['unit_cost'],
                $total
            );
            $iStmt->execute();
        }
    }

    setAlert('success', 'Purchase Order updated successfully!');
    return true;
}

// --- Update PO Status ---------------------------------------------------------
function updatePOStatus(mysqli $con, int $po_id, string $status): bool {
    $stmt = $con->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $po_id);
    if (!$stmt->execute()) {
        setAlert('error', 'Failed to update status: ' . $con->error);
        return false;
    }

    if ($status === 'approved') {
        // Ensure po_id column exists
        $con->query("ALTER TABLE notice_to_proceed ADD COLUMN IF NOT EXISTS po_id INT DEFAULT NULL AFTER nta_id");

        $po = $con->prepare("SELECT * FROM purchase_orders WHERE id = ? AND deleted = 0 LIMIT 1");
        $po->bind_param('i', $po_id);
        $po->execute();
        $poRow = $po->get_result()->fetch_assoc();
        $po->close();

        if ($poRow) {
            $chk = $con->prepare("SELECT id FROM notice_to_proceed WHERE po_id = ? LIMIT 1");
            $chk->bind_param('i', $po_id);
            $chk->execute();
            $exists = $chk->get_result()->num_rows > 0;
            $chk->close();

            if (!$exists) {
                $year  = date('Y');
                $res   = $con->query("SELECT ntp_number FROM notice_to_proceed WHERE ntp_number LIKE 'NTP-$year-%' ORDER BY id DESC LIMIT 1");
                $last  = 0;
                if ($res && $res->num_rows > 0) {
                    $parts = explode('-', $res->fetch_assoc()['ntp_number']);
                    $last  = (int)($parts[2] ?? 0);
                }
                $ntp_number    = 'NTP-' . $year . '-' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
                $supplier      = $poRow['supplier_name'] ?? '';
                $project       = $poRow['delivery_terms'] ?? '';
                $amount        = (float)($poRow['total_amount'] ?? 0);
                $delivery_days = 30;

                $ins = $con->prepare("INSERT INTO notice_to_proceed (ntp_number, po_id, supplier, project, amount, delivery_days) VALUES (?,?,?,?,?,?)");
                $ins->bind_param('sissdi', $ntp_number, $po_id, $supplier, $project, $amount, $delivery_days);
                $ins->execute();
                $ins->close();
            }
        }
    }

    setAlert('success', "PO status updated to '$status'.");
    return true;
}

// --- Soft-delete PO -----------------------------------------------------------
function deletePO(mysqli $con, int $po_id): bool {
    $stmt = $con->prepare("UPDATE purchase_orders SET deleted = 1 WHERE id = ?");
    $stmt->bind_param('i', $po_id);
    if ($stmt->execute()) {
        setAlert('success', 'Purchase Order deleted.');
        return true;
    }
    setAlert('error', 'Failed to delete PO: ' . $con->error);
    return false;
}
