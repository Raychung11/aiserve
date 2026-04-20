<?php
/**
 * Marketplace Order API — AJAX endpoint
 * POST /api/marketplace_order
 * Mirrors the logic in public/product.php but returns JSON
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
$token  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');
$stored = $_SESSION['csrf_token'] ?? '';
if (!$stored || !hash_equals($stored, $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF tidak sah.']);
    exit;
}

$product_id = (int)($input['product_id'] ?? 0);
$quantity   = max(1, (int)($input['quantity'] ?? 1));

if (!$product_id) {
    echo json_encode(['error' => 'Produk tidak sah.']);
    exit;
}

$db      = getDB();
$user_id = auth_id();

// Get product with merchant user_id
$p_stmt = $db->prepare("SELECT mp.*, m.user_id AS merchant_user_id
    FROM marketplace_products mp
    JOIN merchants m ON m.id = mp.merchant_id
    WHERE mp.id = ? AND mp.status = 'active' AND mp.deleted_at IS NULL");
$p_stmt->execute([$product_id]);
$product = $p_stmt->fetch();

if (!$product) {
    echo json_encode(['error' => 'Produk tidak tersedia.']);
    exit;
}

if ((int)$product['merchant_user_id'] === $user_id) {
    echo json_encode(['error' => 'Anda tidak boleh membeli produk anda sendiri.']);
    exit;
}

if ($product['stock_qty'] !== null && (int)$product['stock_qty'] < $quantity) {
    echo json_encode(['error' => 'Stok tidak mencukupi. Baki stok: ' . $product['stock_qty']]);
    exit;
}

$balance     = get_wallet_balance($user_id);
$total_pts   = bcmul((string)$product['points_price'], (string)$quantity, GOLD_POINT_DECIMALS);
if (bccomp($balance['points'], $total_pts, GOLD_POINT_DECIMALS) < 0) {
    echo json_encode(['error' => 'Baki mata tidak mencukupi. Diperlukan: ' . gold_format_points($total_pts) . ' pts, Baki: ' . gold_format_points($balance['points']) . ' pts.']);
    exit;
}

$price      = get_active_gold_price();
$price_snap = $price ? (string)$price['price_per_g'] : '390.0000';
$grams      = gold_grams_from_points($total_pts);
$total_rm   = gold_rm_from_points($total_pts, $price_snap);

$buyer_wid    = ensure_wallet_exists($user_id);
$merchant_wid = ensure_wallet_exists((int)$product['merchant_user_id']);

$db->beginTransaction();
try {
    // Deduct stock
    if ($product['stock_qty'] !== null) {
        $db->prepare("UPDATE marketplace_products SET stock_qty=stock_qty-? WHERE id=? AND stock_qty>=?")
           ->execute([$quantity, $product_id, $quantity]);
    }

    // Create order
    $db->prepare("INSERT INTO marketplace_orders (buyer_user_id,merchant_id,status,total_points,total_rm_value,created_at,updated_at) VALUES (?,?,'pending',?,?,NOW(),NOW())")
       ->execute([$user_id, $product['merchant_id'], $total_pts, $total_rm]);
    $order_id = (int)$db->lastInsertId();

    // Create order item
    $db->prepare("INSERT INTO marketplace_order_items (order_id,product_id,qty,points_price,rm_reference_value,product_title,created_at) VALUES (?,?,?,?,?,?,NOW())")
       ->execute([$order_id, $product_id, $quantity, $product['points_price'], $product['rm_reference_value'], $product['title']]);

    // Debit buyer
    $bid = ledger_debit($buyer_wid, $user_id, $total_pts, $grams, $total_rm, $price_snap,
        'marketplace_spend', $order_id, 'Pembelian: ' . $product['title'] . ' (x' . $quantity . ')');

    // Credit merchant
    $mid = ledger_credit($merchant_wid, (int)$product['merchant_user_id'], $total_pts, $grams, $total_rm, $price_snap,
        'marketplace_receive', $order_id, 'Jualan: ' . $product['title'] . ' (x' . $quantity . ') #' . $order_id);

    // Mark order as paid
    $db->prepare("UPDATE marketplace_orders SET status='paid_by_points',buyer_ledger_id=?,merchant_ledger_id=?,updated_at=NOW() WHERE id=?")
       ->execute([$bid, $mid, $order_id]);

    $db->commit();
    audit_log($user_id,'user','marketplace_purchase','marketplace_orders',$order_id,null,['product'=>$product['title'],'pts'=>$total_pts]);

    echo json_encode([
        'ok'              => true,
        'order_id'        => $order_id,
        'message'         => 'Pesanan berjaya dibuat! Pedagang akan memproses dalam masa terdekat.',
        'deducted_points' => gold_format_points($total_pts),
        'new_balance'     => gold_format_points(get_wallet_balance($user_id)['points']),
    ]);
} catch (\Throwable $e) {
    $db->rollBack();
    error_log('Marketplace order API error: ' . $e->getMessage());
    echo json_encode(['error' => 'Ralat semasa membuat pesanan. Sila cuba lagi.']);
}
