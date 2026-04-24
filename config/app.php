<?php
define('APP_NAME',     'STRHub AI');
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

// Auto-detect app URL (works on Hostinger)
if (!defined('APP_URL')) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base     = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    define('APP_URL', $scheme . '://' . $host . $base);
}

// Error display — set 0 in production
ini_set('display_errors', 1);
error_reporting(E_ALL);
