<?php
declare(strict_types=1);
// CoLive OS — shared helpers
// Require db.php before this file.

// ── App constants ────────────────────────────────────────────────────────────
define('APP_NAME',    'CoLive OS');
define('APP_URL',     'https://roomee.my/colive');  // must include subfolder — no trailing slash
define('APP_VERSION', '1.0.0');
define('BRAND_COLOR', '#9333ea');
define('TRIAL_DAYS',  14);
define('TZ',          'Asia/Kuala_Lumpur');

date_default_timezone_set(TZ);

// ── Debug mode ───────────────────────────────────────────────────────────────
// Flip to true on local/staging only.
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
ini_set('log_errors',  '1');
ini_set('error_log',   __DIR__ . '/logs/php_errors.log');

// ── Session bootstrap ────────────────────────────────────────────────────────
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !DEBUG_MODE,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('colive_sess');
        session_start();
    }
}

// ── Multi-tenant isolation ───────────────────────────────────────────────────
function currentCompanyId(): int {
    return (int)($_SESSION['company_id'] ?? 0);
}

// Appends " AND company_id=?" — always pair with binding currentCompanyId().
function companyWhere(): string {
    return ' AND company_id=?';
}

// Shorthand for binding
function companyId(): int {
    return currentCompanyId();
}

// ── Auth helpers ─────────────────────────────────────────────────────────────
function requireOperatorLogin(): void {
    startSecureSession();
    if (empty($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
        header('Location: ' . APP_URL . '/app/login.php');
        exit;
    }
}

function requirePlatformLogin(): void {
    startSecureSession();
    if (empty($_SESSION['platform_admin_id'])) {
        header('Location: ' . APP_URL . '/platform/login.php');
        exit;
    }
}

function requireResidentLogin(): void {
    startSecureSession();
    if (empty($_SESSION['resident_id'])) {
        header('Location: ' . APP_URL . '/portal/resident/login.php');
        exit;
    }
}

function requireOwnerLogin(): void {
    startSecureSession();
    if (empty($_SESSION['owner_id'])) {
        header('Location: ' . APP_URL . '/portal/owner/login.php');
        exit;
    }
}

function currentRole(): string {
    return $_SESSION['role'] ?? '';
}

function requireRole(string ...$roles): void {
    if (!in_array(currentRole(), $roles, true)) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;padding:2rem;">Access denied.</p>');
    }
}

// ── CSRF ─────────────────────────────────────────────────────────────────────
function csrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid security token. Please go back and try again.');
    }
}

function csrfField(): string {
    return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

// ── Flash messages ───────────────────────────────────────────────────────────
function flashSet(string $type, string $msg): void {
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flashGet(): array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return [];
}

function flashHtml(): string {
    $f = flashGet();
    if (!$f) return '';
    $cls = $f['type'] === 'success' ? 'alert-success' : ($f['type'] === 'warning' ? 'alert-warning' : 'alert-danger');
    return '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">'
        . htmlspecialchars($f['msg'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// ── Audit log ────────────────────────────────────────────────────────────────
function auditLog(
    PDO    $db,
    string $action,
    string $targetTable = '',
    int    $targetId    = 0,
    array  $oldVal      = [],
    array  $newVal      = []
): void {
    try {
        $stmt = $db->prepare(
            'INSERT INTO audit_logs
             (company_id, user_id, action, target_table, target_id, old_val, new_val, ip, created_at)
             VALUES (?,?,?,?,?,?,?,?,NOW())'
        );
        $stmt->execute([
            currentCompanyId(),
            $_SESSION['user_id'] ?? 0,
            $action,
            $targetTable,
            $targetId,
            $oldVal ? json_encode($oldVal) : null,
            $newVal ? json_encode($newVal) : null,
            $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (Throwable $e) {
        error_log('[CoLive audit] ' . $e->getMessage());
    }
}

// ── Brand color ───────────────────────────────────────────────────────────────
// Returns the operator's brand color or the platform default.
function brandColor(array $company = []): string {
    return !empty($company['brand_color']) ? $company['brand_color'] : BRAND_COLOR;
}

// ── Formatting helpers ───────────────────────────────────────────────────────
function money(float $amount, string $currency = 'RM'): string {
    return $currency . ' ' . number_format($amount, 2);
}

function dateDisplay(string $date): string {
    return $date ? date('d M Y', strtotime($date)) : '&mdash;';
}

function datetimeDisplay(string $dt): string {
    return $dt ? date('d M Y, H:i', strtotime($dt)) : '&mdash;';
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ── Pagination ───────────────────────────────────────────────────────────────
function paginate(PDO $db, string $countSql, array $params, int $perPage = 25): array {
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $stmt  = $db->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    return [
        'page'       => $page,
        'per_page'   => $perPage,
        'total'      => $total,
        'total_pages'=> (int)ceil($total / $perPage),
        'offset'     => ($page - 1) * $perPage,
    ];
}

// ── Debug shutdown handler ────────────────────────────────────────────────────
// Placed at top of every new page during development; removed after confirmed live.
function registerDebugShutdown(): void {
    if (!DEBUG_MODE) return;
    register_shutdown_function(function () {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            echo '<div style="background:#1e1e1e;color:#f8f8f2;padding:1rem;font-family:monospace;font-size:.85rem;position:fixed;bottom:0;left:0;right:0;z-index:9999;">';
            echo '<strong style="color:#f92672;">Fatal:</strong> ' . e($err['message']);
            echo ' &mdash; ' . e($err['file']) . ':' . $err['line'];
            echo '</div>';
        }
    });
}
