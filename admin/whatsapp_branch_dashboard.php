<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT
        COALESCE(branch_name, 'Unassigned') AS branch_name,
        COUNT(*) AS total_conversations
    FROM wa_conversations
    GROUP BY COALESCE(branch_name, 'Unassigned')
    ORDER BY total_conversations DESC
")->fetchAll();

admin_header('WhatsApp Branch Dashboard');
?>

<div class="card">
    <h3 style="margin-top:0;">Conversations by Branch</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Total Conversations</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['branch_name']) ?></td>
                    <td><?= (int)$r['total_conversations'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>