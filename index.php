<?php
define('ROOT_PATH', __DIR__);

require_once ROOT_PATH.'/config/app.php';
require_once ROOT_PATH.'/config/database.php';
require_once ROOT_PATH.'/config/billplz.php';
require_once ROOT_PATH.'/src/Database.php';
require_once ROOT_PATH.'/src/Auth.php';
require_once ROOT_PATH.'/src/ActivityLog.php';
require_once ROOT_PATH.'/src/ComplianceEngine.php';
require_once ROOT_PATH.'/src/ROIEngine.php';
require_once ROOT_PATH.'/src/StrategyEngine.php';
require_once ROOT_PATH.'/src/BillplzService.php';

Auth::start();

// Parse path
$requestUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDir   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$path        = substr($requestUri, strlen($scriptDir));
$path        = trim($path, '/');
// Route map: URL segment => page file
$routes = [
    ''               => 'pages/landing.php',
    'dashboard'      => 'pages/dashboard.php',
    'login'          => 'pages/login.php',
    'register'       => 'pages/register.php',
    'logout'         => 'pages/logout.php',
    'properties'     => 'pages/properties.php',
    'revenue'        => 'pages/revenue.php',
    'tenancies'      => 'pages/tenancies.php',
    'agents'         => 'pages/agents.php',
    'investment'              => 'pages/investment.php',
    'subscription'            => 'pages/subscription.php',
    'roi-calculator'          => 'pages/roi_calculator.php',
    'owners'                  => 'pages/owners.php',
    'owner-portal'            => 'pages/owner_portal.php',
    'owner-documents'         => 'pages/owner_documents.php',
    'owner-document-download' => 'pages/owner_document_download.php',
    'renters'                 => 'pages/renters.php',
    'rent-payments'           => 'pages/rent_payments.php',
    'einvoice'                => 'pages/einvoice.php',
    'cp58'                    => 'pages/cp58.php',
    'str-report'              => 'pages/str_report.php',
];

$page = $routes[$path] ?? null;

if ($page && file_exists(ROOT_PATH.'/'.$page)) {
    require_once ROOT_PATH.'/'.$page;
} else {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>404 — STRHub AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </head>
    <body class="d-flex align-items-center justify-content-center" style="min-height:100vh;background:#f8fafc;">
    <div class="text-center">
      <div style="font-size:4rem;">🏠</div>
      <h2 class="fw-bold mt-3">Page Not Found</h2>
      <p class="text-muted">The page you're looking for doesn't exist.</p>
      <a href="<?= APP_URL ?>/dashboard" class="btn btn-primary">Back to Dashboard</a>
    </div>
    </body>
    </html>
    <?php
}
