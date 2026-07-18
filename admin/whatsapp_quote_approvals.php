<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $note = trim($_POST['approval_note'] ?? '');

    if ($id > 0 && in_array($action, ['approved', 'rejected'], true)) {
        $stmt = db()->prepare("
            UPDATE wa_quote_approvals
            SET approval_status = ?, approval_note = ?, approved_by = ?, decided_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$action, $note, $_SESSION['admin_id'] ?? null, $id]);
    }

    redirect('/admin/whatsapp_quote_approvals.php');
}

$rows = db()->query("
    SELECT a.*, wc.push_name, wc.phone
    FROM wa_quote_approvals a
    INNER JOIN wa_contacts wc ON wc.id = a.wa_contact_id
    ORDER BY a.created_at DESC
    LIMIT 200
")->fetchAll();

admin_header('WhatsApp Quote Approvals');
?>

<div class="card">
    <h3 style="margin-top:0;">Quote Approval Queue</h3>

    <?php foreach ($rows as $r): ?>
        <div style="padding:14px 0;border-bottom:1px solid #e8defd;">
            <div><strong><?= h($r['push_name']) ?></strong> • <?= h($r['phone']) ?></div>
            <div class="muted">Status: <?= h($r['approval_status']) ?> • Created: <?= h($r['created_at']) ?></div>

            <?php if ($r['approval_status'] === 'pending'): ?>
                <form method="post" style="margin-top:10px;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <div class="form-grid">
                        <div class="field full">
                            <label>Approval Note</label>
                            <textarea name="approval_note"></textarea>
                        </div>
                        <div class="field" style="display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="submit" name="action" value="approved" class="btn">Approve</button>
                            <button type="submit" name="action" value="rejected" class="btn-secondary">Reject</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="muted" style="margin-top:8px;">Decision Note: <?= h($r['approval_note']) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php admin_footer(); ?>