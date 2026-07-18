<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/*
|--------------------------------------------------------------------------
| Projects helper
|--------------------------------------------------------------------------
| Manages the `projects` table used by the admin manager and the public
| "Our Projects" page. The table is created on first use so no manual SQL
| / phpMyAdmin step is required.
*/

function projects_ensure_schema(): void {
    static $done = false;
    if ($done) {
        return;
    }
    db()->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL DEFAULT '',
            client VARCHAR(255) NOT NULL DEFAULT '',
            category VARCHAR(120) NOT NULL DEFAULT '',
            description TEXT NULL,
            project_status VARCHAR(40) NOT NULL DEFAULT 'in_progress',
            cover_image VARCHAR(500) NOT NULL DEFAULT '',
            project_url VARCHAR(500) NOT NULL DEFAULT '',
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_published (is_published),
            KEY idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $done = true;
}

/** Allowed workflow statuses. */
function project_status_options(): array {
    return ['planning', 'in_progress', 'completed', 'on_hold'];
}

/** Human-readable label for a workflow status. */
function project_status_label(string $status): string {
    $map = [
        'planning'    => 'Planning',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'on_hold'     => 'On Hold',
    ];
    return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
}
