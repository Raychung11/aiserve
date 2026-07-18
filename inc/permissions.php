<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function admin_role(): string {
    return (string)($_SESSION['admin_role'] ?? '');
}

function has_role(array $roles): bool {
    return in_array(admin_role(), $roles, true);
}

function require_role(array $roles): void {
    if (!has_role($roles)) {
        http_response_code(403);
        exit('Access denied.');
    }
}