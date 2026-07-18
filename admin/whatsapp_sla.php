<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT s.*, wc.push_name, wc.phone
    FROM wa_sla_logs s
    INNER JOIN wa_contacts wc ON wc.id = s.wa_contact_id
    ORDER BY s.updated_at DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp SLA');
?>

<div class="card">
    <h3 style="margin-top:0;">SLA & Response Tracking</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>First Inbound</th>
                    <th>First Outbound</th>
                    <th>First Response (min)</th>
                    <th>Closed At</th>
                    <th>Total Resolution (min)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7">No SLA records yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['push_name']) ?></td>
                        <td><?= h($r['phone']) ?></td>
                        <td><?= h($r['first_inbound_at']) ?></td>
                        <td><?= h($r['first_outbound_at']) ?></td>
                        <td><?= h((string)$r['first_response_minutes']) ?></td>
                        <td><?= h($r['closed_at']) ?></td>
                        <td><?= h((string)$r['total_resolution_minutes']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($rows as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Contact</span><?= h($r['push_name']) ?></div>
                    <div class="row"><span class="label">Phone</span><?= h($r['phone']) ?></div>
                    <div class="row"><span class="label">First Inbound</span><?= h($r['first_inbound_at']) ?></div>
                    <div class="row"><span class="label">First Outbound</span><?= h($r['first_outbound_at']) ?></div>
                    <div class="row"><span class="label">First Response</span><?= h((string)$r['first_response_minutes']) ?> min</div>
                    <div class="row"><span class="label">Closed</span><?= h($r['closed_at']) ?></div>
                    <div class="row"><span class="label">Resolution</span><?= h((string)$r['total_resolution_minutes']) ?> min</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>