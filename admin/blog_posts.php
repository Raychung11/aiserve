<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$q = trim((string)($_GET['q'] ?? ''));
$type = trim((string)($_GET['type'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$sql = "SELECT * FROM blog_posts WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (title LIKE ? OR slug LIKE ? OR excerpt LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($type !== '') {
    $sql .= " AND post_type = ?";
    $params[] = $type;
}

if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY id DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalPosts = count($rows);
$publishedCount = 0;
$draftCount = 0;

foreach ($rows as $r) {
    if (($r['status'] ?? '') === 'published') {
        $publishedCount++;
    } else {
        $draftCount++;
    }
}

admin_header('Blog & Case Studies');
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0;">Blog & Case Study Posts</h3>
            <p class="muted" style="margin:6px 0 0;">Manage public blog articles and case studies from one place.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="/admin/blog_post_edit.php" class="btn">Create Post</a>
        </div>
    </div>

    <div class="stats" style="margin-top:18px;">
        <div class="card stat">
            <strong><?= (int)$totalPosts ?></strong>
            <span class="muted">Total Posts</span>
        </div>
        <div class="card stat">
            <strong><?= (int)$publishedCount ?></strong>
            <span class="muted">Published</span>
        </div>
        <div class="card stat">
            <strong><?= (int)$draftCount ?></strong>
            <span class="muted">Drafts</span>
        </div>
        <div class="card stat">
            <strong><?= count(array_filter($rows, fn($r) => ($r['post_type'] ?? '') === 'case_study')) ?></strong>
            <span class="muted">Case Studies</span>
        </div>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Search & Filter</h3>

    <form method="get">
        <div class="form-grid">
            <div class="field">
                <label>Search</label>
                <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search title, slug, excerpt">
            </div>

            <div class="field">
                <label>Type</label>
                <select name="type">
                    <option value="">All</option>
                    <option value="blog" <?= $type === 'blog' ? 'selected' : '' ?>>blog</option>
                    <option value="case_study" <?= $type === 'case_study' ? 'selected' : '' ?>>case_study</option>
                </select>
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
                    <a href="/admin/blog_posts.php" class="btn-secondary">Reset</a>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <h3 style="margin:0;">All Posts</h3>
        <div class="muted"><?= (int)$totalPosts ?> result(s)</div>
    </div>

    <div class="table-wrap" style="margin-top:16px;">
        <table class="desktop-table">
            <thead>
                <tr>
                    <th style="width:72px;">ID</th>
                    <th>Post</th>
                    <th style="width:120px;">Type</th>
                    <th style="width:120px;">Status</th>
                    <th style="width:180px;">Published</th>
                    <th style="width:180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No posts found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $featured = trim((string)($r['featured_image'] ?? ''));
                    $slugVal = trim((string)($r['slug'] ?? ''));
                    $liveUrl = $slugVal !== '' ? '/blog/' . rawurlencode($slugVal) : '';
                    ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td>
                            <div style="display:flex;gap:14px;align-items:flex-start;">
                                <div style="
                                    width:68px;height:68px;flex:0 0 68px;
                                    border-radius:14px;overflow:hidden;
                                    border:1px solid var(--line);
                                    background:#faf8ff;
                                    display:grid;place-items:center;
                                ">
                                    <?php if ($featured !== ''): ?>
                                        <img src="<?= h($featured) ?>" alt="<?= h($r['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="muted" style="font-size:12px;">No image</span>
                                    <?php endif; ?>
                                </div>

                                <div style="min-width:0;">
                                    <div style="font-weight:800;color:var(--dark);margin-bottom:4px;">
                                        <?= h($r['title']) ?>
                                    </div>
                                    <div class="muted" style="font-size:13px;word-break:break-word;">
                                        /blog/<?= h($slugVal) ?>
                                    </div>
                                    <?php if (!empty($r['excerpt'])): ?>
                                        <div class="muted" style="margin-top:6px;font-size:14px;">
                                            <?= h(mb_strimwidth((string)$r['excerpt'], 0, 110, '...')) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="pill"><?= h((string)$r['post_type']) ?></span>
                        </td>
                        <td>
                            <?php if (($r['status'] ?? '') === 'published'): ?>
                                <span class="pill" style="background:#ecfdf3;color:#166534;border:1px solid #bbf7d0;">published</span>
                            <?php else: ?>
                                <span class="pill" style="background:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe;">draft</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= h((string)($r['published_at'] ?? '—')) ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                <a href="/admin/blog_post_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                                <?php if ($liveUrl !== '' && ($r['status'] ?? '') === 'published'): ?>
                                    <a href="<?= h($liveUrl) ?>" target="_blank">View</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="mobile-card-list">
            <?php if ($rows): ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $featured = trim((string)($r['featured_image'] ?? ''));
                    $slugVal = trim((string)($r['slug'] ?? ''));
                    $liveUrl = $slugVal !== '' ? '/blog/' . rawurlencode($slugVal) : '';
                    ?>
                    <div class="mobile-card">
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div style="
                                width:64px;height:64px;flex:0 0 64px;
                                border-radius:12px;overflow:hidden;
                                border:1px solid var(--line);
                                background:#faf8ff;
                                display:grid;place-items:center;
                            ">
                                <?php if ($featured !== ''): ?>
                                    <img src="<?= h($featured) ?>" alt="<?= h($r['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <span class="muted" style="font-size:11px;">No image</span>
                                <?php endif; ?>
                            </div>

                            <div style="min-width:0;flex:1;">
                                <div style="font-weight:800;"><?= h($r['title']) ?></div>
                                <div class="muted" style="font-size:13px;">/blog/<?= h($slugVal) ?></div>
                            </div>
                        </div>

                        <div class="row" style="margin-top:12px;"><span class="label">Type</span><?= h((string)$r['post_type']) ?></div>
                        <div class="row"><span class="label">Status</span><?= h((string)$r['status']) ?></div>
                        <div class="row"><span class="label">Published</span><?= h((string)($r['published_at'] ?? '—')) ?></div>

                        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:12px;">
                            <a href="/admin/blog_post_edit.php?id=<?= (int)$r['id'] ?>" class="btn-secondary">Edit</a>
                            <?php if ($liveUrl !== '' && ($r['status'] ?? '') === 'published'): ?>
                                <a href="<?= h($liveUrl) ?>" target="_blank" class="btn-secondary">View</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 900px){
    .desktop-table{
        display:none;
    }
}
@media (min-width: 901px){
    .mobile-card-list{
        display:none;
    }
}
.mobile-card-list{
    display:grid;
    gap:14px;
    margin-top:8px;
}
.mobile-card{
    border:1px solid var(--line);
    border-radius:18px;
    background:#fff;
    padding:16px;
}
.mobile-card .row{
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-top:8px;
    font-size:14px;
}
.mobile-card .label{
    color:var(--muted);
    font-weight:700;
}
</style>

<?php admin_footer(); ?>