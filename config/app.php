<?php
define('APP_NAME',     'Roomee');
define('APP_VERSION',  '1.0.0');
define('APP_CURRENCY', 'RM');
define('APP_TIMEZONE', 'Asia/Kuala_Lumpur');

date_default_timezone_set(APP_TIMEZONE);

// Trial period in days
define('TRIAL_DAYS', 14);

// Platform revenue share taken before agent commission
define('PLATFORM_REVENUE_SHARE', 0.15);   // 15%

// Subscription plan limits
define('PLAN_LIMITS', [
    'starter'    => ['properties' => 5,    'agents' => 2,    'price_monthly' => 500,  'price_annual' => 5000],
    'growth'     => ['properties' => 20,   'agents' => 10,   'price_monthly' => 1500, 'price_annual' => 15000],
    'enterprise' => ['properties' => 9999, 'agents' => 9999, 'price_monthly' => 4000, 'price_annual' => 40000],
]);

define('APP_URL', 'https://roomee.my');

// ── Debug mode ─────────────────────────────────────────────────────────────
// Set to true ONLY on local/staging, never in production
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// Global exception handler — shows clean error page instead of stack trace
set_exception_handler(function (Throwable $e) {
    if (DEBUG_MODE) {
        echo '<pre style="background:#1e1e1e;color:#f8f8f2;padding:1.5rem;margin:1rem;border-radius:8px;font-size:.85rem;">';
        echo '<strong style="color:#f92672;">' . get_class($e) . '</strong>: ';
        echo htmlspecialchars($e->getMessage()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        http_response_code(500);
        echo '<!DOCTYPE html><html><head><title>Error — Roomee</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
        <body class="d-flex align-items-center justify-content-center" style="min-height:100vh;background:#f8fafc;">
        <div class="text-center p-4">
          <div style="font-size:3rem;">⚠️</div>
          <h4 class="fw-bold mt-3">Something went wrong</h4>
          <p class="text-muted">Please try again or contact support.</p>
          <a href="' . (defined('APP_URL') ? APP_URL : '/') . '/dashboard" class="btn btn-primary">Back to Dashboard</a>
        </div></body></html>';
    }
    error_log('[Roomee] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    exit(1);
});
