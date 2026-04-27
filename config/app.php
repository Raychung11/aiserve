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

// Production — disable error output
ini_set('display_errors', 0);
error_reporting(0);
