<?php
declare(strict_types=1);
// CoLive OS — database singleton
// Usage: $db = getDB();  (call at top of every public page)

define('DB_HOST',    '127.0.0.1');
define('DB_NAME',    'u822252863_roomee');
define('DB_USER',    'u822252863_roomee');
define('DB_PASS',    'YOUR_DB_PASSWORD_HERE');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+08:00'");
    }
    return $pdo;
}
