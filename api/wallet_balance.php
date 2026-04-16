<?php
/**
 * Wallet Balance API
 * GET /api/wallet_balance
 * Returns current user's wallet balance as JSON
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

auth_start_session();
if (!auth_check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Tidak dibenarkan.']);
    exit;
}

$user_id = auth_id();
$balance = get_wallet_balance($user_id);
$price   = get_active_gold_price();

echo json_encode([
    'ok'          => true,
    'points'      => $balance['points'],
    'grams'       => $balance['grams'],
    'rm_value'    => $balance['rm_value'],
    'price_per_g' => $balance['price_per_g'],
    'formatted'   => [
        'points'   => gold_format_points($balance['points']) . ' pts',
        'grams'    => gold_format_grams($balance['grams']),
        'rm_value' => gold_format_rm($balance['rm_value']),
    ],
    'price_active' => $price !== null,
]);
