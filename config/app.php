<?php
/**
 * Application Configuration
 */

define('APP_NAME', 'AiServe ESG OS');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost'); // Update to your domain e.g. https://yourdomain.com
define('APP_TIMEZONE', 'Asia/Kuala_Lumpur');
define('APP_CURRENCY', 'RM');
define('APP_COUNTRY', 'Malaysia');
define('REPORTING_YEAR', date('Y'));

// Session configuration
define('SESSION_NAME', 'aiserve_session');
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

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
