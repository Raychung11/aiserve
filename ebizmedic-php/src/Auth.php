<?php

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('ebizmedic_session');
            session_start();
        }
    }

    public static function login(array $user): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
    }

    public static function logout(): void
    {
        self::start();
        session_unset();
        session_destroy();
    }

    public static function check(): bool
    {
        self::start();
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return [
            'id'   => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
        ];
    }

    public static function role(): ?string
    {
        self::start();
        return $_SESSION['user_role'] ?? null;
    }

    public static function id(): ?int
    {
        self::start();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    // Redirect if not logged in
    public static function require(): void
    {
        if (!self::check()) {
            redirect('login');
        }
    }

    // Redirect if not a specific role
    public static function requireRole(string|array $roles): void
    {
        self::require();
        $roles = (array) $roles;
        if (!in_array(self::role(), $roles)) {
            redirect(self::dashboardPath());
        }
    }

    public static function dashboardPath(): string
    {
        return match (self::role()) {
            'admin'        => 'admin/dashboard',
            'medic'        => 'medic/dashboard',
            'organisation' => 'organisation/dashboard',
            'pharmacist'   => 'pharmacist/dashboard',
            default        => 'user/dashboard',
        };
    }
}
