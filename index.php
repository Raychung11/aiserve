<?php
/**
 * Adcellent ESG OS — Main Router
 * Handles all page routing for Hostinger deployment
 *
 * URL format: /?page=dashboard (query-string mode — no .htaccess needed)
 * With .htaccess: /dashboard (clean URLs)
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

Auth::startSession();

// Determine the requested page from URL
// Supports both /page-name (via .htaccess rewrite) and /?page=name
$requestUri   = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName   = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath     = rtrim(dirname($scriptName), '/');
$path         = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH) ?? '/');
$path         = '/' . trim($path, '/');

// Extract page name from path or query string
if ($path !== '/' && $path !== '') {
    $page = ltrim($path, '/');
    // Convert path params: /data-entry?cat=environment → data-entry
    $page = explode('/', $page)[0];
} else {
    $page = $_GET['page'] ?? '';
}

// Normalize page name: replace hyphens and map aliases
$page = strtolower(preg_replace('/[^a-z0-9_-]/', '', $page));

// Route table: page slug → file path
$routes = [
    ''             => 'pages/landing.php',
    'landing'      => 'pages/landing.php',
    'dashboard'    => 'pages/dashboard.php',
    'login'        => 'pages/login.php',
    'register'     => 'pages/register.php',
    'logout'       => null, // handled below
    'onboarding'   => 'pages/onboarding.php',
    'data-entry'   => 'pages/data_entry.php',
    'data_entry'   => 'pages/data_entry.php',
    'gap-analysis' => 'pages/gap_analysis.php',
    'gap_analysis' => 'pages/gap_analysis.php',
    'reports'      => 'pages/reports.php',
    'companies'    => 'pages/companies.php',
    'carbon'       => 'pages/carbon.php',
    'benchmarking' => 'pages/benchmarking.php',
    'benchmark'    => 'pages/benchmarking.php',
    'admin'        => 'pages/admin.php',
    'pricing'      => 'pages/pricing.php',
    'billing'       => 'pages/billing.php',
    'team'          => 'pages/team.php',
    'departments'   => 'pages/departments.php',
    'action-plans'  => 'pages/action_plans.php',
    'action_plans'  => 'pages/action_plans.php',
    'notifications' => 'pages/notifications.php',
    'kpi-trends'    => 'pages/kpi_trends.php',
    'kpi_trends'    => 'pages/kpi_trends.php',
];

// Handle logout
if ($page === 'logout') {
    require_once __DIR__ . '/src/Company.php';
    require_once __DIR__ . '/src/ESGDataManager.php';
    require_once __DIR__ . '/src/GapAnalyzer.php';
    require_once __DIR__ . '/src/ReportGenerator.php';
    Auth::logout();
    header('Location: ' . APP_URL . '/login');
    exit;
}

// Resolve file
if (isset($routes[$page])) {
    $file = __DIR__ . '/' . $routes[$page];
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
}

// 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 — Adcellent ESG OS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
  <div class="text-center">
    <div style="font-size:60px">🌿</div>
    <h1 class="fw-bold mt-3">Page Not Found</h1>
    <p class="text-muted">The page "<?= htmlspecialchars($page) ?>" doesn't exist.</p>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-success mt-2">Go to Dashboard</a>
  </div>
</body>
</html>
