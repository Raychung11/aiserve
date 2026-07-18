<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect('/admin/campaigns.php');
}

$stmt = db()->prepare("SELECT * FROM email_campaigns WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    redirect('/admin/campaigns.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');

    if (!in_array($status, campaign_status_options(), true)) {
        $status = 'draft';
    }

    if ($subject !== '' && $body !== '') {
        $stmt = db()->prepare("
            UPDATE email_campaigns
            SET subject = ?, body = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$subject, $body, $status, $id]);

        redirect('/admin/campaign_view.php?id=' . $id);
    }
}

admin_header('Campaign Detail');
?>

<div class="card">
    <h3 style="margin-top:0;">Campaign #<?= (int)$row['id'] ?></h3>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field full">
                <label>Subject</label>
                <input type="text" name="subject" value="<?= h($row['subject']) ?>" required>
            </div>

            <div class="field full">
                <label>Body</label>
                <textarea name="body" required><?= h($row['body']) ?></textarea>
            </div>
            <div class="field">
                <label>Assigned Admin ID</label>
                <input type="text" value="<?= h((string)$row['assigned_admin_id']) ?>" readonly>
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <?php foreach (campaign_status_options() as $opt): ?>
                        <option value="<?= h($opt) ?>" <?= $row['status'] === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field" style="align-self:end;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn">Update Campaign</button>
                <?php if ($row['status'] !== 'sent'): ?>
                    <a href="/admin/campaign_send.php?id=<?= (int)$row['id'] ?>" class="btn-secondary">Send Campaign</a>
                    <a href="/admin/contact_assign.php?id=<?= (int)$row['id'] ?>" class="btn-secondary">Assign Lead</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Preview</h3>
    <p><strong>Subject:</strong> <?= h($row['subject']) ?></p>
    <div style="white-space:pre-wrap;color:#6f6785;"><?= h($row['body']) ?></div>
</div>

<?php admin_footer(); ?>