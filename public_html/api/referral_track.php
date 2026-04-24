<?php
/**
 * MM2H 管家 — Referral Tracking API
 * Records a referral link click before redirecting to registration.
 * Usage: /api/referral_track.php?ref=CODE
 */

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();

$ref = strtoupper(trim($_GET['ref'] ?? ''));

if ($ref) {
    // Validate that the referral code exists
    $stmt = db()->prepare('SELECT id FROM users WHERE referral_code = ? AND status = "active"');
    $stmt->execute([$ref]);
    if ($stmt->fetch()) {
        $_SESSION['pending_referral'] = $ref;
        log_activity('referral_click_' . $ref);
    }
}

// Redirect to registration with ref param
$register_url = APP_URL . '/register' . ($ref ? '?ref=' . urlencode($ref) : '');
header('Location: ' . $register_url);
exit;
