<?php
/**
 * MM2H 管家 — Language switcher endpoint
 */
require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/includes/auth.php';
start_secure_session();

$allowed = ['en', 'zh_hant', 'zh_hans'];
$set     = $_GET['set'] ?? 'en';
if (in_array($set, $allowed, true)) {
    $_SESSION['lang'] = $set;
}

$return = $_GET['return'] ?? '/';
// Validate return URL is relative
$parsed = parse_url($return);
if (!empty($parsed['host'])) {
    $return = '/';
}
header('Location: ' . $return);
exit;
