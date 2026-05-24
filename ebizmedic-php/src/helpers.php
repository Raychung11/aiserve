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
    $token = $_POST['_csrf'] ?? $_POST['_token'] ?? '';
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

// Upload a profile photo; returns the relative path or null on failure
function uploadPhoto(string $inputName, string $subDir = 'users'): ?string
{
    if (empty($_FILES[$inputName]['name'])) return null;

    $file    = $_FILES[$inputName];
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    if (!in_array($file['type'], $allowed))  { flash('error', 'Only JPG, PNG, WEBP or GIF images allowed.'); return null; }
    if ($file['size'] > $maxSize)            { flash('error', 'Image must be under 2 MB.');                  return null; }
    if ($file['error'] !== UPLOAD_ERR_OK)    { flash('error', 'Upload failed. Please try again.');           return null; }

    $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name    = Auth::id() . '_' . time() . '.' . strtolower($ext);
    $dir     = dirname(__DIR__) . '/public/assets/uploads/' . $subDir . '/';

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        flash('error', 'Could not save file. Check folder permissions.');
        return null;
    }

    return 'uploads/' . $subDir . '/' . $name;
}

// Return avatar URL — shows initials placeholder if no photo
function avatarUrl(?string $path, string $name, string $size = '10'): string
{
    if ($path) {
        return '<img src="' . asset($path) . '" class="w-' . $size . ' h-' . $size . ' rounded-full object-cover" alt="">';
    }
    $initials = strtoupper(substr($name, 0, 1));
    return '<div class="w-' . $size . ' h-' . $size . ' rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg flex-shrink-0">' . $initials . '</div>';
}

function paginate(int $total, int $perPage, int $current): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    return [
        'total'    => $total,
        'per_page' => $perPage,
        'page'     => $current,
        'current'  => $current,
        'pages'    => $pages,
        'offset'   => ($current - 1) * $perPage,
        'has_prev' => $current > 1,
        'has_next' => $current < $pages,
    ];
}

function notify(int $userId, string $type, string $title, string $message = '', string $link = ''): void
{
    Database::insert(
        'INSERT INTO notifications (user_id, type, title, message, link) VALUES (?,?,?,?,?)',
        [$userId, $type, $title, $message, $link]
    );
}

function stars(float $avg, int $count = 0): string
{
    $full  = (int) floor($avg);
    $half  = ($avg - $full) >= 0.5;
    $empty = 5 - $full - ($half ? 1 : 0);
    $html  = '<span class="flex items-center gap-0.5">';
    for ($i = 0; $i < $full; $i++)  $html .= '<i class="fa-solid fa-star text-yellow-400 text-xs"></i>';
    if ($half)                       $html .= '<i class="fa-solid fa-star-half-stroke text-yellow-400 text-xs"></i>';
    for ($i = 0; $i < $empty; $i++) $html .= '<i class="fa-regular fa-star text-gray-300 text-xs"></i>';
    if ($count > 0)                  $html .= '<span class="text-xs text-gray-500 ml-1">(' . $count . ')</span>';
    $html .= '</span>';
    return $html;
}
