<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// ---------------------------------------------------------------------------
// Session Bootstrap
// ---------------------------------------------------------------------------

/**
 * Starts (or resumes) the secure application session.
 * Safe to call multiple times — subsequent calls are no-ops.
 */
function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name(SESSION_NAME);

    ini_set('session.use_strict_mode', '1');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    // Regenerate session ID every 300 seconds to mitigate session fixation.
    if (!isset($_SESSION['_last_regen']) || (time() - (int)$_SESSION['_last_regen']) > 300) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}

// ---------------------------------------------------------------------------
// Authentication Checks
// ---------------------------------------------------------------------------

/**
 * Returns true if the current session has an authenticated user.
 */
function auth_check(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Returns the current user array (cached in session), or null if not logged in.
 * Loads from DB if not already cached.
 */
function auth_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if (!isset($_SESSION['auth_user'])) {
        try {
            $db   = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if (!$user) {
                auth_logout();
                return null;
            }
            $_SESSION['auth_user'] = $user;
        } catch (\Throwable $e) {
            return null;
        }
    }

    return $_SESSION['auth_user'];
}

/**
 * Returns the role string of the current user, or '' if not authenticated.
 */
function auth_role(): string
{
    return (string)($_SESSION['user_role'] ?? '');
}

/**
 * Returns the current user's ID as int, or 0 if not authenticated.
 */
function auth_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Returns true if the current user has the 'super_admin' role.
 */
function auth_is_admin(): bool
{
    return auth_role() === 'super_admin';
}

/**
 * Returns true if the current user has the 'merchant' role.
 */
function auth_is_merchant(): bool
{
    return auth_role() === 'merchant';
}

/**
 * Returns true if the current user has the 'user' role.
 */
function auth_is_user(): bool
{
    return auth_role() === 'user';
}

// ---------------------------------------------------------------------------
// Guards
// ---------------------------------------------------------------------------

/**
 * Redirects to $redirect if the visitor is not authenticated.
 */
function auth_require_login(string $redirect = '/login'): void
{
    if (!auth_check()) {
        $_SESSION['auth_intended'] = $_SERVER['REQUEST_URI'] ?? '/';
        $url = str_starts_with($redirect, 'http') ? $redirect : APP_URL . $redirect;
        header('Location: ' . $url);
        exit;
    }
}

/**
 * Redirects to $redirect if the current user's role is not in $roles.
 * Calls auth_require_login() first to ensure the visitor is authenticated.
 */
function auth_require_role(array $roles, string $redirect = '/login'): void
{
    auth_require_login($redirect);

    if (!in_array(auth_role(), $roles, true)) {
        header('Location: ' . $redirect);
        exit;
    }
}

// ---------------------------------------------------------------------------
// Login / Logout
// ---------------------------------------------------------------------------

/**
 * Establishes an authenticated session for the given user array.
 * Call auth_start_session() before this.
 */
function auth_login(array $user): void
{
    auth_start_session();
    session_regenerate_id(true);

    $_SESSION['user_id']     = (int)$user['id'];
    $_SESSION['user_role']   = (string)($user['role'] ?? 'user');
    $_SESSION['auth_user']   = $user;
    $_SESSION['login_time']  = time();
    $_SESSION['_last_regen'] = time();
}

/**
 * Destroys the current session, clears the session cookie, and redirects to /login.
 */
function auth_logout(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header('Location: ' . APP_URL . '/login');
    exit;
}
