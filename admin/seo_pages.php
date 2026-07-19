<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$pages = [
    'home' => 'Homepage',
    'about' => 'About',
    'ai_bos' => 'Ai-BOS',
    'solutions' => 'Solutions',
    'industries' => 'Industries',
    'projects' => 'Projects',
    'contact' => 'Contact',
    'blog' => 'Blog'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($pages as $key => $label) {
        $seoTitle = trim($_POST[$key . '_title'] ?? '');
        $seoDescription = trim($_POST[$key . '_description'] ?? '');

        $stmt = db()->prepare("
            INSERT INTO seo_pages (page_key, seo_title, seo_description, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE seo_title = VALUES(seo_title), seo_description = VALUES(seo_description), updated_at = NOW()
        ");
        $stmt->execute([$key, $seoTitle, $seoDescription]);
    }

    redirect('/admin/seo_pages.php');
}

$existing = db()->query("SELECT * FROM seo_pages")->fetchAll();
$map = [];
foreach ($existing as $row) {
    $map[$row['page_key']] = $row;
}

admin_header('SEO Meta');
?>

<div class="card">
    <h3 style="margin-top:0;">SEO Meta Editor</h3>
    <form method="post">
        <?= csrf_input() ?>
        <?php foreach ($pages as $key => $label): ?>
            <div class="card" style="margin-top:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:6px;">
                    <strong><?= h($label) ?></strong>
                    <button type="button" class="btn-secondary ai-seo-btn"
                            data-key="<?= h($key) ?>" data-label="<?= h($label) ?>"
                            style="min-height:36px;padding:0 12px;">✨ Generate with AI</button>
                </div>
                <div class="form-grid">
                    <div class="field full">
                        <label><?= h($label) ?> SEO Title</label>
                        <input type="text" id="seo_title_<?= h($key) ?>" name="<?= h($key) ?>_title" value="<?= h($map[$key]['seo_title'] ?? '') ?>">
                    </div>
                    <div class="field full">
                        <label><?= h($label) ?> SEO Description</label>
                        <textarea id="seo_desc_<?= h($key) ?>" name="<?= h($key) ?>_description"><?= h($map[$key]['seo_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="margin-top:18px;">
            <button type="submit" class="btn">Save SEO Meta</button>
            <span class="muted" style="margin-left:10px;font-size:13px;">Tip: use “Generate with AI”, review the text, then Save. Requires an API key in <a href="/admin/ai_settings.php">AI API Settings</a>.</span>
        </div>
    </form>
</div>

<script>
(function(){
    const csrf = <?= json_encode(csrf_token()) ?>;
    document.querySelectorAll('.ai-seo-btn').forEach(function(btn){
        btn.addEventListener('click', async function(){
            const key = btn.dataset.key;
            const label = btn.dataset.label;
            const titleEl = document.getElementById('seo_title_' + key);
            const descEl = document.getElementById('seo_desc_' + key);
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Generating…';
            try {
                const body = new URLSearchParams();
                body.append('csrf_token', csrf);
                body.append('page_key', key);
                body.append('label', label);
                body.append('current_title', titleEl ? titleEl.value : '');
                body.append('current_description', descEl ? descEl.value : '');
                const res = await fetch('/admin/ai_seo_generate.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: body.toString()
                });
                const data = await res.json();
                if (data.ok) {
                    if (titleEl && data.title) titleEl.value = data.title;
                    if (descEl && data.description) descEl.value = data.description;
                } else {
                    alert('AI error: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Request failed: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.textContent = original;
            }
        });
    });
})();
</script>

<?php admin_footer(); ?>