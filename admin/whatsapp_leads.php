<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("
    SELECT
        p.*,
        c.remote_jid,
        wc.push_name,
        wc.phone,
        wc.crm_contact_id
    FROM wa_lead_profiles p
    INNER JOIN wa_conversations c ON c.id = p.conversation_id
    INNER JOIN wa_contacts wc ON wc.id = p.wa_contact_id
    ORDER BY p.updated_at DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp Leads');
?>

<div class="card">
    <h3 style="margin-top:0;">Extracted WhatsApp Leads</h3>

    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Business Type</th>
                    <th>Interest</th>
                    <th>Phone</th>
                    <th>Score</th>
                    <th>CRM</th>
                    <th>Open</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8">No extracted leads yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['extracted_name']) ?></td>
                        <td><?= h($r['extracted_company']) ?></td>
                        <td><?= h($r['extracted_business_type']) ?></td>
                        <td><?= h($r['extracted_interest']) ?></td>
                        <td><?= h($r['phone']) ?></td>
                        <td><?= h((string)$r['confidence_score']) ?></td>
                        <td><?= !empty($r['crm_contact_id']) ? 'Yes' : 'No' ?></td>
                        <td><a href="/admin/whatsapp_lead_view.php?id=<?= (int)$r['id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php foreach ($rows as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Name</span><?= h($r['extracted_name']) ?></div>
                    <div class="row"><span class="label">Company</span><?= h($r['extracted_company']) ?></div>
                    <div class="row"><span class="label">Business Type</span><?= h($r['extracted_business_type']) ?></div>
                    <div class="row"><span class="label">Interest</span><?= h($r['extracted_interest']) ?></div>
                    <div class="row"><span class="label">Phone</span><?= h($r['phone']) ?></div>
                    <div class="row"><span class="label">Score</span><?= h((string)$r['confidence_score']) ?></div>
                    <div class="row"><span class="label">CRM</span><?= !empty($r['crm_contact_id']) ? 'Yes' : 'No' ?></div>
                    <div class="row"><a href="/admin/whatsapp_lead_view.php?id=<?= (int)$r['id'] ?>" class="btn-secondary">Open</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>