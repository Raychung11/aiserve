<?php

function redirect(string $path): never
{
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

function view(string $template, array $data = []): void
{
    extract($data);
    $file = dirname(__DIR__) . '/views/' . $template . '.php';
    if (!file_exists($file)) {
        http_response_code(404);
        include dirname(__DIR__) . '/views/errors/404.php';
        exit;
    }
    include $file;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string
{
    return APP_URL . '/public/assets/' . ltrim($path, '/');
}

function url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

function csrf_token(): string
{
    Auth::start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['_csrf'] ?? '';
    return hash_equals(csrf_token(), $token);
}

function flash(string $key, mixed $value = null): mixed
{
    Auth::start();
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

function old(string $key, string $default = ''): string
{
    Auth::start();
    return e($_SESSION['old'][$key] ?? $default);
}

function set_old(array $data): void
{
    Auth::start();
    $_SESSION['old'] = $data;
}

function ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

function paginate(int $total, int $perPage, int $current): array
{
    $pages = (int) ceil($total / $perPage);
    return [
        'total'    => $total,
        'per_page' => $perPage,
        'current'  => $current,
        'pages'    => $pages,
        'offset'   => ($current - 1) * $perPage,
        'has_prev' => $current > 1,
        'has_next' => $current < $pages,
    ];
}
