<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT v.*, wc.push_name, wc.phone
    FROM wa_voice_notes v
    INNER JOIN wa_contacts wc ON wc.id = v.wa_contact_id
    ORDER BY v.created_at DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp Voice Notes');
?>

<div class="card">
    <h3 style="margin-top:0;">Voice Note Processing</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Audio</th>
                    <th>Transcription</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="5">No voice notes found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['push_name']) ?> • <?= h($r['phone']) ?></td>
                        <td><span class="pill"><?= h($r['processing_status']) ?></span></td>
                        <td><?php if (!empty($r['audio_url'])): ?><a href="<?= h($r['audio_url']) ?>" target="_blank">Open</a><?php else: ?>—<?php endif; ?></td>
                        <td><?= h(mb_strimwidth((string)$r['transcription_text'], 0, 120, '...')) ?></td>
                        <td><?= h($r['error_message']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>