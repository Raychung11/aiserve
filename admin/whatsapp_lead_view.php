<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect('/admin/whatsapp_leads.php');

$stmt = db()->prepare("
    SELECT
        p.*,
        c.remote_jid,
        c.id AS conversation_id,
        wc.push_name,
        wc.phone,
        wc.crm_contact_id,
        wc.company_name,
        wc.business_type,
        wc.use_case,
        wc.lead_score
    FROM wa_lead_profiles p
    INNER JOIN wa_conversations c ON c.id = p.conversation_id
    INNER JOIN wa_contacts wc ON wc.id = p.wa_contact_id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) redirect('/admin/whatsapp_leads.php');

admin_header('WhatsApp Lead Detail');
?>

<div class="card">
    <h3 style="margin-top:0;">Lead Detail</h3>

    <div class="form-grid">
        <div class="field">
            <label>Name</label>
            <input type="text" value="<?= h($row['extracted_name']) ?>" readonly>
        </div>
        <div class="field">
            <label>Company</label>
            <input type="text" value="<?= h($row['extracted_company']) ?>" readonly>
        </div>
        <div class="field">
            <label>Business Type</label>
            <input type="text" value="<?= h($row['extracted_business_type']) ?>" readonly>
        </div>
        <div class="field">
            <label>Interest</label>
            <input type="text" value="<?= h($row['extracted_interest']) ?>" readonly>
        </div>
        <div class="field">
            <label>Phone</label>
            <input type="text" value="<?= h($row['phone']) ?>" readonly>
        </div>
        <div class="field">
            <label>Confidence Score</label>
            <input type="text" value="<?= h((string)$row['confidence_score']) ?>" readonly>
        </div>
        <div class="field full">
            <label>Use Case</label>
            <textarea readonly><?= h($row['extracted_use_case']) ?></textarea>
        </div>
        <div class="field full">
            <label>Extraction JSON</label>
            <textarea readonly><?= h($row['extraction_json']) ?></textarea>
        </div>
    </div>

    <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
        <a href="/admin/whatsapp_conversation.php?id=<?= (int)$row['conversation_id'] ?>" class="btn">Open Conversation</a>
        <?php if (!empty($row['crm_contact_id'])): ?>
            <a href="/admin/contact_view.php?id=<?= (int)$row['crm_contact_id'] ?>" class="btn-secondary">Open CRM Contact</a>
        <?php endif; ?>
    </div>
</div>

<?php admin_footer(); ?>