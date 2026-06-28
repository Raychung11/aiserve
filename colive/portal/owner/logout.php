<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';

startSecureSession();

// Only process logout on POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/portal/owner/dashboard.php');
    exit;
}

verifyCsrf();

// Clear owner-specific session keys
unset(
    $_SESSION['owner_id'],
    $_SESSION['owner_company_id'],
    $_SESSION['owner_name'],
    $_SESSION['owner_email']
);

// Fully destroy the session and clear the cookie
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $p['path'],
        $p['domain'],
        $p['secure'],
        $p['httponly']
    );
}
session_destroy();

header('Location: ' . APP_URL . '/portal/owner/login.php');
exit;
