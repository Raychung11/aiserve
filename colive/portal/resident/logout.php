<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../helpers.php';

startSecureSession();

// Only log out on POST to prevent CSRF / accidental logout via link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/portal/resident/dashboard.php');
    exit;
}

verifyCsrf();

// Remove resident session keys without touching any other session data
// (e.g. an operator might share the browser in dev; be surgical)
unset(
    $_SESSION['resident_id'],
    $_SESSION['resident_company_id'],
    $_SESSION['resident_name'],
    $_SESSION['resident_email']
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

header('Location: ' . APP_URL . '/portal/resident/login.php');
exit;
