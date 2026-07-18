<?php
declare(strict_types=1);

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

function csrf_token(): string {
    return $_SESSION['admin_csrf'] ?? '';
}

function csrf_input(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void {
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'] ?? '', (string)$posted)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
}