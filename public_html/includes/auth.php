<?php
/**
 * MM2H 管家 Platform — Authentication & Authorization
 */

function start_secure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure',  isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_name(SESSION_NAME);
        session_start();
    }
}

function auth_check(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function require_auth(): void {
    if (!auth_check()) {
        flash('warning', 'Please sign in to continue.');
        redirect('login');
    }
}

function require_role(string|array $roles): void {
    require_auth();
    $allowed = (array)$roles;
    if (!in_array($_SESSION['user_role'], $allowed, true)) {
        http_response_code(403);
        require __DIR__ . '/../includes/403.php';
        exit;
    }
}

function require_admin(): void {
    require_role(['super_admin', 'admin']);
}

function is_admin(): bool {
    return in_array($_SESSION['user_role'] ?? '', ['super_admin', 'admin'], true);
}

function is_super_admin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'super_admin';
}

function is_member(): bool {
    return ($_SESSION['user_role'] ?? '') === 'member';
}

function is_partner(): bool {
    return in_array($_SESSION['user_role'] ?? '', ['agent', 'property_partner', 'bank_partner', 'biz_partner', 'affiliate'], true);
}

function current_user(): ?array {
    if (!auth_check()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function login_user(string $email, string $password): array {
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Regenerate session ID on login to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_email']= $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];

    // Update last login
    db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
    log_activity('login');

    return ['success' => true, 'role' => $user['role']];
}

function logout_user(): void {
    log_activity('logout');
    $_SESSION = [];
    session_destroy();
}

function register_user(array $data): array {
    $email     = strtolower(trim($data['email'] ?? ''));
    $password  = $data['password'] ?? '';
    $full_name = trim($data['full_name'] ?? '');
    $phone     = trim($data['phone'] ?? '');
    $ref_code  = strtoupper(trim($data['referral_code'] ?? ''));

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    if (empty($full_name)) {
        return ['success' => false, 'error' => 'Full name is required.'];
    }

    // Check duplicate email
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'This email is already registered.'];
    }

    // Resolve referrer
    $referred_by = null;
    if ($ref_code) {
        $stmt = db()->prepare('SELECT id FROM users WHERE referral_code = ?');
        $stmt->execute([$ref_code]);
        $referrer = $stmt->fetch();
        if ($referrer) {
            $referred_by = $referrer['id'];
        }
    }

    $hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $my_code  = generate_referral_code($full_name);

    // Insert user
    $stmt = db()->prepare(
        'INSERT INTO users (email, password, full_name, phone, role, referral_code, referred_by)
         VALUES (?, ?, ?, ?, "member", ?, ?)'
    );
    $stmt->execute([$email, $hash, $full_name, $phone, $my_code, $referred_by]);
    $user_id = (int)db()->lastInsertId();

    // Create empty member profile
    db()->prepare(
        'INSERT INTO member_profiles (user_id) VALUES (?)'
    )->execute([$user_id]);

    // Track referral
    if ($referred_by) {
        db()->prepare(
            'INSERT INTO referrals (referrer_id, referred_user_id, commission_rate)
             VALUES (?, ?, ?)'
        )->execute([$referred_by, $user_id, (float)get_setting('default_commission', '20')]);
    }

    log_activity('register');
    return ['success' => true, 'user_id' => $user_id];
}
