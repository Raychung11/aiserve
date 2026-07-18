<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function wa_voice_note_queue_if_needed(array $message): void {
    if (($message['message_type'] ?? '') !== 'audioMessage') {
        return;
    }

    $stmt = db()->prepare("
        INSERT IGNORE INTO wa_voice_notes
        (wa_message_id, conversation_id, wa_contact_id, audio_url, mime_type, processing_status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $message['id'],
        $message['conversation_id'],
        $message['wa_contact_id'],
        $message['media_url'] ?? '',
        $message['media_mime'] ?? ''
    ]);
}