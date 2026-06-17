<?php
/**
 * MM2H 管家 Platform — Database Configuration
 * Copy this file to db_config.local.php for environment overrides.
 */

// ── Database credentials ────────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'mm2h_platform');
define('DB_USER', getenv('DB_USER') ?: 'mm2h_user');
define('DB_PASS', getenv('DB_PASS') ?: 'change_this_password');
define('DB_CHARSET', 'utf8mb4');

// ── Application settings ────────────────────────────────────────────────────
define('APP_NAME', 'MM2H 管家');
define('APP_VERSION', '1.0.0');
define('APP_URL', rtrim(getenv('APP_URL') ?: 'https://yourdomain.com', '/'));
define('APP_ENV', getenv('APP_ENV') ?: 'production'); // production | development

// ── Upload settings ─────────────────────────────────────────────────────────
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10 MB
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// ── Session ─────────────────────────────────────────────────────────────────
define('SESSION_NAME', 'mm2h_session');
define('SESSION_LIFETIME', 7200); // 2 hours

// ── Security ────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', '_csrf_token');

// ── PDO singleton ────────────────────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
            }
            die('A database error occurred. Please try again later.');
        }
    }
    return $pdo;
}
