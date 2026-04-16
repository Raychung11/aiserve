<?php
/**
 * Kasih Gold Easy — Main Router
 * Routes all requests to the appropriate page files.
 * Works with clean URLs via .htaccess or /?page=xxx query string.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

// Start session early
auth_start_session();

// ── Determine request path ──────────────────────────────────────
$request_uri  = $_SERVER['REQUEST_URI'] ?? '/';
$script_name  = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$base_path    = rtrim(dirname($script_name), '/');
$path         = str_replace($base_path, '', parse_url($request_uri, PHP_URL_PATH) ?? '/');
$path         = '/' . trim($path, '/');

// Strip sub-segments for route matching, keep full path for sub-routes
$segments     = array_values(array_filter(explode('/', $path)));
$first        = $segments[0] ?? '';
$second       = $segments[1] ?? '';

// ── Handle logout immediately ───────────────────────────────────
if ($first === 'logout') {
    auth_logout();
    // auth_logout redirects and exits
}

// ── Route table ─────────────────────────────────────────────────
// public pages (no auth required)
$public_routes = [
    ''           => 'public/landing.php',
    'login'      => 'public/login.php',
    'register'   => 'public/register.php',
    'forgot'     => 'public/forgot_password.php',
    'reset'      => 'public/reset_password.php',
    'terms'      => 'public/terms.php',
    'privacy'    => 'public/privacy.php',
    'marketplace'=> 'public/marketplace.php',
    'product'    => 'public/product.php',
    'campaigns'  => 'public/campaigns.php',
];

// user pages (user + merchant + admin)
$user_routes = [
    'dashboard'  => 'public/dashboard.php',
    'wallet'     => 'public/wallet.php',
    'buy-gold'   => 'public/buy_gold.php',
    'transfer'   => 'public/transfer.php',
    'referrals'  => 'public/referrals.php',
    'profile'    => 'public/profile.php',
];

// admin pages
$admin_routes = [
    ''           => 'admin/index.php',
    'gold-price' => 'admin/gold_price.php',
    'users'      => 'admin/users.php',
    'merchants'  => 'admin/merchants.php',
    'purchases'  => 'admin/purchases.php',
    'transfers'  => 'admin/transfers.php',
    'marketplace'=> 'admin/marketplace.php',
    'campaigns'  => 'admin/campaigns.php',
    'payouts'    => 'admin/payouts.php',
    'referrals'  => 'admin/referrals.php',
    'settings'   => 'admin/settings.php',
    'ai-settings'=> 'admin/ai_settings.php',
    'audit-logs' => 'admin/audit_logs.php',
    'reports'    => 'admin/reports.php',
];

// merchant pages
$merchant_routes = [
    ''           => 'merchant/index.php',
    'products'   => 'merchant/products.php',
    'orders'     => 'merchant/orders.php',
    'payouts'    => 'merchant/payouts.php',
    'referrals'  => 'merchant/referrals.php',
    'profile'    => 'merchant/profile.php',
];

// api routes
$api_routes = [
    'ai-chat'         => 'api/ai_chat.php',
    'payment-callback'=> 'api/payment_callback.php',
    'wallet-balance'  => 'api/wallet_balance.php',
    'marketplace-order'=> 'api/marketplace_order.php',
];

// ── Routing ─────────────────────────────────────────────────────

// /api/* routes
if ($first === 'api') {
    $api_key = $second;
    if (isset($api_routes[$api_key])) {
        $file = __DIR__ . '/' . $api_routes[$api_key];
        if (file_exists($file)) { require $file; exit; }
    }
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// /admin/* routes
if ($first === 'admin') {
    $admin_key = $second;
    if (isset($admin_routes[$admin_key])) {
        $file = __DIR__ . '/' . $admin_routes[$admin_key];
        if (file_exists($file)) { require $file; exit; }
    }
    // Fallback to admin index
    $file = __DIR__ . '/admin/index.php';
    if (file_exists($file)) { require $file; exit; }
}

// /merchant/* routes
if ($first === 'merchant') {
    $merchant_key = $second;
    if (isset($merchant_routes[$merchant_key])) {
        $file = __DIR__ . '/' . $merchant_routes[$merchant_key];
        if (file_exists($file)) { require $file; exit; }
    }
    $file = __DIR__ . '/merchant/index.php';
    if (file_exists($file)) { require $file; exit; }
}

// Public routes (no auth check)
if (isset($public_routes[$first])) {
    $file = __DIR__ . '/' . $public_routes[$first];
    if (file_exists($file)) { require $file; exit; }
}

// User-authenticated routes
if (isset($user_routes[$first])) {
    $file = __DIR__ . '/' . $user_routes[$first];
    if (file_exists($file)) { require $file; exit; }
}

// Root redirect
if ($path === '/' || $path === '') {
    if (auth_check()) {
        $role = auth_role();
        if ($role === 'super_admin') redirect(APP_URL . '/admin');
        elseif ($role === 'merchant') redirect(APP_URL . '/merchant');
        else redirect(APP_URL . '/dashboard');
    } else {
        $file = __DIR__ . '/public/landing.php';
        if (file_exists($file)) { require $file; exit; }
        redirect(APP_URL . '/login');
    }
    exit;
}

// 404
http_response_code(404);
require_once __DIR__ . '/inc/layout.php';
layout_head('Halaman Tidak Dijumpai');
echo '<div style="display:flex;align-items:center;justify-content:center;min-height:80vh;text-align:center;padding:40px;">';
echo '<div><div style="font-size:4rem;margin-bottom:16px;">✦</div>';
echo '<h1 style="font-size:2rem;font-weight:800;color:var(--kasih-dark);">404 — Halaman Tidak Dijumpai</h1>';
echo '<p style="color:#6B7280;margin:12px 0 24px;">Halaman yang anda cari tidak wujud atau telah dipindahkan.</p>';
echo '<a href="' . APP_URL . '/" class="btn-gold">Kembali ke Laman Utama</a>';
echo '</div></div>';
layout_footer();
