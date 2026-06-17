<?php
/**
 * MM2H 管家 Platform — Front Controller / Router
 */

require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/language.php';
start_secure_session();

// Resolve route from clean URL (via .htaccess) or query string
$route = trim($_GET['_route'] ?? $_GET['page'] ?? '', '/');
$route = strtolower(preg_replace('/[^a-z0-9_\-\/]/', '', $route));
$route = explode('/', $route)[0]; // only first segment

// Route map: slug => file
$routes = [
    ''          => 'pages/home.php',
    'home'      => 'pages/home.php',
    'about'     => 'pages/about.php',
    'services'  => 'pages/services.php',
    'pricing'   => 'pages/pricing.php',
    'contact'   => 'pages/contact.php',
    'mm2h-guide'=> 'pages/mm2h_guide.php',
    'login'     => 'pages/login.php',
    'register'  => 'pages/register.php',
    'logout'    => 'logout.php',
    'lang'      => 'lang.php',

    // Member area
    'member'           => 'member/dashboard.php',
    'dashboard'        => 'member/dashboard.php',
    'onboarding'       => 'member/onboarding.php',
    'checklist'        => 'member/checklist.php',
    'documents'        => 'member/documents.php',
    'case-progress'    => 'member/case_progress.php',
    'property-match'   => 'member/property_match.php',
    'bank-support'     => 'member/bank_support.php',
    'business-network' => 'member/business_network.php',
    'profile'          => 'member/profile.php',

    // Admin area
    'admin'            => 'admin/dashboard.php',

    // Partner area
    'partner'          => 'partner/dashboard.php',
];

if (isset($routes[$route])) {
    $file = __DIR__ . '/' . $routes[$route];
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

// Admin sub-routes  (admin/*)
if (str_starts_with($route, 'admin')) {
    $sub = ltrim(str_replace('admin', '', $route), '-/');
    $map = [
        'dashboard'   => 'admin/dashboard.php',
        'leads'       => 'admin/leads.php',
        'cases'       => 'admin/cases.php',
        'users'       => 'admin/users.php',
        'partners'    => 'admin/partners.php',
        'commissions' => 'admin/commissions.php',
        'properties'  => 'admin/properties.php',
        'banks'       => 'admin/banks.php',
        'services'    => 'admin/services.php',
        'mm2h-faq'    => 'admin/mm2h_faq.php',
        'settings'    => 'admin/settings.php',
    ];
    if (isset($map[$sub])) {
        require __DIR__ . '/' . $map[$sub];
        exit;
    }
    require __DIR__ . '/admin/dashboard.php';
    exit;
}

// Partner sub-routes
if (str_starts_with($route, 'partner')) {
    $sub = ltrim(str_replace('partner', '', $route), '-/');
    $map = [
        'dashboard'   => 'partner/dashboard.php',
        'leads'       => 'partner/leads.php',
        'referrals'   => 'partner/referrals.php',
        'commissions' => 'partner/commissions.php',
        'profile'     => 'partner/profile.php',
    ];
    if (isset($map[$sub])) {
        require __DIR__ . '/' . $map[$sub];
        exit;
    }
    require __DIR__ . '/partner/dashboard.php';
    exit;
}

// Root redirect
if ($route === '' || $route === 'index') {
    if (auth_check()) {
        redirect(is_admin() ? 'admin/dashboard' : (is_partner() ? 'partner/dashboard' : 'member/dashboard'));
    } else {
        redirect('');
    }
}

// 404
http_response_code(404);
$page_title = '404 — Page Not Found';
require __DIR__ . '/includes/header.php';
?>
<div class="container min-vh-75 d-flex align-items-center justify-content-center py-5">
  <div class="text-center">
    <div class="display-1 fw-bold text-gold">404</div>
    <h2 class="mt-3">Page Not Found</h2>
    <p class="text-muted">The page "<?= h($route) ?>" doesn't exist.</p>
    <a href="<?= APP_URL ?>" class="btn btn-gold mt-3"><i class="bi bi-house me-2"></i>Return Home</a>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
