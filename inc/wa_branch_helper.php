<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_branch_set(int $conversationId, int $waContactId, string $branchName): void {
    $stmt = db()->prepare("
        UPDATE wa_conversations
        SET branch_name = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$branchName, $conversationId]);

    $stmt = db()->prepare("
        UPDATE wa_contacts
        SET branch_name = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$branchName, $waContactId]);
}