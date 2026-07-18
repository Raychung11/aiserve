<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$sql = "SELECT * FROM demo_sites WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (title LIKE ? OR category LIKE ? OR short_description LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY is_featured DESC, sort_order ASC, id DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

admin_header('Demo Sites');
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0;">Demo Sites Builder</h3>
            <p class="muted" style="margin:6px 0 0;">Manage the websites and systems you want to showcase as proof of execution.</p>
        </div>
        <a href="/admin/demo_site_edit.php" class="btn">Create Demo</a>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <form method="get">
        <div class="form-grid">
            <div class="field">
                <label>Search</label>
                <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search title, category, description">
            </div>

            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>draft</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>published</option>
                </select>
            </div>

            <div class="field" style="align-self:end;">
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn">Apply</button>
                    <a href="/admin/demo_sites.php" class="btn-secondary">Reset</a>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <div class="table-wrap">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th>Demo</th>
                    <th>Category</th>
                    <th>Badge</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7">No demo sites yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <div style="display:flex;gap:12px;align-items:flex-start;">
                                <div style="width:72px;height:72px;border-radius:14px;overflow:hidden;border:1px solid var(--line);background:#faf8ff;display:grid;place-items:center;flex:0 0 72px;">
                                    <?php if (!empty($r['cover_image'])): ?>
                                        <img src="<?= h($r['cover_image']) ?>" alt="<?= h($r['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="muted" style="font-size:12px;">No image</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight:800;"><?= h($r['title']) ?></div>
                                    <div class="muted" style="font-size:13px;"><?= h($r['slug']) ?></div>
                                    <?php if (!empty($r['live_url'])): ?>
                                        <div style="margin-top:4px;"><a href="<?= h($r['live_url']) ?>" target="_blank">Open live</a></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= h((string)$r['category']) ?></td>
                        <td><?= h((string)$r['badge_text']) ?></td>
                        <td><?= (int)$r['is_featured'] === 1 ? 'Yes' : 'No' ?></td>
                        <td><span class="pill"><?= h((string)$r['status']) ?></span></td>
                        <td><?= (int)$r['sort_order'] ?></td>
                        <td>
                            <a href="/admin/demo_site_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>