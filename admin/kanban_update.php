<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

verify_csrf();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = trim($_POST['status'] ?? '');

$allowed = ['new', 'contacted', 'qualified', 'closed'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    http_response_code(400);
    exit('Invalid request');
}

$stmt = db()->prepare("UPDATE contacts SET status = ? WHERE id = ?");
$stmt->execute([$status, $id]);

echo 'ok';