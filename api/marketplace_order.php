<?php
/**
 * Marketplace Order API
 * POST /api/marketplace_order
 * Creates an order for a marketplace product (AJAX endpoint)
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';

header('Content-Type: application/json; charset=utf-8');

auth_start_session();
if (!auth_check() || auth_role() !== 'user') {
    http_response_code(401);
    echo json_encode(['error' => 'Sila log masuk sebagai pengguna.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Kaedah tidak dibenarkan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// CSRF
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');
auth_start_session();
$stored = $_SESSION['csrf_token'] ?? '';
if (!$stored || !hash_equals($stored, $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF tidak sah.']);
    exit;
}

$product_id = (int)($input['product_id'] ?? 0);
$quantity   = max(1, (int)($input['quantity'] ?? 1));
$address    = trim($input['delivery_address'] ?? '');

if (!$product_id) {
    echo json_encode(['error' => 'Produk tidak sah.']);
    exit;
}

$db      = getDB();
$user_id = auth_id();

// Get product
$p_stmt = $db->prepare("SELECT mp.*,m.user_id AS merchant_user_id FROM marketplace_products mp JOIN merchants m ON m.id=mp.merchant_id WHERE mp.id=? AND mp.status='active' AND mp.deleted_at IS NULL");
$p_stmt->execute([$product_id]);
$product = $p_stmt->fetch();

if (!$product) {
    echo json_encode(['error' => 'Produk tidak tersedia.']);
    exit;
}

// Cannot buy own products
if ((int)$product['merchant_user_id'] === $user_id) {
    echo json_encode(['error' => 'Anda tidak boleh membeli produk anda sendiri.']);
    exit;
}

// Stock check
if ((int)$product['stock_quantity'] < $quantity) {
    echo json_encode(['error' => 'Stok tidak mencukupi. Baki stok: ' . $product['stock_quantity']]);
    exit;
}

// Balance check
$balance     = get_wallet_balance($user_id);
$total_points = bcmul((string)$product['price_points'], (string)$quantity, GOLD_POINT_DECIMALS);
if (bccomp($balance['points'], $total_points, GOLD_POINT_DECIMALS) < 0) {
    echo json_encode(['error' => 'Baki mata tidak mencukupi. Diperlukan: ' . gold_format_points($total_points) . ' pts, Baki: ' . gold_format_points($balance['points']) . ' pts.']);
    exit;
}

$price      = get_active_gold_price();
$price_snap = $price ? (string)$price['price_per_g'] : '0';
$total_grams = gold_grams_from_points($total_points);
$total_rm    = $price ? gold_rm_from_points($total_points, $price_snap) : '0.00';

$buyer_wallet    = get_wallet($user_id);
$merchant_wallet = get_wallet((int)$product['merchant_user_id']);

if (!$buyer_wallet || !$merchant_wallet) {
    echo json_encode(['error' => 'Ralat dompet. Sila hubungi sokongan.']);
    exit;
}

$db->beginTransaction();
try {
    // Deduct stock
    $db->prepare("UPDATE marketplace_products SET stock_quantity=stock_quantity-? WHERE id=? AND stock_quantity>=?")
       ->execute([$quantity, $product_id, $quantity]);

    // Create order
    $db->prepare("INSERT INTO marketplace_orders (merchant_id,buyer_user_id,total_points,total_grams,total_rm_value,price_per_g_snapshot,delivery_address,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,'paid',NOW(),NOW())")
       ->execute([$product['merchant_id'], $user_id, $total_points, $total_grams, $total_rm, $price_snap, $address]);
    $order_id = (int)$db->lastInsertId();

    // Create order item
    $db->prepare("INSERT INTO marketplace_order_items (order_id,product_id,quantity,unit_price_points,subtotal_points,price_per_g_snapshot) VALUES (?,?,?,?,?,?)")
       ->execute([$order_id, $product_id, $quantity, $product['price_points'], $total_points, $price_snap]);

    // Debit buyer
    ledger_debit(
        (int)$buyer_wallet['id'],
        $user_id,
        $total_points,
        $total_grams,
        $total_rm,
        $price_snap,
        'marketplace_purchase',
        $order_id,
        'Pembelian: ' . $product['name'] . ' (x' . $quantity . ')'
    );

    // Credit merchant
    ledger_credit(
        (int)$merchant_wallet['id'],
        (int)$product['merchant_user_id'],
        $total_points,
        $total_grams,
        $total_rm,
        $price_snap,
        'marketplace_sale',
        $order_id,
        'Jualan: ' . $product['name'] . ' (x' . $quantity . ') - Pesanan #' . $order_id
    );

    $db->commit();

    echo json_encode([
        'ok'       => true,
        'order_id' => $order_id,
        'message'  => 'Pesanan berjaya dibuat! Pedagang akan memproses pesanan anda.',
        'deducted_points' => gold_format_points($total_points),
        'new_balance'     => gold_format_points(get_wallet_balance($user_id)['points']),
    ]);
} catch (\Throwable $e) {
    $db->rollBack();
    error_log('Marketplace order error: ' . $e->getMessage());
    echo json_encode(['error' => 'Ralat semasa membuat pesanan. Sila cuba lagi.']);
}
