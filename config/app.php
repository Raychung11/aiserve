<?php
/**
 * Application Configuration
 */

define('APP_NAME', 'ESG gen');
define('APP_VERSION', '1.0.0');

// Auto-detect APP_URL — works on Hostinger, localhost, and subdirectory installs.
// No manual editing needed. Override by defining APP_URL before including this file.
if (!defined('APP_URL')) {
    $_esg_scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_esg_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Calculate base path: subtract DOCUMENT_ROOT from this app's root directory
    $_esg_docroot  = realpath($_SERVER['DOCUMENT_ROOT'] ?? '/');
    $_esg_approot  = realpath(__DIR__ . '/..');          // config/ is one level below app root
    $_esg_base     = '';
    if ($_esg_docroot && $_esg_approot && strpos($_esg_approot, $_esg_docroot) === 0) {
        $_esg_base = substr($_esg_approot, strlen($_esg_docroot));
        $_esg_base = str_replace('\\', '/', rtrim($_esg_base, '/'));
    }
    define('APP_URL', $_esg_scheme . '://' . $_esg_host . $_esg_base);
    unset($_esg_scheme, $_esg_host, $_esg_docroot, $_esg_approot, $_esg_base);
}

define('APP_TIMEZONE', 'Asia/Kuala_Lumpur');
define('APP_CURRENCY', 'RM');
define('APP_COUNTRY', 'Malaysia');
define('REPORTING_YEAR', date('Y'));

// Session configuration
define('SESSION_NAME', 'adcellent_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Pagination
define('ITEMS_PER_PAGE', 20);

// ESG Scoring weights (must add up to 100)
define('WEIGHT_ENVIRONMENT', 40);
define('WEIGHT_SOCIAL', 35);
define('WEIGHT_GOVERNANCE', 25);

// Score thresholds
define('SCORE_EXCELLENT', 80);
define('SCORE_GOOD', 60);
define('SCORE_MODERATE', 40);
define('SCORE_POOR', 20);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting (production — errors logged, not displayed)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
