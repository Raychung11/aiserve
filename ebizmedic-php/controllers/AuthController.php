<?php

class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) redirect(Auth::dashboardPath());
        view('layouts/auth', ['pageTitle' => 'Login — eBizMedic', 'content' => 'auth/login_form']);
    }

    public function login(): void
    {
        if (!csrf_verify()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('login');
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            flash('error', 'Email and password are required.');
            redirect('login');
        }

        $user = Database::queryOne(
            'SELECT * FROM users WHERE email = ? AND is_active = 1',
            [$email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Invalid email or password.');
            set_old(['email' => $email]);
            redirect('login');
        }

        // Check approval for medic / organisation accounts
        if (in_array($user['role'], ['medic', 'organisation']) && !($user['approved'] ?? 1)) {
            flash('error', 'Your account is pending admin approval. You will be notified once approved.');
            redirect('login');
        }

        Auth::login($user);
        redirect(Auth::dashboardPath());
    }

    public function registerForm(): void
    {
        if (Auth::check()) redirect(Auth::dashboardPath());
        view('layouts/auth', ['pageTitle' => 'Register — eBizMedic', 'content' => 'auth/register_form']);
    }

    public function register(): void
    {
        if (!csrf_verify()) {
            flash('error', 'Invalid request.');
            redirect('register');
        }

        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $role    = $_POST['role'] ?? 'user';

        $errors = [];
        if (strlen($name) < 2)                                  $errors[] = 'Name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Invalid email address.';
        if (strlen($password) < 8)                              $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)                             $errors[] = 'Passwords do not match.';
        if (!in_array($role, ['user', 'medic', 'organisation'])) $errors[] = 'Invalid role.';

        if ($errors) {
            flash('error', implode(' ', $errors));
            set_old(compact('name', 'email', 'role'));
            redirect('register');
        }

        if (Database::queryOne('SELECT id FROM users WHERE email = ?', [$email])) {
            flash('error', 'Email is already registered.');
            set_old(compact('name', 'email', 'role'));
            redirect('register');
        }

        // Medic and organisation accounts require admin approval
        $approved = in_array($role, ['medic', 'organisation']) ? 0 : 1;

        $hash   = password_hash($password, PASSWORD_BCRYPT);
        $userId = Database::insert(
            'INSERT INTO users (name, email, password, role, approved) VALUES (?, ?, ?, ?, ?)',
            [$name, $email, $hash, $role, $approved]
        );

        if ($role === 'organisation') {
            Database::insert('INSERT INTO organisations (user_id, name, email) VALUES (?, ?, ?)', [$userId, $name, $email]);
        }
        if ($role === 'medic') {
            Database::insert('INSERT INTO doctors (user_id) VALUES (?)', [$userId]);
        }

        if (!$approved) {
            flash('success', 'Registration successful! Your account is pending admin approval. We will notify you once approved.');
            redirect('login');
        }

        $user = Database::queryOne('SELECT * FROM users WHERE id = ?', [$userId]);
        Auth::login($user);
        redirect(Auth::dashboardPath());
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('login');
    }
}
