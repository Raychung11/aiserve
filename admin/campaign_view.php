<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect('/admin/contacts.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $status = trim($_POST['status'] ?? 'new');
    $subscribed = isset($_POST['is_subscribed']) ? 1 : 0;

    $allowed = ['new','contacted','qualified','closed'];
    if (!in_array($status, $allowed, true)) {
        $status = 'new';
    }

    $stmt = db()->prepare("UPDATE contacts SET status = ?, is_subscribed = ? WHERE id = ?");
    $stmt->execute([$status, $subscribed, $id]);

    redirect('/admin/contact_view.php?id=' . $id);
}

$stmt = db()->prepare("SELECT * FROM contacts WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    redirect('/admin/contacts.php');
}

admin_header('Contact Detail');
?>

<div class="card">
    <h3 style="margin-top:0;">Lead Detail #<?= (int)$row['id'] ?></h3>

    <div class="form-grid">
        <div class="field">
            <label>Full Name</label>
            <input type="text" value="<?= h($row['full_name']) ?>" readonly>
        </div>
        <div class="field">
            <label>Company Name</label>
            <input type="text" value="<?= h($row['company_name']) ?>" readonly>
        </div>
        <div class="field">
            <label>Email</label>
            <input type="text" value="<?= h($row['email']) ?>" readonly>
        </div>
        <div class="field">
            <label>Phone</label>
            <input type="text" value="<?= h($row['phone']) ?>" readonly>
        </div>
        <div class="field">
            <label>Interest</label>
            <input type="text" value="<?= h($row['interest']) ?>" readonly>
        </div>
        <div class="field">
            <label>Company Size</label>
            <input type="text" value="<?= h($row['company_size']) ?>" readonly>
        </div>
        <div class="field full">
            <label>Message</label>
            <textarea readonly><?= h($row['message']) ?></textarea>
        </div>
        <div class="field full" style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" class="btn">Update Contact</button>
            <a href="/admin/contact_notes.php?contact_id=<?= (int)$row['id'] ?>" class="btn-secondary">Notes & Follow Up</a>
        </div>
    </div>

    <form method="post" style="margin-top:18px;">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="new" <?= $row['status']==='new' ? 'selected' : '' ?>>new</option>
                    <option value="contacted" <?= $row['status']==='contacted' ? 'selected' : '' ?>>contacted</option>
                    <option value="qualified" <?= $row['status']==='qualified' ? 'selected' : '' ?>>qualified</option>
                    <option value="closed" <?= $row['status']==='closed' ? 'selected' : '' ?>>closed</option>
                </select>
            </div>

            <div class="field">
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_subscribed" value="1" <?= (int)$row['is_subscribed'] === 1 ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                    Email subscribed
                </label>
            </div>

            <div class="field" style="align-self:end;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn">Update Campaign</button>
                <a href="/admin/campaign_test_email.php?id=<?= (int)$row['id'] ?>" class="btn-secondary">Test Email</a>
                <a href="/admin/campaign_recipients.php?id=<?= (int)$row['id'] ?>" class="btn-secondary">Recipient Logs</a>
                <?php if ($row['status'] !== 'sent'): ?>
                    <a href="/admin/campaign_send.php?id=<?= (int)$row['id'] ?>" class="btn-secondary">Send Campaign</a>
                <?php endif; ?>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Update Contact</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>