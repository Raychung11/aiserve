<?php

// Load .env file if it exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

define('APP_NAME',  $_ENV['APP_NAME']  ?? 'eBizMedic');
define('APP_URL',   $_ENV['APP_URL']   ?? 'http://localhost/ebizmedic-php');
define('APP_ENV',   $_ENV['APP_ENV']   ?? 'production');
define('APP_DEBUG', ($_ENV['APP_DEBUG'] ?? 'false') === 'true');

define('SESSION_SECRET', $_ENV['SESSION_SECRET'] ?? 'changeme_secret_key_32chars_min');

define('ROLES', ['admin', 'medic', 'organisation', 'user']);

if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
