<?php
if (!isset($con)) include __DIR__ . '/../../config/localdb.php';

if (!function_exists('getTableColumnsMap')) {
    function getTableColumnsMap($con, $table) {
        static $cache = [];

        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return [];
        }

        if (isset($cache[$table])) {
            return $cache[$table];
        }

        $result = mysqli_query($con, "SHOW COLUMNS FROM `$table`");
        if (!$result) {
            $cache[$table] = [];
            return $cache[$table];
        }

        $map = [];
        while ($row = mysqli_fetch_assoc($result)) {
            if (!isset($row['Field'])) continue;
            $map[$row['Field']] = true;
        }

        $cache[$table] = $map;
        return $cache[$table];
    }
}

if (!function_exists('tableHasColumn')) {
    function tableHasColumn($con, $table, $column) {
        $columns = getTableColumnsMap($con, $table);
        return isset($columns[$column]);
    }
}

if (!function_exists('tableExists')) {
    function tableExists($con, $table) {
        static $cache = [];

        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        if (isset($cache[$table])) {
            return $cache[$table];
        }

        $safe = mysqli_real_escape_string($con, $table);
        $result = mysqli_query($con, "SHOW TABLES LIKE '$safe'");
        $exists = $result && mysqli_num_rows($result) > 0;
        $cache[$table] = $exists;
        return $cache[$table];
    }
}

if (!function_exists('bindParams')) {
    function bindParams($stmt, $types, $params) {
        $refs = [];
        $refs[] = &$types;
        foreach ($params as $k => $v) {
            $refs[] = &$params[$k];
        }
        return call_user_func_array([$stmt, 'bind_param'], $refs);
    }
}

function getAllPR($con){
    $query = "SELECT * FROM purchase_requests WHERE deleted = 0 ORDER BY created_at DESC";
    $result = mysqli_query($con, $query);
    $prs = [];
    if($result){
        while($row = mysqli_fetch_assoc($result)){
            $prs[] = $row;
        }
    }
    return $prs;
}

function getPRItemsByPRIds($con, $prIds) {
    if (!tableExists($con, 'purchase_requests_items')) {
        return [];
    }

    $ids = [];
    if (is_array($prIds)) {
        foreach ($prIds as $id) {
            $intId = (int)$id;
            if ($intId > 0) $ids[$intId] = true;
        }
    }
    $ids = array_keys($ids);
    if (empty($ids)) return [];

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT * FROM purchase_requests_items WHERE pr_id IN ($placeholders) ORDER BY pr_id ASC, id ASC";
    $stmt = $con->prepare($sql);
    if (!$stmt) return [];

    $types = str_repeat('i', count($ids));
    $params = $ids;
    bindParams($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();

    $map = [];
    while ($row = $result->fetch_assoc()) {
        $pid = (int)($row['pr_id'] ?? 0);
        if ($pid <= 0) continue;
        if (!isset($map[$pid])) $map[$pid] = [];
        $map[$pid][] = $row;
    }

    $stmt->close();
    return $map;
}

function dedupePRItemRows($rows) {
    if (!is_array($rows) || empty($rows)) return [];

    $seen = [];
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) continue;

        if (isset($row['id']) && is_numeric($row['id'])) {
            $key = 'id:' . (string)((int)$row['id']);
        } else {
            $key = 'h:' . sha1(
                (string)($row['item_name'] ?? '') . '|' .
                (string)($row['description'] ?? ($row['item_description'] ?? '')) . '|' .
                (string)($row['quantity'] ?? '') . '|' .
                (string)($row['unit'] ?? '') . '|' .
                (string)($row['estimated_cost'] ?? ($row['total_cost'] ?? ($row['amount'] ?? ($row['unit_cost'] ?? ''))))
            );
        }

        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $out[] = $row;
    }

    return $out;
}

function getPRItemsByPRNumbers($con, $prNumbers) {
    if (!tableExists($con, 'pr_items') || !tableHasColumn($con, 'pr_items', 'pr_no')) {
        return [];
    }

    $nos = [];
    if (is_array($prNumbers)) {
        foreach ($prNumbers as $no) {
            $no = trim((string)$no);
            if ($no === '') continue;
            $nos[$no] = true;
        }
    }
    $nos = array_keys($nos);
    if (empty($nos)) return [];

    $placeholders = implode(',', array_fill(0, count($nos), '?'));
    $sql = "SELECT * FROM pr_items WHERE pr_no IN ($placeholders) ORDER BY pr_no ASC, id ASC";
    $stmt = $con->prepare($sql);
    if (!$stmt) return [];

    $types = str_repeat('s', count($nos));
    $params = $nos;
    bindParams($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();

    $map = [];
    while ($row = $result->fetch_assoc()) {
        $prNo = trim((string)($row['pr_no'] ?? ''));
        if ($prNo === '') continue;
        if (!isset($map[$prNo])) $map[$prNo] = [];
        $map[$prNo][] = $row;
    }
    $stmt->close();

    foreach ($map as $k => $rows) {
        $map[$k] = dedupePRItemRows($rows);
    }

    return $map;
}

function getPRItemSummaryByPRNumbers($con, $prNumbers) {
    if (!tableExists($con, 'pr_items') || !tableHasColumn($con, 'pr_items', 'pr_no')) {
        return [];
    }

    $nos = [];
    if (is_array($prNumbers)) {
        foreach ($prNumbers as $no) {
            $no = trim((string)$no);
            if ($no === '') continue;
            $nos[$no] = true;
        }
    }
    $nos = array_keys($nos);
    if (empty($nos)) return [];

    $placeholders = implode(',', array_fill(0, count($nos), '?'));
    $sql = "SELECT pr_no, COUNT(DISTINCT id) AS item_count, SUM(COALESCE(estimated_cost, 0)) AS total_estimated_cost
            FROM pr_items
            WHERE pr_no IN ($placeholders)
            GROUP BY pr_no";
    $stmt = $con->prepare($sql);
    if (!$stmt) return [];

    $types = str_repeat('s', count($nos));
    $params = $nos;
    bindParams($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();

    $map = [];
    while ($row = $result->fetch_assoc()) {
        $prNo = trim((string)($row['pr_no'] ?? ''));
        if ($prNo === '') continue;
        $map[$prNo] = [
            'item_count' => (int)($row['item_count'] ?? 0),
            'total_estimated_cost' => (float)($row['total_estimated_cost'] ?? 0),
        ];
    }

    $stmt->close();
    return $map;
}

function getPRItemSummaryByPRIds($con, $prIds) {
    if (!tableExists($con, 'purchase_requests_items')) {
        return [];
    }

    $ids = [];
    if (is_array($prIds)) {
        foreach ($prIds as $id) {
            $intId = (int)$id;
            if ($intId > 0) $ids[$intId] = true;
        }
    }
    $ids = array_keys($ids);
    if (empty($ids)) return [];

    $itemTable = 'purchase_requests_items';
    $cols = getTableColumnsMap($con, $itemTable);

    $costExpr = '0';
    if (isset($cols['total_cost'])) {
        $costExpr = "COALESCE(total_cost, 0)";
    } elseif (isset($cols['amount'])) {
        $costExpr = "COALESCE(amount, 0)";
    } elseif (isset($cols['quantity']) && isset($cols['unit_cost'])) {
        $costExpr = "COALESCE(quantity, 0) * COALESCE(unit_cost, 0)";
    } elseif (isset($cols['estimated_cost'])) {
        $costExpr = "COALESCE(estimated_cost, 0)";
    }

    $countExpr = isset($cols['id']) ? "COUNT(DISTINCT id)" : "COUNT(*)";

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT pr_id, $countExpr AS item_count, SUM($costExpr) AS total_estimated_cost
            FROM `$itemTable`
            WHERE pr_id IN ($placeholders)
            GROUP BY pr_id";
    $stmt = $con->prepare($sql);
    if (!$stmt) return [];

    $types = str_repeat('i', count($ids));
    $params = $ids;
    bindParams($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();

    $map = [];
    while ($row = $result->fetch_assoc()) {
        $pid = (int)($row['pr_id'] ?? 0);
        if ($pid <= 0) continue;
        $map[$pid] = [
            'item_count' => (int)($row['item_count'] ?? 0),
            'total_estimated_cost' => (float)($row['total_estimated_cost'] ?? 0),
        ];
    }

    $stmt->close();
    return $map;
}

function getPRWithItems($con, $pr_id) {
    $prData = [];

    // Fetch the purchase request
    $stmt = $con->prepare("SELECT * FROM purchase_requests WHERE id = ? AND deleted = 0 LIMIT 1");
    $stmt->bind_param("i", $pr_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $prData = $result->fetch_assoc();

        // Fetch the items for this PR
        $stmtItems = $con->prepare("SELECT * FROM purchase_requests_items WHERE pr_id = ?");
        $stmtItems->bind_param("i", $pr_id);
        $stmtItems->execute();
        $itemsResult = $stmtItems->get_result();

        $items = [];
        while ($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }

        // Add items to main PR data
        $prData['items'] = $items;
    } else {
        $prData = null; // PR not found or deleted
    }

    $stmt->close();
    return $prData;
}

// ?? Create a new Purchase Request
function createPR($con, $data){
    if(session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $pr_number = trim((string)($data['pr_number'] ?? ''));
    $requested_by = trim((string)($data['requested_by'] ?? ''));
    if ($requested_by === '') {
        $requested_by = (string)($_SESSION['auth_user']['name'] ?? $_SESSION['auth_user']['username'] ?? '');
    }

    $po_number = trim((string)($data['po_number'] ?? ''));
    $end_use = trim((string)($data['end_use'] ?? $requested_by));
    $purpose = trim((string)($data['purpose'] ?? $data['end_use'] ?? ''));
    $office = trim((string)($data['office'] ?? ''));
    $section = trim((string)($data['section'] ?? ''));
    $fund_cluster = trim((string)($data['fund_cluster'] ?? ''));
    $fund_source = trim((string)($data['fund_source'] ?? ''));
    $designation = trim((string)($data['designation'] ?? ''));
    $supplierProvided = array_key_exists('supplier', $data);
    $supplier = $supplierProvided ? trim((string)($data['supplier'] ?? '')) : '';
    $approved_budget = $data['approved_budget'] ?? null;
    $remarks = trim((string)($data['remarks'] ?? ''));
    $status = 'Pending';

    $quantities    = is_array($data['quantity'] ?? null) ? $data['quantity'] : [];
    $units         = is_array($data['unit'] ?? null) ? $data['unit'] : [];
    $descriptions  = is_array($data['description'] ?? null) ? $data['description'] : [];
    $unitCosts     = is_array($data['unit_cost'] ?? null) ? $data['unit_cost'] : [];
    $stockNos      = is_array($data['stock_property_no'] ?? null) ? $data['stock_property_no'] : [];

    $items = [];
    $itemsTotal = 0.0;

    $max = max(count($quantities), count($units), count($descriptions), count($unitCosts));
    for ($i = 0; $i < $max; $i++) {
        $desc = trim((string)($descriptions[$i] ?? ''));
        $unit = trim((string)($units[$i] ?? ''));
        $qtyRaw = $quantities[$i] ?? null;
        $unitCostRaw = $unitCosts[$i] ?? null;

        $hasAny = ($desc !== '') || ($unit !== '') || ($qtyRaw !== null && $qtyRaw !== '') || ($unitCostRaw !== null && $unitCostRaw !== '');
        if (!$hasAny) continue;

        $qty = is_numeric($qtyRaw) ? (float)$qtyRaw : 0.0;
        $unitCost = is_numeric($unitCostRaw) ? (float)$unitCostRaw : 0.0;
        $totalCost = $qty * $unitCost;

        $items[] = [
            'stock_property_no' => trim((string)($stockNos[$i] ?? '')),
            'quantity' => $qty,
            'unit' => $unit,
            'description' => $desc,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
        ];
        $itemsTotal += $totalCost;
    }

    if ($office === '' || $requested_by === '') {
        setAlert('error', 'Office and Requested By are required!');
        return false;
    }

    if (empty($items)) {
        setAlert('error', 'Please add at least one item.');
        return false;
    }

    $total_amount = $itemsTotal;
    if (!is_numeric($total_amount) || $total_amount <= 0) {
        if (is_numeric($approved_budget)) {
            $total_amount = (float)$approved_budget;
        }
    }

    $prTable = 'purchase_requests';
    $itemTable = 'purchase_requests_items';


    mysqli_begin_transaction($con);

    $hasPoHeader = tableHasColumn($con, $prTable, 'po_number') || tableHasColumn($con, $prTable, 'po_no') || tableHasColumn($con, $prTable, 'purchase_order');
    $hasSupplierHeader = tableHasColumn($con, $prTable, 'supplier');
    $hasSupplierItem = tableHasColumn($con, $itemTable, 'supplier');
    $remarksAdditions = [];
    if (!$hasPoHeader && $po_number !== '') {
        $remarksAdditions[] = "PO Number: " . $po_number;
    }
    if (!$hasSupplierHeader && !$hasSupplierItem && $supplier !== '') {
        $remarksAdditions[] = "Supplier: " . $supplier;
    }
    if (!empty($remarksAdditions)) {
        $remarks = trim($remarks);
        $append = implode(' | ', $remarksAdditions);
        $remarks = $remarks === '' ? $append : ($remarks . ' | ' . $append);
    }

    $account_id = isset($data['account_id']) ? (int)$data['account_id'] : ((int)($_SESSION['auth_user']['account_id'] ?? 0) ?: null);

    $ppmp_id  = isset($data['ppmp_id']) && is_numeric($data['ppmp_id']) ? (int)$data['ppmp_id'] : null;
    $has_ppmp = ($ppmp_id !== null) ? 1 : 0;

    $insertData = [
        'pr_number'    => $pr_number,
        'requested_by' => $requested_by,
        'office'       => $office,
        'total_amount' => $total_amount,
        'status'       => $status,
        'remarks'      => $remarks,
        'has_ppmp'     => $has_ppmp,
        'created_at'   => date('Y-m-d H:i:s'),
    ];

    if ($ppmp_id !== null && tableHasColumn($con, $prTable, 'ppmp_id')) {
        $insertData['ppmp_id'] = $ppmp_id;
    }
    if ($ppmp_id !== null && tableHasColumn($con, $prTable, 'tool_sub_id')) {
        $insertData['tool_sub_id'] = $ppmp_id;
    }

    if ($account_id && tableHasColumn($con, $prTable, 'account_id')) {
        $insertData['account_id'] = $account_id;
    }

    if (tableHasColumn($con, $prTable, 'po_number')) $insertData['po_number'] = $po_number;
    if (tableHasColumn($con, $prTable, 'po_no')) $insertData['po_no'] = $po_number;
    if (tableHasColumn($con, $prTable, 'purchase_order')) $insertData['purchase_order'] = $po_number;
    if (tableHasColumn($con, $prTable, 'section')) $insertData['section'] = $section;
    if (tableHasColumn($con, $prTable, 'responsibility_center_code')) $insertData['responsibility_center_code'] = trim((string)($data['responsibility_center_code'] ?? ''));
    if (tableHasColumn($con, $prTable, 'end_use')) $insertData['end_use'] = $end_use;
    if (tableHasColumn($con, $prTable, 'purpose')) $insertData['purpose'] = $purpose;
    if (tableHasColumn($con, $prTable, 'fund_source')) $insertData['fund_source'] = $fund_source;
    if (tableHasColumn($con, $prTable, 'fund_cluster')) $insertData['fund_cluster'] = $fund_cluster;
    if (tableHasColumn($con, $prTable, 'supplier')) $insertData['supplier'] = $supplier;
    if (tableHasColumn($con, $prTable, 'approved_budget')) $insertData['approved_budget'] = is_numeric($approved_budget) ? (float)$approved_budget : null;
    if (tableHasColumn($con, $prTable, 'abc')) $insertData['abc'] = is_numeric($approved_budget) ? (float)$approved_budget : null;
    if (tableHasColumn($con, $prTable, 'designation')) $insertData['designation'] = $designation;
    if (tableHasColumn($con, $prTable, 'ppmp_attachments_required')) $insertData['ppmp_attachments_required'] = is_string($data['ppmp_attachments_required'] ?? null) ? $data['ppmp_attachments_required'] : json_encode($data['ppmp_attachments_required'] ?? []);
    if (tableHasColumn($con, $prTable, 'expected_delivery_date')) $insertData['expected_delivery_date'] = !empty($data['expected_delivery_date']) ? $data['expected_delivery_date'] : null;
    if (tableHasColumn($con, $prTable, 'procurement_mode')) $insertData['procurement_mode'] = trim((string)($data['procurement_mode'] ?? ''));
    if (tableHasColumn($con, $prTable, 'is_pr_locked')) $insertData['is_pr_locked'] = isset($data['is_pr_locked']) ? (int)$data['is_pr_locked'] : 0;

    $columnsMap = getTableColumnsMap($con, $prTable);
    $cols = [];
    $placeholders = [];
    $types = '';
    $params = [];

    foreach ($insertData as $col => $val) {
        if (!isset($columnsMap[$col])) continue;
        $cols[] = "`$col`";
        $placeholders[] = '?';
        if ($val === null) {
            $types .= 's';
            $params[] = null;
        } elseif (is_float($val) || is_int($val) || is_numeric($val)) {
            $types .= 'd';
            $params[] = (float)$val;
        } else {
            $types .= 's';
            $params[] = (string)$val;
        }
    }

    if (empty($cols)) {
        mysqli_rollback($con);
        setAlert('error', 'Failed to create Purchase Request: invalid table columns.');
        return false;
    }

    $sql = "INSERT INTO `$prTable` (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $con->prepare($sql);
    if (!$stmt) {
        mysqli_rollback($con);
        setAlert('error', 'Failed to create Purchase Request: ' . mysqli_error($con));
        return false;
    }
    bindParams($stmt, $types, $params);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        mysqli_rollback($con);
        setAlert('error', 'Failed to create Purchase Request: ' . mysqli_error($con));
        return false;
    }

    $prId = (int)mysqli_insert_id($con);

    if (!tableExists($con, $itemTable) || !tableHasColumn($con, $itemTable, 'pr_id')) {
        mysqli_rollback($con);
        setAlert('error', 'Failed to create Purchase Request: items table missing.');
        return false;
    }

    $itemColumnsMap = getTableColumnsMap($con, $itemTable);
    foreach ($items as $item) {
        $itemInsert = [
            'pr_id' => $prId,
            'stock_property_no' => $item['stock_property_no'] ?? '',
            'quantity' => $item['quantity'],
            'unit' => $item['unit'],
            'description' => $item['description'],
            'unit_cost' => $item['unit_cost'],
            'total_cost' => $item['total_cost'],
        ];
        if (isset($itemColumnsMap['item_description']) && !isset($itemColumnsMap['description'])) {
            $itemInsert['item_description'] = $item['description'];
        }
        if (isset($itemColumnsMap['amount']) && !isset($itemColumnsMap['total_cost'])) {
            $itemInsert['amount'] = $item['total_cost'];
        }
        if (isset($itemColumnsMap['supplier']) && $supplier !== '') {
            $itemInsert['supplier'] = $supplier;
        }

        $itemCols = [];
        $itemPlaceholders = [];
        $itemTypes = '';
        $itemParams = [];

        foreach ($itemInsert as $col => $val) {
            if (!isset($itemColumnsMap[$col])) continue;
            $itemCols[] = "`$col`";
            $itemPlaceholders[] = '?';
            if ($val === null) {
                $itemTypes .= 's';
                $itemParams[] = null;
            } elseif (is_float($val) || is_int($val) || is_numeric($val)) {
                $itemTypes .= 'd';
                $itemParams[] = (float)$val;
            } else {
                $itemTypes .= 's';
                $itemParams[] = (string)$val;
            }
        }

        if (empty($itemCols)) {
            mysqli_rollback($con);
            setAlert('error', 'Failed to create Purchase Request: invalid item columns.');
            return false;
        }

        $itemSql = "INSERT INTO `$itemTable` (" . implode(',', $itemCols) . ") VALUES (" . implode(',', $itemPlaceholders) . ")";
        $itemStmt = $con->prepare($itemSql);
        if (!$itemStmt) {
            mysqli_rollback($con);
            setAlert('error', 'Failed to create Purchase Request: ' . mysqli_error($con));
            return false;
        }

        bindParams($itemStmt, $itemTypes, $itemParams);
        $itemOk = $itemStmt->execute();
        $itemStmt->close();

        if (!$itemOk) {
            mysqli_rollback($con);
            setAlert('error', 'Failed to create Purchase Request: ' . mysqli_error($con));
            return false;
        }
    }

    mysqli_commit($con);

    // Log to documents + document_logs
    $tracking = 'PR-' . date('Ymd') . '-' . str_pad((string)$prId, 4, '0', STR_PAD_LEFT);
    $docTitle = "Purchase Request - $pr_number";
    $docDesc  = "PR submitted by $requested_by";
    $docUserCol = tableHasColumn($con, 'documents', 'user_id') ? 'user_id' : (tableHasColumn($con, 'documents', 'account_id') ? 'account_id' : null);
    if ($docUserCol) {
        $stmtDoc = $con->prepare("INSERT INTO documents (`$docUserCol`, tracking_number, title, description, current_status, created_at) VALUES (?, ?, ?, ?, 'Pending', NOW())");
        if ($stmtDoc) $stmtDoc->bind_param("isss", $account_id, $tracking, $docTitle, $docDesc);
    } else {
        $stmtDoc = $con->prepare("INSERT INTO documents (tracking_number, title, description, current_status, created_at) VALUES (?, ?, ?, 'Pending', NOW())");
        if ($stmtDoc) $stmtDoc->bind_param("sss", $tracking, $docTitle, $docDesc);
    }
    if ($stmtDoc) {
        $stmtDoc->execute();
        $docId = (int)$stmtDoc->insert_id;
        $stmtDoc->close();
        if ($docId > 0) {
            $logRemarks = "PR $pr_number submitted by account ID: $account_id";
            $stmtLog = $con->prepare("INSERT INTO document_logs (document_id, status, remarks) VALUES (?, 'Pending', ?)");
            if ($stmtLog) {
                $stmtLog->bind_param("is", $docId, $logRemarks);
                $stmtLog->execute();
                $stmtLog->close();
            }
        }
    }

    setAlert('success', "Purchase Request '$pr_number' created successfully!");
    return true;
}

// ?? Update an existing Purchase Request
function updatePR($con, $id, $data){
    if(session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $prId = (int)$id;
    if ($prId <= 0) {
        setAlert('error', 'Invalid Purchase Request ID.');
        return false;
    }

    $pr_number = trim((string)($data['pr_number'] ?? ''));
    $requested_by = trim((string)($data['requested_by'] ?? ''));

    $po_number = trim((string)($data['po_number'] ?? ''));
    $end_use = trim((string)($data['end_use'] ?? $requested_by));
    $purpose = trim((string)($data['purpose'] ?? $data['end_use'] ?? ''));
    $office = trim((string)($data['office'] ?? ''));
    $fund_source = trim((string)($data['fund_source'] ?? ''));
    $designation = trim((string)($data['designation'] ?? ''));
    $supplierProvided = array_key_exists('supplier', $data);
    $supplier = $supplierProvided ? trim((string)($data['supplier'] ?? '')) : '';
    $approved_budget = $data['approved_budget'] ?? null;
    $remarks = trim((string)($data['remarks'] ?? ''));

    $quantities    = is_array($data['quantity'] ?? null) ? $data['quantity'] : [];
    $units         = is_array($data['unit'] ?? null) ? $data['unit'] : [];
    $descriptions  = is_array($data['description'] ?? null) ? $data['description'] : [];
    $unitCosts     = is_array($data['unit_cost'] ?? null) ? $data['unit_cost'] : [];
    $stockNos      = is_array($data['stock_property_no'] ?? null) ? $data['stock_property_no'] : [];

    $items = [];
    $itemsTotal = 0.0;
    $max = max(count($quantities), count($units), count($descriptions), count($unitCosts));
    for ($i = 0; $i < $max; $i++) {
        $desc = trim((string)($descriptions[$i] ?? ''));
        $unit = trim((string)($units[$i] ?? ''));
        $qtyRaw = $quantities[$i] ?? null;
        $unitCostRaw = $unitCosts[$i] ?? null;

        $hasAny = ($desc !== '') || ($unit !== '') || ($qtyRaw !== null && $qtyRaw !== '') || ($unitCostRaw !== null && $unitCostRaw !== '');
        if (!$hasAny) continue;

        $qty = is_numeric($qtyRaw) ? (float)$qtyRaw : 0.0;
        $unitCost = is_numeric($unitCostRaw) ? (float)$unitCostRaw : 0.0;
        $totalCost = $qty * $unitCost;

        $items[] = [
            'stock_property_no' => trim((string)($stockNos[$i] ?? '')),
            'quantity' => $qty,
            'unit' => $unit,
            'description' => $desc,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
        ];
        $itemsTotal += $totalCost;
    }

    if ($pr_number === '' || $requested_by === '') {
        setAlert('error', 'PR Number and Requested By are required!');
        return false;
    }
    if ($office === '') {
        setAlert('error', 'Office is required!');
        return false;
    }
    if (empty($items)) {
        setAlert('error', 'Please add at least one item.');
        return false;
    }

    $total_amount = $data['total_amount'] ?? null;
    $total_amount = is_numeric($total_amount) ? (float)$total_amount : (float)$itemsTotal;
    if ($total_amount <= 0 && $itemsTotal > 0) {
        $total_amount = (float)$itemsTotal;
    }
    if ($total_amount <= 0 && is_numeric($approved_budget)) {
        $total_amount = (float)$approved_budget;
    }

    $prTable = 'purchase_requests';
    $itemTable = 'purchase_requests_items';

    $hasPoHeader = tableHasColumn($con, $prTable, 'po_number') || tableHasColumn($con, $prTable, 'po_no') || tableHasColumn($con, $prTable, 'purchase_order');
    $hasSupplierHeader = tableHasColumn($con, $prTable, 'supplier');
    $hasSupplierItem = tableHasColumn($con, $itemTable, 'supplier');

    $existingSupplier = '';
    if (!$supplierProvided) {
        $selectCols = ['`remarks`'];
        if ($hasSupplierHeader) {
            $selectCols[] = '`supplier`';
        }
        $selSql = "SELECT " . implode(', ', $selectCols) . " FROM `$prTable` WHERE id = ? AND deleted = 0 LIMIT 1";
        $selStmt = $con->prepare($selSql);
        if ($selStmt) {
            $selStmt->bind_param("i", $prId);
            $selStmt->execute();
            $selRes = $selStmt->get_result();
            $row = $selRes ? $selRes->fetch_assoc() : null;
            $selStmt->close();

            if (is_array($row)) {
                if ($hasSupplierHeader) {
                    $existingSupplier = trim((string)($row['supplier'] ?? ''));
                }
                if ($existingSupplier === '') {
                    $existingRemarks = (string)($row['remarks'] ?? '');
                    if ($existingRemarks !== '' && preg_match('/Supplier:\s*([^|]+)/i', $existingRemarks, $m)) {
                        $existingSupplier = trim($m[1]);
                    }
                }
            }
        }
    }
    $effectiveSupplierForItems = $supplierProvided ? $supplier : $existingSupplier;

    $parts = array_map('trim', explode('|', (string)$remarks));
    $filtered = [];
    foreach ($parts as $p) {
        if ($p === '') continue;
        if (preg_match('/^PO\\s*Number\\s*:/i', $p)) continue;
        if ($supplierProvided && preg_match('/^Supplier\\s*:/i', $p)) continue;
        $filtered[] = $p;
    }
    $remarks = implode(' | ', $filtered);

    $remarksAdditions = [];
    if (!$hasPoHeader && $po_number !== '') {
        $remarksAdditions[] = "PO Number: " . $po_number;
    }
    if ($supplierProvided && !$hasSupplierHeader && !$hasSupplierItem && $supplier !== '') {
        $remarksAdditions[] = "Supplier: " . $supplier;
    }
    if (!empty($remarksAdditions)) {
        $append = implode(' | ', $remarksAdditions);
        $remarks = $remarks === '' ? $append : ($remarks . ' | ' . $append);
    }

    $updateData = [
        'pr_number' => $pr_number,
        'requested_by' => $requested_by,
        'office' => $office,
        'total_amount' => $total_amount,
        'remarks' => $remarks,
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if (tableHasColumn($con, $prTable, 'po_number')) $updateData['po_number'] = $po_number;
    if (tableHasColumn($con, $prTable, 'po_no')) $updateData['po_no'] = $po_number;
    if (tableHasColumn($con, $prTable, 'purchase_order')) $updateData['purchase_order'] = $po_number;
    if (tableHasColumn($con, $prTable, 'end_use')) $updateData['end_use'] = $end_use;
    if (tableHasColumn($con, $prTable, 'purpose')) $updateData['purpose'] = $purpose;
    if (tableHasColumn($con, $prTable, 'fund_source')) $updateData['fund_source'] = $fund_source;
    if (tableHasColumn($con, $prTable, 'fund_cluster')) $updateData['fund_cluster'] = $fund_source;
    if (tableHasColumn($con, $prTable, 'section')) $updateData['section'] = trim((string)($data['section'] ?? ''));
    if ($supplierProvided && tableHasColumn($con, $prTable, 'supplier')) $updateData['supplier'] = $supplier;
    if (tableHasColumn($con, $prTable, 'approved_budget')) $updateData['approved_budget'] = is_numeric($approved_budget) ? (float)$approved_budget : null;
    if (tableHasColumn($con, $prTable, 'abc')) $updateData['abc'] = is_numeric($approved_budget) ? (float)$approved_budget : null;
    if (tableHasColumn($con, $prTable, 'designation')) $updateData['designation'] = $designation;
    if (tableHasColumn($con, $prTable, 'ppmp_attachments_required')) $updateData['ppmp_attachments_required'] = is_string($data['ppmp_attachments_required'] ?? null) ? $data['ppmp_attachments_required'] : json_encode($data['ppmp_attachments_required'] ?? []);
    if (tableHasColumn($con, $prTable, 'expected_delivery_date')) $updateData['expected_delivery_date'] = !empty($data['expected_delivery_date']) ? $data['expected_delivery_date'] : null;
    if (tableHasColumn($con, $prTable, 'procurement_mode')) $updateData['procurement_mode'] = trim((string)($data['procurement_mode'] ?? ''));
    if (tableHasColumn($con, $prTable, 'is_pr_locked')) $updateData['is_pr_locked'] = isset($data['is_pr_locked']) ? (int)$data['is_pr_locked'] : 0;

    $columnsMap = getTableColumnsMap($con, $prTable);
    $setParts = [];
    $types = '';
    $params = [];

    foreach ($updateData as $col => $val) {
        if (!isset($columnsMap[$col])) continue;
        $setParts[] = "`$col` = ?";
        if ($val === null) {
            $types .= 's';
            $params[] = null;
        } elseif (is_float($val) || is_int($val) || is_numeric($val)) {
            $types .= 'd';
            $params[] = (float)$val;
        } else {
            $types .= 's';
            $params[] = (string)$val;
        }
    }

    if (empty($setParts)) {
        setAlert('error', 'Failed to update Purchase Request: invalid table columns.');
        return false;
    }

    mysqli_begin_transaction($con);

    $sql = "UPDATE `$prTable` SET " . implode(', ', $setParts) . " WHERE id = ? AND deleted = 0";
    $stmt = $con->prepare($sql);
    if (!$stmt) {
        mysqli_rollback($con);
        setAlert('error', 'Failed to update Purchase Request: ' . mysqli_error($con));
        return false;
    }
    $typesWithId = $types . 'i';
    $paramsWithId = $params;
    $paramsWithId[] = $prId;
    bindParams($stmt, $typesWithId, $paramsWithId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        mysqli_rollback($con);
        setAlert('error', 'Failed to update Purchase Request: ' . mysqli_error($con));
        return false;
    }

    if (!tableExists($con, $itemTable) || !tableHasColumn($con, $itemTable, 'pr_id')) {
        mysqli_rollback($con);
        setAlert('error', 'Failed to update Purchase Request: items table missing.');
        return false;
    }

    $delStmt = $con->prepare("DELETE FROM `$itemTable` WHERE pr_id = ?");
    if (!$delStmt) {
        mysqli_rollback($con);
        setAlert('error', 'Failed to update Purchase Request: ' . mysqli_error($con));
        return false;
    }
    $delStmt->bind_param("i", $prId);
    $delOk = $delStmt->execute();
    $delStmt->close();

    if (!$delOk) {
        mysqli_rollback($con);
        setAlert('error', 'Failed to update Purchase Request: ' . mysqli_error($con));
        return false;
    }

    $itemColumnsMap = getTableColumnsMap($con, $itemTable);
    foreach ($items as $item) {
        $itemInsert = [
            'pr_id' => $prId,
            'stock_property_no' => $item['stock_property_no'] ?? '',
            'quantity' => $item['quantity'],
            'unit' => $item['unit'],
            'description' => $item['description'],
            'unit_cost' => $item['unit_cost'],
            'total_cost' => $item['total_cost'],
        ];
        if (isset($itemColumnsMap['item_description']) && !isset($itemColumnsMap['description'])) {
            $itemInsert['item_description'] = $item['description'];
        }
        if (isset($itemColumnsMap['amount']) && !isset($itemColumnsMap['total_cost'])) {
            $itemInsert['amount'] = $item['total_cost'];
        }
        if (isset($itemColumnsMap['supplier']) && $effectiveSupplierForItems !== '') {
            $itemInsert['supplier'] = $effectiveSupplierForItems;
        }

        $itemCols = [];
        $itemPlaceholders = [];
        $itemTypes = '';
        $itemParams = [];

        foreach ($itemInsert as $col => $val) {
            if (!isset($itemColumnsMap[$col])) continue;
            $itemCols[] = "`$col`";
            $itemPlaceholders[] = '?';
            if ($val === null) {
                $itemTypes .= 's';
                $itemParams[] = null;
            } elseif (is_float($val) || is_int($val) || is_numeric($val)) {
                $itemTypes .= 'd';
                $itemParams[] = (float)$val;
            } else {
                $itemTypes .= 's';
                $itemParams[] = (string)$val;
            }
        }

        if (empty($itemCols)) {
            mysqli_rollback($con);
            setAlert('error', 'Failed to update Purchase Request: invalid item columns.');
            return false;
        }

        $itemSql = "INSERT INTO `$itemTable` (" . implode(',', $itemCols) . ") VALUES (" . implode(',', $itemPlaceholders) . ")";
        $itemStmt = $con->prepare($itemSql);
        if (!$itemStmt) {
            mysqli_rollback($con);
            setAlert('error', 'Failed to update Purchase Request: ' . mysqli_error($con));
            return false;
        }

        bindParams($itemStmt, $itemTypes, $itemParams);
        $itemOk = $itemStmt->execute();
        $itemStmt->close();

        if (!$itemOk) {
            mysqli_rollback($con);
            setAlert('error', 'Failed to update Purchase Request: ' . mysqli_error($con));
            return false;
        }
    }

    mysqli_commit($con);

    setAlert('success', 'Purchase Request updated successfully!');
    return true;
}

// ?? Soft-delete a Purchase Request
function deletePR($con, $id){
    $stmt = $con->prepare("UPDATE purchase_requests SET deleted = 1, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        setAlert('error', 'Failed to delete Purchase Request: ' . mysqli_error($con));
        return false;
    }
    $intId = (int)$id;
    $stmt->bind_param("i", $intId);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        setAlert('error', 'Failed to delete Purchase Request: ' . mysqli_error($con));
        return false;
    }
    setAlert('success', 'Purchase Request deleted successfully!');
    return true;
}


// ?? Update status (Approve, Reject, PO Generated)
function updatePRStatus($con, $id, $status){
    $id = mysqli_real_escape_string($con, $id);
    $status = mysqli_real_escape_string($con, $status);

    if(!in_array($status, ['Pending','Approved','Rejected','PO Generated'])){
        setAlert('error', 'Invalid status value!');
        return false;
    }

    $query = "UPDATE purchase_requests SET status='$status', updated_at=NOW() WHERE id='$id'";
    if(!mysqli_query($con, $query)){
        setAlert('error', 'Failed to update status: ' . mysqli_error($con));
        return false;
    }

    setAlert('success', "Status updated to '$status' successfully!");

    // Log status change to document_logs
    $prCols = getTableColumnsMap($con, 'purchase_requests');
    $prSelectCols = isset($prCols['account_id']) ? 'pr_number, account_id' : 'pr_number';
    $prRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT $prSelectCols FROM purchase_requests WHERE id='$id' LIMIT 1"));
    if ($prRow) {
        $prNum = mysqli_real_escape_string($con, $prRow['pr_number']);
        $docRes = mysqli_query($con, "SELECT id FROM documents WHERE title LIKE '%$prNum%' LIMIT 1");
        if ($docRes) {
            $docRow = mysqli_fetch_assoc($docRes);
            if ($docRow) {
                $docId = (int)$docRow['id'];
                $logRemarks = "Status changed to $status for PR $prNum";
                $stmtLog = $con->prepare("INSERT INTO document_logs (document_id, status, remarks) VALUES (?, ?, ?)");
                if ($stmtLog) {
                    $stmtLog->bind_param("iss", $docId, $status, $logRemarks);
                    $stmtLog->execute();
                    $stmtLog->close();
                }
                mysqli_query($con, "UPDATE documents SET current_status='$status' WHERE id=$docId");
            }
        }
    }

    return true;
}

// ?? Session Alert helper
function setAlert($type, $message){
    if(!session_id()) session_start();
    $_SESSION['alert'] = ['type'=>$type, 'msg'=>$message];
}

// ?? Display SweetAlert
function showAlert(){
    if(!session_id()) session_start();
    if(isset($_SESSION['alert'])){
        $type    = $_SESSION['alert']['type'];
        $message = $_SESSION['alert']['msg'];
        $icon    = ($type === 'error') ? 'error' : (($type === 'success') ? 'success' : 'info');
        $title   = ($type === 'error') ? 'Error!' : (($type === 'success') ? 'Success!' : 'Notice');
        $bgColor = ($type === 'error') ? '#FEF2F2' : (($type === 'success') ? '#ECFDF5' : '#EFF6FF');
        $color   = ($type === 'error') ? '#C0272D' : (($type === 'success') ? '#006B3C' : '#1A4080');
        echo "<script>
        document.addEventListener('DOMContentLoaded', function(){
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: " . json_encode($icon) . ",
                title: " . json_encode($title) . ",
                text: " . json_encode($message) . ",
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                background: " . json_encode($bgColor) . ",
                color: " . json_encode($color) . ",
                customClass: {
                    popup: 'swal-toast-popup',
                    title: 'swal-toast-title',
                    timerProgressBar: 'swal-toast-bar'
                },
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        });
        </script>";
        unset($_SESSION['alert']);
    }
}
function generatePRNumber($con){
    $datePart = date('myd');
    
    $query = "SELECT pr_number FROM purchase_requests WHERE pr_number LIKE 'PR-$datePart-%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($con, $query);
    
    $lastNumber = 0;
    if($result && mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);
        $parts = explode('-', $row['pr_number']);
        $lastNumber = isset($parts[2]) ? (int)$parts[2] : 0;
    }

    $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    return "PR-$datePart-$newNumber";
}
?>
