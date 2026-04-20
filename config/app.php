<?php
declare(strict_types=1);

// Application constants
define('APP_NAME', 'Kasih Gold Easy');
define('APP_TAGLINE', 'Emas Mudah, Kaya Mudah.');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost', '/'));
define('APP_BASE_PATH', dirname(__DIR__));

// Gold calculation constants
define('GOLD_POINT_DIVISOR', 100);     // 1 point = 0.01 gram
define('GOLD_POINT_DECIMALS', 4);
define('GOLD_GRAM_DECIMALS', 6);
define('GOLD_RM_DECIMALS', 2);

// Upload paths
define('UPLOAD_PATH', APP_BASE_PATH . '/uploads');
define('UPLOAD_URL', APP_URL . '/uploads');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB

// Session config
define('SESSION_NAME', 'kasih_gold_session');
define('SESSION_LIFETIME', 7200); // 2 hours

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

date_default_timezone_set('Asia/Kuala_Lumpur');
