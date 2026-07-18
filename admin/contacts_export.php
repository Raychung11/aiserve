<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/functions.php';

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM contacts WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (
        full_name LIKE ?
        OR company_name LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
        OR interest LIKE ?
    )";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

if ($status !== '' && in_array($status, contact_status_options(), true)) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY id DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=contacts_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'ID',
    'Full Name',
    'Company Name',
    'Email',
    'Phone',
    'Interest',
    'Company Size',
    'Message',
    'Source Page',
    'Status',
    'Created At'
]);

foreach ($rows as $r) {
    fputcsv($output, [
        $r['id'],
        $r['full_name'],
        $r['company_name'],
        $r['email'],
        $r['phone'],
        $r['interest'],
        $r['company_size'],
        $r['message'],
        $r['source_page'],
        $r['status'],
        $r['created_at']
    ]);
}

fclose($output);
exit;