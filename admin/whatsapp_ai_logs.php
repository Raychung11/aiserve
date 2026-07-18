<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT l.*, c.remote_jid
    FROM wa_ai_logs l
    LEFT JOIN wa_conversations c ON c.id = l.conversation_id
    ORDER BY l.id DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp AI Logs');
?>

<div class="card">
    <h3 style="margin-top:0;">AI Processing Logs</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Conversation</th>
                    <th>Stage</th>
                    <th>Model</th>
                    <th>Created</th>
                    <th>Prompt</th>
                    <th>Response</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7">No AI logs yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><?= h($r['remote_jid']) ?></td>
                        <td><?= h($r['ai_stage']) ?></td>
                        <td><?= h($r['model_name']) ?></td>
                        <td><?= h($r['created_at']) ?></td>
                        <td><?= h(mb_strimwidth((string)$r['prompt_text'], 0, 120, '...')) ?></td>
                        <td><?= h(mb_strimwidth((string)$r['response_text'], 0, 120, '...')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($rows as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Conversation</span><?= h($r['remote_jid']) ?></div>
                    <div class="row"><span class="label">Stage</span><?= h($r['ai_stage']) ?></div>
                    <div class="row"><span class="label">Model</span><?= h($r['model_name']) ?></div>
                    <div class="row"><span class="label">Created</span><?= h($r['created_at']) ?></div>
                    <div class="row"><span class="label">Prompt</span><?= h(mb_strimwidth((string)$r['prompt_text'], 0, 120, '...')) ?></div>
                    <div class="row"><span class="label">Response</span><?= h(mb_strimwidth((string)$r['response_text'], 0, 120, '...')) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>