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
    load_env();
    return $_ENV['OPENAI_API_KEY'] ?? '';
}

function openai_model(): string {
    load_env();
    return $_ENV['OPENAI_MODEL'] ?? 'gpt-4.1-mini';
}