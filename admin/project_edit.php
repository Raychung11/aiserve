<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/projects_helper.php';

projects_ensure_schema();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;

if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        redirect('/admin/projects.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Delete
    if (($_POST['action'] ?? '') === 'delete' && $id > 0) {
        $stmt = db()->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        redirect('/admin/projects.php');
    }

    $name        = trim((string)($_POST['name'] ?? ''));
    $client      = trim((string)($_POST['client'] ?? ''));
    $category    = trim((string)($_POST['category'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $status      = trim((string)($_POST['project_status'] ?? 'in_progress'));
    $coverImage  = trim((string)($_POST['cover_image'] ?? ''));
    $projectUrl  = trim((string)($_POST['project_url'] ?? ''));
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if (!in_array($status, project_status_options(), true)) {
        $status = 'in_progress';
    }

    $slug = trim((string)($_POST['slug'] ?? ''));
    if ($slug === '') {
        $slug = make_slug($name);
    }

    if ($name === '') {
        $error = 'Project name is required.';
    } elseif ($id > 0) {
        $stmt = db()->prepare("
            UPDATE projects
            SET name = ?, slug = ?, client = ?, category = ?, description = ?, project_status = ?,
                cover_image = ?, project_url = ?, is_published = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $name, $slug, $client, $category, $description, $status,
            $coverImage, $projectUrl, $isPublished, $id
        ]);
        redirect('/admin/project_edit.php?id=' . $id . '&saved=1');
    } else {
        $stmt = db()->prepare("
            INSERT INTO projects
            (name, slug, client, category, description, project_status, cover_image, project_url, is_published, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $name, $slug, $client, $category, $description, $status,
            $coverImage, $projectUrl, $isPublished, $_SESSION['admin_id'] ?? null
        ]);
        $newId = (int)db()->lastInsertId();
        redirect('/admin/project_edit.php?id=' . $newId . '&saved=1');
    }
}

admin_header($id > 0 ? 'Edit Project' : 'Add Project');
$currentStatus = (string)($row['project_status'] ?? 'in_progress');
?>

<?php if (isset($error)): ?>
    <div class="card" style="margin-bottom:16px;border-color:#fecaca;background:#fef2f2;">
        <strong style="color:#991b1b;"><?= h($error) ?></strong>
    </div>
<?php elseif (isset($_GET['saved'])): ?>
    <div class="card" style="margin-bottom:16px;border-color:#bbf7d0;background:#ecfdf3;">
        <strong style="color:#166534;">Project saved.</strong>
    </div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-top:0;"><?= $id > 0 ? 'Edit Project' : 'Add Project' ?></h3>

    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field full">
                <label>Project Name</label>
                <input type="text" name="name" value="<?= h($row['name'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label>Client / Company</label>
                <input type="text" name="client" value="<?= h($row['client'] ?? '') ?>">
            </div>

            <div class="field">
                <label>Category</label>
                <input type="text" name="category" value="<?= h($row['category'] ?? '') ?>" placeholder="AI System / Web / Automation">
            </div>

            <div class="field full">
                <label>Description</label>
                <textarea name="description"><?= h($row['description'] ?? '') ?></textarea>
            </div>

            <div class="field full">
                <label>Cover Image URL</label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <input type="text" id="cover_image_field" name="cover_image" value="<?= h($row['cover_image'] ?? '') ?>" placeholder="/uploads/media/project-cover.jpg">
                    <button type="button" class="btn-secondary" id="ai_photo_prompt_btn">✨ Create photo prompt</button>
                    <button type="button" class="btn" id="ai_image_btn">Generate image</button>
                </div>
                <div id="photo_prompt_wrap" style="display:none;margin-top:10px;">
                    <label>Photo prompt (editable) — this is used to create the image</label>
                    <textarea id="photo_prompt" style="min-height:70px;"></textarea>
                </div>
                <img id="ai_image_preview" alt="Generated cover image" style="display:none;max-width:100%;border-radius:14px;border:1px solid var(--line);margin-top:10px;">
                <div id="ai_image_status" class="muted" style="font-size:13px;margin-top:6px;"></div>
            </div>

            <div class="field">
                <label>Project / Live URL</label>
                <input type="text" name="project_url" value="<?= h($row['project_url'] ?? '') ?>" placeholder="https://example.com/">
            </div>

            <div class="field">
                <label>Status</label>
                <select name="project_status">
                    <?php foreach (project_status_options() as $opt): ?>
                        <option value="<?= h($opt) ?>" <?= $currentStatus === $opt ? 'selected' : '' ?>><?= h(project_status_label($opt)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field full">
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_published" value="1" <?= (!isset($row['is_published']) || (int)$row['is_published'] === 1) ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                    Show on public Our Projects page
                </label>
            </div>

            <div class="field full">
                <button type="submit" class="btn">Save Project</button>
                <a href="/admin/projects.php" class="btn-secondary" style="margin-left:8px;">Back to list</a>
            </div>
        </div>
    </form>
</div>

<?php if ($id > 0): ?>
    <div class="card" style="margin-top:20px;border-color:#fecaca;">
        <h3 style="margin-top:0;">Danger zone</h3>
        <p class="muted" style="margin-top:0;">Permanently delete this project. This cannot be undone.</p>
        <form method="post" onsubmit="return confirm('Delete this project permanently?');">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn-secondary" style="color:#b91c1c;border-color:#fecaca;">Delete Project</button>
        </form>
    </div>
<?php endif; ?>

<script>
(function(){
    const csrf = <?= json_encode(csrf_token()) ?>;
    const nameEl = document.querySelector('input[name="name"]');
    const descEl = document.querySelector('textarea[name="description"]');
    const coverEl = document.getElementById('cover_image_field');
    const statusEl = document.getElementById('ai_image_status');
    const promptWrap = document.getElementById('photo_prompt_wrap');
    const promptEl = document.getElementById('photo_prompt');
    const promptBtn = document.getElementById('ai_photo_prompt_btn');
    const imageBtn = document.getElementById('ai_image_btn');
    const btns = [promptBtn, imageBtn].filter(Boolean);

    function busy(on, msg){ btns.forEach(b => b.disabled = on); if (statusEl) statusEl.textContent = msg || ''; }

    async function postForm(url, params){
        const body = new URLSearchParams();
        body.append('csrf_token', csrf);
        Object.keys(params).forEach(k => body.append(k, params[k]));
        const res = await fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString()});
        const text = await res.text();
        try { return JSON.parse(text); }
        catch (e) { return {ok:false, error:'The server did not return a valid response (it may have timed out). Please try again.'}; }
    }

    // Step 1: create an editable photo prompt
    if (promptBtn) promptBtn.addEventListener('click', async function(){
        if (!nameEl || nameEl.value.trim() === '') { alert('Enter a project name first.'); return; }
        busy(true, 'Creating a photo prompt…');
        const brief = nameEl.value + (descEl && descEl.value.trim() ? ' — ' + descEl.value.trim() : '');
        const data = await postForm('/admin/ai_marketing_generate.php', {mode:'photo_prompt', brief: brief});
        if (!data.ok) { busy(false); alert('AI error: ' + (data.error || 'Unknown error')); return; }
        if (data.photo_prompt) { promptWrap.style.display = ''; promptEl.value = data.photo_prompt; }
        busy(false, 'Photo prompt ready. Edit it if you like, then click Generate image.');
    });

    // Step 2: generate the image from the (edited) prompt
    if (imageBtn) imageBtn.addEventListener('click', async function(){
        if (!nameEl || nameEl.value.trim() === '') { alert('Enter a project name first.'); return; }
        if (coverEl && coverEl.value.trim() && !confirm('Replace the current cover image with an AI-generated one?')) return;
        busy(true, 'Generating image — this can take 10–30 seconds.');
        const prompt = promptEl && promptEl.value.trim() ? promptEl.value.trim() : '';
        const data = await postForm('/admin/ai_image_generate.php', {
            kind: 'project',
            title: nameEl.value,
            context: descEl ? descEl.value : '',
            prompt: prompt
        });
        if (!data.ok) { busy(false); alert('Image error: ' + (data.error || 'Unknown error')); return; }
        if (coverEl) coverEl.value = data.url;
        const preview = document.getElementById('ai_image_preview');
        if (preview) { preview.src = data.url; preview.style.display = 'block'; }
        busy(false, 'Image added. Remember to Save Project.');
    });
})();
</script>

<?php admin_footer(); ?>
