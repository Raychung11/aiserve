<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/inc/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$full_name    = trim($_POST['full_name'] ?? '');
$company_name = trim($_POST['company_name'] ?? '');
$email        = trim($_POST['email'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$interest     = trim($_POST['interest'] ?? '');
$company_size = trim($_POST['company_size'] ?? '');
$message      = trim($_POST['message'] ?? '');

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$source_page = 'homepage';
$redirect_target = '/?success=1#subscribe';

if (strpos($referer, '/contact.php') !== false) {
    $source_page = 'contact';
    $redirect_target = '/contact.php?success=1';
}

if ($full_name === '' || $company_name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $interest === '') {
    if ($source_page === 'contact') {
        redirect('/contact.php?error=' . urlencode('Please complete all required fields correctly.'));
    }
    redirect('/?error=' . urlencode('Please complete all required fields correctly.'));
}

$token = bin2hex(random_bytes(16));

$stmt = db()->prepare("
    INSERT INTO contacts
    (full_name, company_name, email, phone, interest, company_size, message, source_page, status, is_subscribed, unsubscribe_token, created_at)
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, 'new', 1, ?, NOW())
");

$stmt->execute([
    $full_name,
    $company_name,
    $email,
    $phone,
    $interest,
    $company_size,
    $message,
    $source_page,
    $token
]);

redirect($redirect_target);