<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_pick_assigned_admin(?string $interest = '', ?string $businessType = ''): ?int {
    $interest = strtolower(trim((string)$interest));
    $businessType = strtolower(trim((string)$businessType));

    $stmt = db()->query("
        SELECT id, full_name, role
        FROM admin_users
        WHERE is_active = 1
        ORDER BY id ASC
    ");
    $admins = $stmt->fetchAll();

    if (!$admins) {
        return null;
    }

    foreach ($admins as $admin) {
        $name = strtolower((string)$admin['full_name']);

        if (strpos($interest, 'ai') !== false && strpos($name, 'ray') !== false) {
            return (int)$admin['id'];
        }

        if (strpos($businessType, 'furniture') !== false && strpos($name, 'ray') !== false) {
            return (int)$admin['id'];
        }
    }

    return (int)$admins[0]['id'];
}