<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_route_detect(string $messageText): ?array {
    $text = strtolower(trim($messageText));
    if ($text === '') {
        return null;
    }

    $stmt = db()->query("
        SELECT *
        FROM wa_department_routes
        WHERE is_active = 1
        ORDER BY id ASC
    ");
    $routes = $stmt->fetchAll();

    foreach ($routes as $route) {
        $keywordMatch = strtolower(trim((string)($route['keyword_match'] ?? '')));
        if ($keywordMatch === '') {
            continue;
        }

        $keywords = array_filter(array_map('trim', explode(',', $keywordMatch)));
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && strpos($text, strtolower($keyword)) !== false) {
                return $route;
            }
        }
    }

    return null;
}

function wa_apply_route(int $conversationId, ?string $department, ?int $assignedAdminId): void {
    $stmt = db()->prepare("
        UPDATE wa_conversations
        SET assigned_department = ?, assigned_admin_id = COALESCE(?, assigned_admin_id), updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$department, $assignedAdminId, $conversationId]);
}