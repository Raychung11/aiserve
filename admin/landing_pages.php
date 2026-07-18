<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$stmt = db()->query("SELECT * FROM landing_pages ORDER BY id DESC");
$rows = $stmt->fetchAll();

admin_header('Landing Pages');
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0;">Industry Landing Pages</h3>
            <p class="muted" style="margin:6px 0 0;">Create custom public pages for industries and campaigns.</p>
        </div>
        <a href="/admin/landing_page_edit.php" class="btn">Create Landing Page</a>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div style="overflow:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Edit</th>
                    <th>Sections</th>
                    <th>View</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7">No landing pages yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><?= h($r['title']) ?></td>
                        <td><?= h($r['slug']) ?></td>
                        <td><span class="pill"><?= h($r['status']) ?></span></td>
                        <td><a href="/admin/landing_page_edit.php?id=<?= (int)$r['id'] ?>">Open</a></td>
                        <td><a href="/admin/landing_sections.php?page_id=<?= (int)$r['id'] ?>">Sections</a></td>
                        <td><a href="/page.php?slug=<?= urlencode($r['slug']) ?>" target="_blank">View</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>