<?php
if (!isset($_SESSION)) {
    session_start();
}
include __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../../config/localdb.php';


use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// ── Data helpers (called by suppliers.php via include) ──────────────────────

function getSuppliers($con, $search, $filterStatus, $filterCategory, $perPage, $offset) {
    $where  = "WHERE deleted=0";
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR location LIKE ? OR supplier_code LIKE ?)";
        $like   = "%$search%";
        $params = array_merge($params, [$like, $like, $like, $like, $like]);
        $types .= 'sssss';
    }
    if ($filterStatus !== '') {
        $where   .= " AND status=?";
        $params[] = $filterStatus;
        $types   .= 's';
    }
    if ($filterCategory !== '') {
        $where   .= " AND category=?";
        $params[] = $filterCategory;
        $types   .= 's';
    }

    // total count
    $countSql = "SELECT COUNT(*) AS c FROM suppliers $where";
    $stmt = $con->prepare($countSql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    // rows
    $stmt = $con->prepare("SELECT * FROM suppliers $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $allParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($types . 'ii', ...$allParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return ['total' => $total, 'rows' => $rows];
}

function getSupplierCategories($con) {
    $cats = [];
    $res  = $con->query("SELECT DISTINCT category FROM suppliers WHERE deleted=0 AND category != '' ORDER BY category");
    if ($res) while ($r = $res->fetch_assoc()) $cats[] = $r['category'];
    return $cats;
}

// ── AJAX / action handler ────────────────────────────────────────────────────

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === '') return; // included as library — no action, just load functions

if ($action === 'save') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status       = in_array($_POST['status'] ?? '', ['Active', 'Inactive']) ? $_POST['status'] : 'Active';
    $position     = trim($_POST['position']     ?? '');
    $company_name = trim($_POST['company_name'] ?? '');

    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit;
    }

    if ($id > 0) {
        $stmt = $con->prepare("UPDATE suppliers SET name=?,company_name=?,email=?,phone=?,location=?,category=?,status=?,position=? WHERE id=? AND deleted=0");
        $stmt->bind_param('ssssssssi', $name, $company_name, $email, $phone, $location, $category, $status, $position, $id);
    } else {
        $year = date('Y');
        $res  = $con->query("SELECT COUNT(*) AS c FROM suppliers WHERE supplier_code LIKE 'SUP-$year-%'");
        $cnt  = $res ? (int)$res->fetch_assoc()['c'] : 0;
        $code = 'SUP-' . $year . '-' . str_pad($cnt + 1, 4, '0', STR_PAD_LEFT);
        $stmt = $con->prepare("INSERT INTO suppliers (supplier_code,name,company_name,email,phone,location,category,status,position) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssss', $code, $name, $company_name, $email, $phone, $location, $category, $status, $position);
    }

    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Saved successfully' : $con->error]);
    exit;
}

if ($action === 'delete') {
    $id   = (int)($_POST['id'] ?? 0);
    $stmt = $con->prepare("UPDATE suppliers SET deleted=1 WHERE id=?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
}

if ($action === 'get') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $con->prepare("SELECT * FROM suppliers WHERE id=? AND deleted=0 LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode($row ?: []);
    exit;
}

if ($action === 'word') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $con->prepare("SELECT * FROM suppliers WHERE id=? AND deleted=0 LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$s) { http_response_code(404); echo 'Supplier not found'; exit; }

    $phpWord = new PhpWord();
    $section = $phpWord->addSection(['marginTop' => 720, 'marginBottom' => 720, 'marginLeft' => 1080, 'marginRight' => 1080]);

    $section->addText('SUPPLIER PROFILE', ['bold' => true, 'size' => 16, 'color' => '0D2B55'], ['alignment' => 'center', 'spaceAfter' => 200]);
    $section->addText('Schools Division Office of Dasmariñas', ['size' => 10, 'color' => '666666'], ['alignment' => 'center', 'spaceAfter' => 400]);

    $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'D6E1EF', 'cellMargin' => 120]);

    $fields = [
        'Supplier Code' => $s['supplier_code'],
        'Supplier Name' => $s['name'],
        'Email'         => $s['email'] ?: '—',
        'Phone'         => $s['phone'] ?: '—',
        'Location'      => $s['location'] ?: '—',
        'Category'      => $s['category'] ?: '—',
        'Status'        => $s['status'],
        'Date Added'    => date('F d, Y', strtotime($s['created_at'])),
    ];

    foreach ($fields as $label => $value) {
        $row   = $table->addRow();
        $row->addCell(2500, ['bgColor' => 'E8EFF9'])->addText($label, ['bold' => true, 'size' => 11]);
        $row->addCell(6000)->addText(htmlspecialchars($value), ['size' => 11]);
    }

    $safe     = preg_replace('/[^A-Za-z0-9_-]+/', '_', $s['name']);
    $filename = 'Supplier_' . $safe . '.docx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
