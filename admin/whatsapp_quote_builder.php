<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT q.*, wc.push_name, wc.phone
    FROM wa_quote_requests q
    INNER JOIN wa_contacts wc ON wc.id = q.wa_contact_id
    ORDER BY q.updated_at DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp Quote Builder');
?>

<div class="card">
    <h3 style="margin-top:0;">Quote Requests from WhatsApp</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Company</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Timeline</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No quote requests yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['push_name']) ?> • <?= h($r['phone']) ?></td>
                        <td><?= h($r['company_name']) ?></td>
                        <td><?= h($r['product_interest']) ?></td>
                        <td><?= h($r['quantity_needed']) ?></td>
                        <td><?= h($r['timeline_needed']) ?></td>
                        <td><span class="pill"><?= h($r['quote_status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($rows as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Contact</span><?= h($r['push_name']) ?> • <?= h($r['phone']) ?></div>
                    <div class="row"><span class="label">Company</span><?= h($r['company_name']) ?></div>
                    <div class="row"><span class="label">Product</span><?= h($r['product_interest']) ?></div>
                    <div class="row"><span class="label">Quantity</span><?= h($r['quantity_needed']) ?></div>
                    <div class="row"><span class="label">Timeline</span><?= h($r['timeline_needed']) ?></div>
                    <div class="row"><span class="label">Status</span><?= h($r['quote_status']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>