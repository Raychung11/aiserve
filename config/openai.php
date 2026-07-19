<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Load .env manually (outside public_html)
|--------------------------------------------------------------------------
*/
function load_env(): void {
    static $loaded = false;
    if ($loaded) return;

    $envPath = dirname(__DIR__, 2) . '/.env'; 
    // adjust if your structure different

    if (!file_exists($envPath)) return;

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("$key=$value");
    }

    $loaded = true;
}

function openai_api_key(): string {
    // Prefer the admin-managed setting (AI API Settings page); fall back to .env.
    if (function_exists('get_setting')) {
        try {
            $k = get_setting('openai_api_key', '');
            if ($k !== '') {
                return $k;
            }
        } catch (Throwable $e) {
            // DB unavailable — fall through to .env
        }
    }
    load_env();
    return (string)($_ENV['OPENAI_API_KEY'] ?? (getenv('OPENAI_API_KEY') ?: ''));
}

function openai_model(): string {
    if (function_exists('get_setting')) {
        try {
            $m = get_setting('openai_model', '');
            if ($m !== '') {
                return $m;
            }
        } catch (Throwable $e) {
            // DB unavailable — fall through to .env
        }
    }
    load_env();
    return (string)($_ENV['OPENAI_MODEL'] ?? (getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini'));
}