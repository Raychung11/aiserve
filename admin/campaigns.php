<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');

    if ($subject !== '' && $body !== '') {
        if (!in_array($status, campaign_status_options(), true)) {
            $status = 'draft';
        }

        $stmt = db()->prepare("
            INSERT INTO email_campaigns (subject, body, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $subject,
            $body,
            $status,
            $_SESSION['admin_id'] ?? null
        ]);

        redirect('/admin/campaigns.php');
    }
}

$stmt = db()->query("SELECT * FROM email_campaigns ORDER BY id DESC");
$rows = $stmt->fetchAll();

admin_header('Campaigns');
?>

<div class="card" style="border-color:#ddd6fe;background:linear-gradient(180deg,#faf8ff,#ffffff);">
    <h3 style="margin-top:0;">✨ AI Marketing Assistant</h3>
    <div class="muted" style="font-size:13px;margin-bottom:12px;">
        Describe what you want to promote, then let AI draft the email and a campaign image. Uses your key from <a href="/admin/ai_settings.php">AI API Settings</a>.
    </div>

    <div class="form-grid">
        <div class="field full">
            <label>Campaign goal / brief</label>
            <textarea id="ai_brief" style="min-height:80px;" placeholder="e.g. Promote our WhatsApp AI customer service to F&amp;B businesses — offer a free demo this month"></textarea>
        </div>
        <div class="field full">
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="btn" id="ai_draft_btn">Draft email (subject + body)</button>
                <button type="button" class="btn-secondary" id="ai_photo_prompt_btn">Create photo prompt</button>
                <button type="button" class="btn-secondary" id="ai_image_btn">Generate image</button>
            </div>
            <div id="ai_marketing_status" class="muted" style="font-size:13px;margin-top:8px;"></div>
        </div>

        <div class="field full" id="photo_prompt_wrap" style="display:none;">
            <label>Photo prompt (editable) — use for “Generate image” or any image tool</label>
            <textarea id="photo_prompt" style="min-height:70px;"></textarea>
        </div>

        <div class="field full" id="ai_image_wrap" style="display:none;">
            <label>Generated image</label>
            <img id="ai_image_preview" alt="Generated campaign image" style="max-width:100%;border-radius:14px;border:1px solid var(--line);display:none;">
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:8px;">
                <input type="text" id="ai_image_url" readonly style="flex:1;min-width:220px;">
                <button type="button" class="btn-secondary" id="ai_insert_image_btn">Add link into email body</button>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Create Campaign</h3>
    <form method="post">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field full">
                <label>Subject</label>
                <input type="text" id="camp_subject" name="subject" required>
            </div>
            <div class="field full">
                <label>Body</label>
                <textarea name="body" id="camp_body" required>Dear [Name],

We would like to introduce AiServe.my, the AI Business Operating System by SLV Group Sdn Bhd.

AiServe.my helps businesses move beyond static software by building AI-powered systems for workflow, reporting, customer service, and operational intelligence.

If your team is exploring AI + BI transformation, we would be glad to schedule a discussion.

Best regards,
SLV Group Sdn Bhd
AiServe.my</textarea>
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="draft">draft</option>
                    <option value="ready">ready</option>
                </select>
            </div>
            <div class="field" style="align-self:end;">
                <button type="submit" class="btn">Save Campaign</button>
            </div>
        </div>
    </form>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Saved Campaigns</h3>

    <div style="overflow:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Sent At</th>
                    <th>Updated</th>
                    <th>Open</th>
                    <th>Recipients</th>
                    <th>Send</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr>
                    <td colspan="8">No campaigns yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><?= h($r['subject']) ?></td>
                        <td><span class="pill"><?= h($r['status']) ?></span></td>
                        <td><?= h($r['sent_at'] ?? '') ?></td>
                        <td><?= h($r['updated_at']) ?></td>
                        <td><a href="/admin/campaign_view.php?id=<?= (int)$r['id'] ?>">Open</a></td>
                        <td><a href="/admin/campaign_recipients.php?id=<?= (int)$r['id'] ?>">Logs</a></td>
                        <td>
                            <?php if ($r['status'] !== 'sent'): ?>
                                <a href="/admin/campaign_send.php?id=<?= (int)$r['id'] ?>">Send</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    const csrf = <?= json_encode(csrf_token()) ?>;
    const briefEl = document.getElementById('ai_brief');
    const subjectEl = document.getElementById('camp_subject');
    const bodyEl = document.getElementById('camp_body');
    const statusEl = document.getElementById('ai_marketing_status');
    const photoWrap = document.getElementById('photo_prompt_wrap');
    const photoPromptEl = document.getElementById('photo_prompt');
    const imageWrap = document.getElementById('ai_image_wrap');
    const imagePreview = document.getElementById('ai_image_preview');
    const imageUrlEl = document.getElementById('ai_image_url');

    const draftBtn = document.getElementById('ai_draft_btn');
    const photoBtn = document.getElementById('ai_photo_prompt_btn');
    const imageBtn = document.getElementById('ai_image_btn');
    const insertBtn = document.getElementById('ai_insert_image_btn');
    const allBtns = [draftBtn, photoBtn, imageBtn].filter(Boolean);

    function busy(on, msg){
        allBtns.forEach(b => b.disabled = on);
        if (statusEl) statusEl.textContent = msg || '';
    }
    function requireBrief(){
        if (!briefEl || briefEl.value.trim() === '') { alert('Describe the campaign goal first.'); return false; }
        return true;
    }

    async function postForm(url, params){
        const body = new URLSearchParams();
        body.append('csrf_token', csrf);
        Object.keys(params).forEach(k => body.append(k, params[k]));
        const res = await fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString()});
        const text = await res.text();
        try { return JSON.parse(text); }
        catch (e) { return {ok:false, error:'The server did not return a valid response (it may have timed out). If generating an image, set Image Model to dall-e-3 in AI API Settings.'}; }
    }

    if (draftBtn) draftBtn.addEventListener('click', async function(){
        if (!requireBrief()) return;
        if (bodyEl && bodyEl.value.trim() && !confirm('Replace the current subject and body with an AI draft?')) return;
        busy(true, 'Writing your email…');
        try {
            const data = await postForm('/admin/ai_marketing_generate.php', {mode:'campaign', brief: briefEl.value});
            if (!data.ok) { busy(false); alert('AI error: ' + (data.error||'Unknown')); return; }
            if (data.subject && subjectEl) subjectEl.value = data.subject;
            if (data.body && bodyEl) bodyEl.value = data.body;
            busy(false, 'Draft ready. Review, then Save Campaign.');
        } catch(e){ busy(false); alert('Request failed: ' + e.message); }
    });

    if (photoBtn) photoBtn.addEventListener('click', async function(){
        if (!requireBrief()) return;
        busy(true, 'Creating a photo prompt…');
        try {
            const data = await postForm('/admin/ai_marketing_generate.php', {mode:'photo_prompt', brief: briefEl.value, subject: subjectEl ? subjectEl.value : ''});
            if (!data.ok) { busy(false); alert('AI error: ' + (data.error||'Unknown')); return; }
            if (data.photo_prompt) {
                photoWrap.style.display = '';
                photoPromptEl.value = data.photo_prompt;
            }
            busy(false, 'Photo prompt ready. Edit it if you like, then click Generate image.');
        } catch(e){ busy(false); alert('Request failed: ' + e.message); }
    });

    if (imageBtn) imageBtn.addEventListener('click', async function(){
        const prompt = photoPromptEl && photoPromptEl.value.trim() ? photoPromptEl.value.trim() : '';
        if (!prompt && !requireBrief()) return;
        busy(true, 'Generating image — this can take 10–30 seconds.');
        try {
            const data = await postForm('/admin/ai_image_generate.php', {
                kind: 'marketing',
                title: subjectEl ? subjectEl.value : '',
                context: briefEl ? briefEl.value : '',
                prompt: prompt
            });
            if (!data.ok) { busy(false); alert('Image error: ' + (data.error||'Unknown')); return; }
            imageWrap.style.display = '';
            imagePreview.src = data.url;
            imagePreview.style.display = 'block';
            imageUrlEl.value = data.url;
            busy(false, 'Image generated and saved to Media Library.');
        } catch(e){ busy(false); alert('Request failed: ' + e.message); }
    });

    if (insertBtn) insertBtn.addEventListener('click', function(){
        if (!imageUrlEl.value || !bodyEl) return;
        const full = location.origin + imageUrlEl.value;
        bodyEl.value = bodyEl.value.replace(/\s*$/, '') + '\n\nImage: ' + full + '\n';
        if (statusEl) statusEl.textContent = 'Image link added to the email body.';
    });
})();
</script>

<?php admin_footer(); ?>