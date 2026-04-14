<?php
/**
 * Authentication class
 * Handles login, register, session management
 */
class Auth {

    /**
     * Start session if not already started
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false, // Set true in production with HTTPS
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    /**
     * Register a new user
     */
    public static function register(string $name, string $email, string $password, string $role = 'sme_owner'): array {
        // Validate
        if (empty($name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
        }
        if (!in_array($role, ['admin', 'consultant', 'sme_owner'])) {
            $role = 'sme_owner';
        }

        // Check existing
        $existing = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered.'];
        }

        $userId = Database::insert('users', [
            'name'     => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'email'    => strtolower(trim($email)),
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role'     => $role,
        ]);

        return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful.'];
    }

    /**
     * Login user
     */
    public static function login(string $email, string $password): array {
        $user = Database::fetchOne('SELECT * FROM users WHERE email = ? AND is_active = 1', [strtolower(trim($email))]);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        self::startSession();
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Set active company for SME owners
        if ($user['role'] === 'sme_owner') {
            $company = Database::fetchOne(
                'SELECT c.id FROM companies c
                 JOIN user_companies uc ON c.id = uc.company_id
                 WHERE uc.user_id = ? LIMIT 1',
                [$user['id']]
            );
            if ($company) {
                $_SESSION['active_company_id'] = $company['id'];
            }
        }

        Database::insert('activity_log', [
            'user_id'     => $user['id'],
            'action'      => 'LOGIN',
            'description' => 'User logged in',
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        return ['success' => true, 'user' => $user, 'message' => 'Login successful.'];
    }

    /**
     * Logout user
     */
    public static function logout(): void {
        self::startSession();
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            Database::insert('activity_log', [
                'user_id'     => $userId,
                'action'      => 'LOGOUT',
                'description' => 'User logged out',
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        }
        session_unset();
        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public static function check(): bool {
        self::startSession();
        return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Require authentication — redirect to login if not authenticated
     */
    public static function requireAuth(): void {
        if (!self::check()) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }

    /**
     * Get current user data
     */
    public static function user(): ?array {
        if (!self::check()) return null;
        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role'  => $_SESSION['user_role'],
        ];
    }

    /**
     * Get active company ID from session
     */
    public static function activeCompanyId(): ?int {
        self::startSession();
        return isset($_SESSION['active_company_id']) ? (int)$_SESSION['active_company_id'] : null;
    }

    /**
     * Set active company in session
     */
    public static function setActiveCompany(int $companyId): void {
        self::startSession();
        $_SESSION['active_company_id'] = $companyId;
    }

    /**
     * Generate CSRF token
     */
    public static function csrfToken(): string {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrf(string $token): bool {
        self::startSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
