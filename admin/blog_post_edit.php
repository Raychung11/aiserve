<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/rich_editor.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;

if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM blog_posts WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $featuredImage = trim($_POST['featured_image'] ?? '');
    $seoTitle = trim($_POST['seo_title'] ?? '');
    $seoDescription = trim($_POST['seo_description'] ?? '');
    $postType = trim($_POST['post_type'] ?? 'blog');
    $status = trim($_POST['status'] ?? 'draft');

    if ($slug === '') {
        $slug = make_slug($title);
    }

    if ($id > 0) {
        $stmt = db()->prepare("
            UPDATE blog_posts
            SET title=?, slug=?, excerpt=?, content=?, featured_image=?, seo_title=?, seo_description=?, post_type=?, status=?,
                published_at = CASE WHEN ?='published' AND published_at IS NULL THEN NOW() ELSE published_at END,
                updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([
            $title, $slug, $excerpt, $content, $featuredImage,
            $seoTitle, $seoDescription, $postType, $status, $status, $id
        ]);
        redirect('/admin/blog_post_edit.php?id=' . $id);
    } else {
        $stmt = db()->prepare("
            INSERT INTO blog_posts
            (title, slug, excerpt, content, featured_image, seo_title, seo_description, post_type, status, published_at, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ?='published' THEN NOW() ELSE NULL END, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $title, $slug, $excerpt, $content, $featuredImage,
            $seoTitle, $seoDescription, $postType, $status, $status,
            $_SESSION['admin_id'] ?? null
        ]);
        $newId = (int)db()->lastInsertId();
        redirect('/admin/blog_post_edit.php?id=' . $newId);
    }
}

admin_header($id > 0 ? 'Edit Post' : 'Create Post');
?>

<div style="display:grid;grid-template-columns:minmax(0,1.5fr) 380px;gap:20px;" class="blog-edit-layout">

    <div style="display:grid;gap:20px;">
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                <div>
                    <h3 style="margin:0;"><?= $id > 0 ? 'Edit Post' : 'Create Post' ?></h3>
                    <div class="muted" style="margin-top:6px;">Write blog articles and case studies with better structure and SEO support.</div>
                </div>
                <div class="pill"><?= $id > 0 ? 'Editing #' . (int)$id : 'New Draft' ?></div>
            </div>
        </div>

        <form method="post">
            <?= csrf_input() ?>

            <div class="card">
                <h3 style="margin-top:0;">Post Details</h3>

                <div class="form-grid">
                    <div class="field full">
                        <label>Title</label>
                        <input type="text" id="post_title" name="title" value="<?= h($row['title'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label>Slug</label>
                        <input type="text" id="post_slug" name="slug" value="<?= h($row['slug'] ?? '') ?>" placeholder="auto-generated-from-title">
                    </div>

                    <div class="field">
                        <label>Type</label>
                        <select name="post_type">
                            <option value="blog" <?= ($row['post_type'] ?? '') === 'blog' ? 'selected' : '' ?>>blog</option>
                            <option value="case_study" <?= ($row['post_type'] ?? '') === 'case_study' ? 'selected' : '' ?>>case_study</option>
                        </select>
                    </div>

                    <div class="field full">
                        <label>Excerpt</label>
                        <textarea name="excerpt" id="excerpt_field" style="min-height:120px;"><?= h($row['excerpt'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:20px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                    <div>
                        <h3 style="margin:0 0 6px 0;">Content Editor</h3>
                        <div class="muted">Use simple markdown-style structure for cleaner blog formatting.</div>
                    </div>
                    <div class="pill">Rich Writing</div>
                </div>

                <div class="rich-toolbar" style="margin-top:16px;">
                    <button type="button" onclick="wrapText('content_editor', '**', '**')">Bold</button>
                    <button type="button" onclick="wrapText('content_editor', '## ')">H2</button>
                    <button type="button" onclick="wrapText('content_editor', '### ')">H3</button>
                    <button type="button" onclick="wrapText('content_editor', '- ')">Bullet</button>
                    <button type="button" onclick="wrapText('content_editor', '1. ')">Number</button>
                    <button type="button" onclick="wrapText('content_editor', '> ')">Quote</button>
                    <button type="button" onclick="insertLine('content_editor', '\n---\n')">Divider</button>
                </div>

                <div class="field full" style="margin-top:14px;">
                    <label>Content</label>
                    <textarea id="content_editor" name="content" style="min-height:420px;"><?= h($row['content'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="card" style="margin-top:20px;">
                <h3 style="margin-top:0;">Featured Image & SEO</h3>

                <div class="form-grid">
                    <div class="field full">
                        <label>Featured Image URL</label>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <input type="text" id="featured_image_field" name="featured_image" value="<?= h($row['featured_image'] ?? '') ?>" placeholder="/uploads/media/blog-cover.jpg">
                            <button type="button" class="btn-secondary" onclick="openMediaPicker('featured_image_field')">Pick Media</button>
                            <button type="button" class="btn-secondary" id="ai_photo_prompt_btn">✨ Create photo prompt</button>
                            <button type="button" class="btn" id="ai_image_btn">Generate image</button>
                        </div>
                        <div id="photo_prompt_wrap" style="display:none;margin-top:10px;">
                            <label>Photo prompt (editable) — this is used to create the image</label>
                            <textarea id="photo_prompt" style="min-height:70px;"></textarea>
                        </div>
                        <div id="ai_image_status" class="muted" style="font-size:13px;margin-top:6px;"></div>
                    </div>

                    <div class="field">
                        <label>SEO Title</label>
                        <input type="text" name="seo_title" id="seo_title_field" value="<?= h($row['seo_title'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Status</label>
                        <select name="status">
                            <option value="draft" <?= ($row['status'] ?? '') === 'draft' ? 'selected' : '' ?>>draft</option>
                            <option value="published" <?= ($row['status'] ?? '') === 'published' ? 'selected' : '' ?>>published</option>
                        </select>
                    </div>

                    <div class="field full">
                        <label>SEO Description</label>
                        <textarea name="seo_description" id="seo_description_field" style="min-height:120px;"><?= h($row['seo_description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn">Save Post</button>
                    <?php if ($id > 0 && !empty($row['slug'])): ?>
                        <a href="/blog/<?= urlencode((string)$row['slug']) ?>" class="btn-secondary" target="_blank">View Live</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div style="display:grid;gap:20px;">
        <div class="card">
            <h3 style="margin-top:0;">✨ AI Assistant</h3>
            <div class="muted" style="font-size:13px;margin-bottom:12px;">
                Uses your OpenAI key from <a href="/admin/ai_settings.php">AI API Settings</a>. Review generated text before saving.
            </div>

            <div class="field full" style="margin-bottom:12px;">
                <label>Brief / angle (optional)</label>
                <textarea id="ai_brief" style="min-height:80px;" placeholder="e.g. focus on how SMEs cut response time with WhatsApp AI"></textarea>
            </div>

            <div style="display:grid;gap:8px;">
                <button type="button" class="btn ai-blog-btn" data-mode="article">Draft full article from title</button>
                <button type="button" class="btn-secondary ai-blog-btn" data-mode="excerpt">Write excerpt from content</button>
                <button type="button" class="btn-secondary ai-blog-btn" data-mode="seo">Generate SEO title &amp; description</button>
            </div>

            <div id="ai_blog_status" class="muted" style="font-size:13px;margin-top:10px;"></div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Live Helpers</h3>

            <div style="display:grid;gap:14px;">
                <div>
                    <div class="muted" style="font-size:13px;">Slug Preview</div>
                    <div id="slugPreview" style="font-weight:700;word-break:break-word;">/blog/<?= h($row['slug'] ?? '') ?></div>
                </div>

                <div>
                    <div class="muted" style="font-size:13px;">SEO Title Length</div>
                    <div id="seoTitleCount" style="font-weight:700;">0</div>
                </div>

                <div>
                    <div class="muted" style="font-size:13px;">SEO Description Length</div>
                    <div id="seoDescCount" style="font-weight:700;">0</div>
                </div>

                <div>
                    <div class="muted" style="font-size:13px;">Excerpt Length</div>
                    <div id="excerptCount" style="font-weight:700;">0</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Featured Image Preview</h3>

            <div style="
                border:1px solid var(--line);
                border-radius:18px;
                overflow:hidden;
                background:#faf8ff;
                min-height:220px;
                display:grid;
                place-items:center;
            ">
                <img
                    id="featuredImagePreview"
                    src="<?= h($row['featured_image'] ?? '') ?>"
                    alt="Featured Image Preview"
                    style="width:100%;height:auto;display:<?= !empty($row['featured_image']) ? 'block' : 'none' ?>;"
                >
                <div id="featuredImageFallback" class="muted" style="<?= !empty($row['featured_image']) ? 'display:none;' : '' ?>">
                    No featured image selected
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Formatting Tips</h3>
            <div class="muted" style="font-size:14px;line-height:1.8;">
                <strong>Heading:</strong> <code>## Section Title</code><br>
                <strong>Subheading:</strong> <code>### Sub Title</code><br>
                <strong>Bullet:</strong> <code>- Point here</code><br>
                <strong>Quote:</strong> <code>&gt; Highlighted message</code><br>
                <strong>Divider:</strong> <code>---</code>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Recommended Structure</h3>
            <div class="muted" style="font-size:14px;line-height:1.8;">
                Start with a strong intro.<br>
                Use short paragraphs.<br>
                Break into 3–5 sections with H2 headings.<br>
                Add bullets where useful.<br>
                End with a clear takeaway or CTA.
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 1100px){
    .blog-edit-layout{
        grid-template-columns:1fr !important;
    }
}
code{
    background:#f5f0ff;
    border:1px solid #e8defd;
    padding:2px 6px;
    border-radius:8px;
    font-size:13px;
}
</style>

<script>
function openMediaPicker(targetId) {
    window.open('/admin/media_picker.php', 'mediaPicker', 'width=1000,height=700');
    window.receiveMediaUrl = function(url) {
        const field = document.getElementById(targetId);
        if (field) {
            field.value = url;
            field.dispatchEvent(new Event('input'));
        }
    };
}

(function(){
    const titleField = document.getElementById('post_title');
    const slugField = document.getElementById('post_slug');
    const slugPreview = document.getElementById('slugPreview');
    const featuredImageField = document.getElementById('featured_image_field');
    const featuredImagePreview = document.getElementById('featuredImagePreview');
    const featuredImageFallback = document.getElementById('featuredImageFallback');
    const seoTitleField = document.getElementById('seo_title_field');
    const seoDescriptionField = document.getElementById('seo_description_field');
    const excerptField = document.getElementById('excerpt_field');
    const seoTitleCount = document.getElementById('seoTitleCount');
    const seoDescCount = document.getElementById('seoDescCount');
    const excerptCount = document.getElementById('excerptCount');

    function makeSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    function updateSlugPreview() {
        const slug = (slugField.value.trim() !== '') ? slugField.value.trim() : makeSlug(titleField.value);
        slugPreview.textContent = '/blog/' + slug;
    }

    function updateFeaturedImagePreview() {
        const val = featuredImageField.value.trim();
        if (val !== '') {
            featuredImagePreview.src = val;
            featuredImagePreview.style.display = 'block';
            featuredImageFallback.style.display = 'none';
        } else {
            featuredImagePreview.style.display = 'none';
            featuredImageFallback.style.display = 'block';
        }
    }

    function updateCounts() {
        seoTitleCount.textContent = (seoTitleField.value || '').length + ' characters';
        seoDescCount.textContent = (seoDescriptionField.value || '').length + ' characters';
        excerptCount.textContent = (excerptField.value || '').length + ' characters';
    }

    if (titleField) titleField.addEventListener('input', updateSlugPreview);
    if (slugField) slugField.addEventListener('input', updateSlugPreview);
    if (featuredImageField) featuredImageField.addEventListener('input', updateFeaturedImagePreview);
    if (seoTitleField) seoTitleField.addEventListener('input', updateCounts);
    if (seoDescriptionField) seoDescriptionField.addEventListener('input', updateCounts);
    if (excerptField) excerptField.addEventListener('input', updateCounts);

    updateSlugPreview();
    updateFeaturedImagePreview();
    updateCounts();
})();
</script>

<script>
(function(){
    const csrf = <?= json_encode(csrf_token()) ?>;
    const statusEl = document.getElementById('ai_blog_status');
    const titleEl = document.getElementById('post_title');
    const contentEl = document.getElementById('content_editor');
    const excerptEl = document.getElementById('excerpt_field');
    const seoTitleEl = document.getElementById('seo_title_field');
    const seoDescEl = document.getElementById('seo_description_field');
    const typeEl = document.querySelector('select[name="post_type"]');
    const briefEl = document.getElementById('ai_brief');

    function setVal(el, v){
        if (el && typeof v === 'string' && v !== '') {
            el.value = v;
            el.dispatchEvent(new Event('input'));
        }
    }

    document.querySelectorAll('.ai-blog-btn').forEach(function(btn){
        btn.addEventListener('click', async function(){
            const mode = btn.dataset.mode;
            const hasTitle = titleEl && titleEl.value.trim() !== '';
            const hasContent = contentEl && contentEl.value.trim() !== '';

            if (mode === 'article') {
                if (!hasTitle && !(briefEl && briefEl.value.trim())) { alert('Enter a title or a brief first.'); return; }
                if (hasContent && !confirm('This will replace the current content, excerpt and SEO fields. Continue?')) return;
            }
            if (mode === 'excerpt' && !hasTitle && !hasContent) { alert('Add a title or content first.'); return; }
            if (mode === 'seo' && !hasTitle && !hasContent) { alert('Add a title or content first.'); return; }

            const buttons = document.querySelectorAll('.ai-blog-btn');
            buttons.forEach(b => b.disabled = true);
            statusEl.textContent = 'Generating… this can take a few seconds.';
            try {
                const body = new URLSearchParams();
                body.append('csrf_token', csrf);
                body.append('mode', mode);
                body.append('title', titleEl ? titleEl.value : '');
                body.append('content', contentEl ? contentEl.value : '');
                body.append('excerpt', excerptEl ? excerptEl.value : '');
                body.append('post_type', typeEl ? typeEl.value : 'blog');
                body.append('brief', briefEl ? briefEl.value : '');

                const res = await fetch('/admin/ai_blog_generate.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: body.toString()
                });
                const _t = await res.text();
                let data;
                try { data = JSON.parse(_t); }
                catch (err) { data = {ok:false, error:'The server did not return a valid response (it may have timed out). For images, set Image Model to dall-e-3 in AI API Settings.'}; }
                if (!data.ok) { statusEl.textContent = ''; alert('AI error: ' + (data.error || 'Unknown error')); return; }

                setVal(contentEl, data.content);
                setVal(excerptEl, data.excerpt);
                setVal(seoTitleEl, data.seo_title);
                setVal(seoDescEl, data.seo_description);
                statusEl.textContent = 'Done. Review the text, then click Save Post.';
            } catch (e) {
                statusEl.textContent = '';
                alert('Request failed: ' + e.message);
            } finally {
                buttons.forEach(b => b.disabled = false);
            }
        });
    });

    // --- AI image generation (two-step: prompt first, then image) ---
    const imgStatus = document.getElementById('ai_image_status');
    const featuredField = document.getElementById('featured_image_field');
    const photoWrap = document.getElementById('photo_prompt_wrap');
    const photoPromptEl = document.getElementById('photo_prompt');
    const photoPromptBtn = document.getElementById('ai_photo_prompt_btn');
    const genImageBtn = document.getElementById('ai_image_btn');
    const imgBtns = [photoPromptBtn, genImageBtn].filter(Boolean);

    function imgBusy(on, msg){ imgBtns.forEach(b => b.disabled = on); if (imgStatus) imgStatus.textContent = msg || ''; }

    async function imgPost(url, params){
        const body = new URLSearchParams();
        body.append('csrf_token', csrf);
        Object.keys(params).forEach(k => body.append(k, params[k]));
        const res = await fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString()});
        const text = await res.text();
        try { return JSON.parse(text); }
        catch (e) { return {ok:false, error:'The server did not return a valid response (it may have timed out). Please try again.'}; }
    }

    // Step 1: create an editable photo prompt
    if (photoPromptBtn) photoPromptBtn.addEventListener('click', async function(){
        if (!titleEl || titleEl.value.trim() === '') { alert('Enter a post title first.'); return; }
        imgBusy(true, 'Creating a photo prompt…');
        const brief = titleEl.value + (excerptEl && excerptEl.value.trim() ? ' — ' + excerptEl.value.trim() : '');
        const data = await imgPost('/admin/ai_marketing_generate.php', {mode:'photo_prompt', brief: brief});
        if (!data.ok) { imgBusy(false); alert('AI error: ' + (data.error || 'Unknown error')); return; }
        if (data.photo_prompt) { photoWrap.style.display = ''; photoPromptEl.value = data.photo_prompt; }
        imgBusy(false, 'Photo prompt ready. Edit it if you like, then click Generate image.');
    });

    // Step 2: generate the image from the (edited) prompt
    if (genImageBtn) genImageBtn.addEventListener('click', async function(){
        if (!titleEl || titleEl.value.trim() === '') { alert('Enter a post title first.'); return; }
        if (featuredField && featuredField.value.trim() && !confirm('Replace the current featured image with an AI-generated one?')) return;
        imgBusy(true, 'Generating image — this can take 10–30 seconds.');
        const prompt = photoPromptEl && photoPromptEl.value.trim() ? photoPromptEl.value.trim() : '';
        const data = await imgPost('/admin/ai_image_generate.php', {
            kind: 'post',
            title: titleEl.value,
            context: (excerptEl ? excerptEl.value : '') + '\n' + (contentEl ? contentEl.value : ''),
            prompt: prompt
        });
        if (!data.ok) { imgBusy(false); alert('Image error: ' + (data.error || 'Unknown error')); return; }
        if (featuredField) { featuredField.value = data.url; featuredField.dispatchEvent(new Event('input')); }
        imgBusy(false, 'Image added. Remember to Save Post.');
    });
})();
</script>

<?php include __DIR__ . '/../inc/rich_editor.php'; ?>
<?php admin_footer(); ?>