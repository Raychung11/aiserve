<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'AiServe.my');
define('COMPANY_NAME', 'SLV Group Sdn Bhd');
define('APP_URL', 'https://aiserve.my/');

/*
|--------------------------------------------------------------------------
| Load environment variables from .env (kept outside the web root / repo)
|--------------------------------------------------------------------------
| Credentials must NOT be committed to source control. Copy .env.example to
| .env (two directories above the project root, matching config/openai.php)
| and fill in the real values there.
*/
if (!function_exists('aiserve_load_env')) {
    function aiserve_load_env(): void {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $envPath = dirname(__DIR__, 2) . '/.env';
        if (!is_file($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if ($key === '') {
                continue;
            }
            $value = trim($value, "\"'");
            if (getenv($key) === false) {
                putenv("$key=$value");
            }
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('aiserve_env')) {
    function aiserve_env(string $key, string $default = ''): string {
        aiserve_load_env();
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string)$_ENV[$key];
        }
        return $default;
    }
}

define('DB_HOST', aiserve_env('DB_HOST', '127.0.0.1'));
define('DB_PORT', aiserve_env('DB_PORT', '3306'));
define('DB_NAME', aiserve_env('DB_NAME'));
define('DB_USER', aiserve_env('DB_USER'));
define('DB_PASS', aiserve_env('DB_PASS'));

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
