<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$contactId = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0;
if ($contactId <= 0) redirect('/admin/contacts.php');

$stmt = db()->prepare("SELECT * FROM contacts WHERE id = ? LIMIT 1");
$stmt->execute([$contactId]);
$contact = $stmt->fetch();
if (!$contact) redirect('/admin/contacts.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $noteText = trim($_POST['note_text'] ?? '');
    $followUpDate = trim($_POST['follow_up_date'] ?? '');

    if ($noteText !== '') {
        $stmt = db()->prepare("
            INSERT INTO contact_notes (contact_id, note_text, follow_up_date, created_by, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $contactId,
            $noteText,
            $followUpDate !== '' ? $followUpDate : null,
            $_SESSION['admin_id'] ?? null
        ]);
        redirect('/admin/contact_notes.php?contact_id=' . $contactId);
    }
}

$stmt = db()->prepare("
    SELECT n.*, a.full_name AS admin_name
    FROM contact_notes n
    LEFT JOIN admin_users a ON a.id = n.created_by
    WHERE n.contact_id = ?
    ORDER BY n.id DESC
");
$stmt->execute([$contactId]);
$notes = $stmt->fetchAll();

admin_header('Contact Notes');
?>

<div class="card">
    <h3 style="margin-top:0;">Notes for <?= h($contact['full_name']) ?></h3>
    <p class="muted"><?= h($contact['company_name']) ?> • <?= h($contact['email']) ?></p>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field full">
                <label>Note</label>
                <textarea name="note_text" required></textarea>
            </div>
            <div class="field">
                <label>Follow Up Date</label>
                <input type="date" name="follow_up_date">
            </div>
            <div class="field" style="align-self:end;">
                <button type="submit" class="btn">Add Note</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">History</h3>
    <?php if (!$notes): ?>
        <p class="muted">No notes yet.</p>
    <?php else: ?>
        <?php foreach ($notes as $note): ?>
            <div style="padding:14px 0;border-bottom:1px solid #e8defd;">
                <div style="white-space:pre-wrap;"><?= h($note['note_text']) ?></div>
                <div class="muted" style="margin-top:8px;font-size:14px;">
                    By <?= h($note['admin_name']) ?> • <?= h($note['created_at']) ?>
                    <?php if (!empty($note['follow_up_date'])): ?>
                        • Follow up: <?= h($note['follow_up_date']) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>