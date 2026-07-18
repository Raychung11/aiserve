<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_template_find(?string $intent, ?string $department = '', string $language = 'English'): ?array {
    $stmt = db()->prepare("
        SELECT *
        FROM wa_template_library
        WHERE is_active = 1
          AND (intent_key = ? OR intent_key IS NULL OR intent_key = '')
          AND (department_key = ? OR department_key IS NULL OR department_key = '')
          AND language_key = ?
        ORDER BY
            CASE WHEN intent_key = ? THEN 0 ELSE 1 END,
            CASE WHEN department_key = ? THEN 0 ELSE 1 END,
            id ASC
        LIMIT 1
    ");
    $stmt->execute([
        $intent ?? '',
        $department ?? '',
        $language,
        $intent ?? '',
        $department ?? ''
    ]);

    $row = $stmt->fetch();
    return $row ?: null;
}

function wa_template_fill(string $templateText, array $data = []): string {
    $replace = [
        '[Name]' => $data['name'] ?? '',
        '[Company]' => $data['company'] ?? '',
        '[Product]' => $data['product'] ?? '',
        '[Timeline]' => $data['timeline'] ?? '',
        '[Branch]' => $data['branch'] ?? '',
    ];

    return strtr($templateText, $replace);
}