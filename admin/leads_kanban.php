<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$statuses = ['new', 'contacted', 'qualified', 'closed'];
$data = [];

foreach ($statuses as $status) {
    $stmt = db()->prepare("
        SELECT c.*, a.full_name AS assigned_name
        FROM contacts c
        LEFT JOIN admin_users a ON a.id = c.assigned_admin_id
        WHERE c.status = ?
        ORDER BY c.id DESC
        LIMIT 100
    ");
    $stmt->execute([$status]);
    $data[$status] = $stmt->fetchAll();
}

admin_header('Lead Kanban');
?>

<div class="card">
    <div class="top-actions">
        <a href="/admin/contacts.php" class="btn-secondary">Contact List</a>
        <a href="/admin/reminders.php" class="btn-secondary">Reminders</a>
        <a href="/admin/analytics_breakdown.php" class="btn-secondary">Analytics</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:20px;" class="kanban-grid">
    <?php foreach ($statuses as $status): ?>
        <div class="card kanban-col" data-status="<?= h($status) ?>">
            <h3 style="margin-top:0;text-transform:capitalize;"><?= h($status) ?> (<?= count($data[$status]) ?>)</h3>

            <div class="kanban-dropzone" data-status="<?= h($status) ?>" style="min-height:120px;">
                <?php foreach ($data[$status] as $lead): ?>
                    <div class="kanban-item" draggable="true" data-id="<?= (int)$lead['id'] ?>" style="padding:12px;border:1px solid #e8defd;border-radius:14px;margin-bottom:12px;background:#faf8ff;">
                        <div style="font-weight:700;"><?= h($lead['full_name']) ?></div>
                        <div class="muted" style="font-size:14px;"><?= h($lead['company_name']) ?></div>
                        <div class="muted" style="font-size:14px;"><?= h($lead['interest']) ?></div>
                        <div class="muted" style="font-size:13px;">Assigned: <?= h($lead['assigned_name'] ?: '—') ?></div>
                        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
                            <a href="/admin/contact_view.php?id=<?= (int)$lead['id'] ?>" class="btn-secondary">Open</a>
                            <a href="/admin/contact_assign.php?id=<?= (int)$lead['id'] ?>" class="btn-secondary">Assign</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
@media (max-width: 1100px){
    .kanban-grid{grid-template-columns:repeat(2,1fr)!important;}
}
@media (max-width: 760px){
    .kanban-grid{grid-template-columns:1fr!important;}
}
.kanban-dropzone.drag-over{
    outline:2px dashed #8b5cf6;
    background:#f7f1ff;
    border-radius:14px;
}
</style>

<script>
let draggedId = null;

document.querySelectorAll('.kanban-item').forEach(item => {
    item.addEventListener('dragstart', () => {
        draggedId = item.dataset.id;
    });
});

document.querySelectorAll('.kanban-dropzone').forEach(zone => {
    zone.addEventListener('dragover', e => {
        e.preventDefault();
        zone.classList.add('drag-over');
    });

    zone.addEventListener('dragleave', () => {
        zone.classList.remove('drag-over');
    });

    zone.addEventListener('drop', async e => {
        e.preventDefault();
        zone.classList.remove('drag-over');

        const newStatus = zone.dataset.status;
        if (!draggedId || !newStatus) return;

        const body = new URLSearchParams();
        body.append('id', draggedId);
        body.append('status', newStatus);
        body.append('csrf_token', <?= json_encode(csrf_token()) ?>);

        const res = await fetch('/admin/kanban_update.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString()
        });

        if (res.ok) {
            window.location.reload();
        } else {
            alert('Failed to update lead status.');
        }
    });
});
</script>

<?php admin_footer(); ?>