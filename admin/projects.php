<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/projects_helper.php';

projects_ensure_schema();

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$sql = "SELECT * FROM projects WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (name LIKE ? OR client LIKE ? OR category LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status !== '' && in_array($status, project_status_options(), true)) {
    $sql .= " AND project_status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY id DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

admin_header('Projects');
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0;">Projects</h3>
            <p class="muted" style="margin:6px 0 0;">Record the projects your team is working on. Published projects appear on the public Our Projects page.</p>
        </div>
        <a href="/admin/project_edit.php" class="btn">Add Project</a>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <form method="get">
        <div class="form-grid">
            <div class="field">
                <label>Search</label>
                <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search name, client, category">
            </div>

            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    <?php foreach (project_status_options() as $opt): ?>
                        <option value="<?= h($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= h(project_status_label($opt)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field" style="align-self:end;">
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn">Apply</button>
                    <a href="/admin/projects.php" class="btn-secondary">Reset</a>
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
                    <th>Project</th>
                    <th>Client</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Public</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No projects yet. Click &ldquo;Add Project&rdquo; to create one.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <div style="display:flex;gap:12px;align-items:flex-start;">
                                <div style="width:72px;height:72px;border-radius:14px;overflow:hidden;border:1px solid var(--line);background:#faf8ff;display:grid;place-items:center;flex:0 0 72px;">
                                    <?php if (!empty($r['cover_image'])): ?>
                                        <img src="<?= h($r['cover_image']) ?>" alt="<?= h($r['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="muted" style="font-size:12px;">No image</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight:800;"><?= h($r['name']) ?></div>
                                    <?php if (!empty($r['project_url'])): ?>
                                        <div style="margin-top:4px;"><a href="<?= h($r['project_url']) ?>" target="_blank" rel="noopener">Open link</a></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= h((string)$r['client']) ?></td>
                        <td><?= h((string)$r['category']) ?></td>
                        <td><span class="pill"><?= h(project_status_label((string)$r['project_status'])) ?></span></td>
                        <td><?= (int)$r['is_published'] === 1 ? 'Yes' : 'No' ?></td>
                        <td><a href="/admin/project_edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
