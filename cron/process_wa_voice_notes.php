<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$stmt = db()->prepare("
    SELECT *
    FROM wa_voice_notes
    WHERE processing_status = 'pending'
    ORDER BY id ASC
    LIMIT 10
");
$stmt->execute();
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    // Future enhancement:
    // 1. download audio
    // 2. transcribe with OpenAI or Whisper
    // 3. save text into transcription_text
    // 4. optionally push text into wa_messages as system summary

    $upd = db()->prepare("
        UPDATE wa_voice_notes
        SET processing_status = 'failed',
            error_message = 'Transcription pipeline not connected yet',
            processed_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([$row['id']]);
}

echo "Processed " . count($rows) . " voice note item(s).\n";