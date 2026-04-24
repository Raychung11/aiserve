<?php
class Auth {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool {
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array {
        if (!self::check()) return null;
        if (!isset($_SESSION['_user_cache'])) {
            $_SESSION['_user_cache'] = Database::fetchOne(
                'SELECT u.*, t.name AS tenant_name, t.status AS tenant_status,
                        t.plan, t.max_properties, t.trial_ends_at, t.subscription_ends_at
                 FROM users u
                 LEFT JOIN tenants t ON t.id = u.tenant_id
                 WHERE u.id = ? AND u.is_active = 1',
                [$_SESSION['user_id']]
            );
        }
        return $_SESSION['_user_cache'] ?: null;
    }

    public static function tenantId(): int {
        return (int) (self::user()['tenant_id'] ?? 0);
    }

    public static function require(): void {
        self::start();
        if (!self::check()) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }
        $user = self::user();
        if (!$user) {
            self::logout();
            header('Location: ' . APP_URL . '/login');
            exit;
        }
        // Check tenant is active
        if (!in_array($user['tenant_status'], ['active', 'trial'])) {
            header('Location: ' . APP_URL . '/subscription');
            exit;
        }
    }

    public static function login(string $email, string $password): array {
        $user = Database::fetchOne(
            'SELECT u.*, t.status AS tenant_status FROM users u
             LEFT JOIN tenants t ON t.id = u.tenant_id
             WHERE u.email = ? AND u.is_active = 1',
            [strtolower(trim($email))]
        );
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        unset($_SESSION['_user_cache']);
        ActivityLog::record('auth.login', 'User logged in', $user['tenant_id'], $user['id']);
        $redirect = ($user['role'] ?? 'admin') === 'owner' ? '/owner-portal' : '/dashboard';
        return ['success' => true, 'redirect' => $redirect];
    }

    public static function register(array $data): array {
        $email = strtolower(trim($data['email']));
        if (Database::count('users', 'email = ?', [$email])) {
            return ['success' => false, 'error' => 'Email already registered.'];
        }
        $plan = $data['plan'] ?? 'starter';
        $limits = PLAN_LIMITS[$plan] ?? PLAN_LIMITS['starter'];
        $tenantId = Database::insert('tenants', [
            'name'             => trim($data['company_name']),
            'email'            => $email,
            'phone'            => trim($data['phone'] ?? ''),
            'plan'             => $plan,
            'status'           => 'trial',
            'max_properties'   => $limits['properties'],
            'trial_ends_at'    => date('Y-m-d H:i:s', strtotime('+' . TRIAL_DAYS . ' days')),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        $userId = Database::insert('users', [
            'tenant_id'     => $tenantId,
            'name'          => trim($data['name']),
            'email'         => $email,
            'phone'         => trim($data['phone'] ?? ''),
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'          => 'admin',
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        unset($_SESSION['_user_cache']);
        ActivityLog::record('auth.register', 'New tenant registered: ' . $data['company_name'], $tenantId, $userId);
        return ['success' => true];
    }

    public static function logout(): void {
        self::start();
        $user = self::user();
        if ($user) {
            ActivityLog::record('auth.logout', 'User logged out', $user['tenant_id'], $user['id']);
        }
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    public static function isAdmin(): bool {
        return in_array(self::user()['role'] ?? '', ['admin', 'super_admin']);
    }

    public static function isOwner(): bool {
        return (self::user()['role'] ?? '') === 'owner';
    }

    public static function csrfToken(): string {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(): void {
        $token = $_POST['_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Invalid CSRF token.');
        }
    }
}
