<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$message = '';
$error = '';

if (!function_exists('ai_cs_page_upload_file')) {
    function ai_cs_page_upload_file(array $file, string $prefix = 'ai_cs_page'): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) return null;

        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
        ];

        $mime = mime_content_type($tmpPath) ?: '';
        if (!isset($allowedMime[$mime])) return null;

        $ext = $allowedMime[$mime];
        $uploadDir = __DIR__ . '/../uploads/media/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($tmpPath, $targetPath)) return null;

        return '/uploads/media/' . $filename;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fields = [
        'ai_cs_page_hero_label',
        'ai_cs_page_hero_title',
        'ai_cs_page_hero_subtitle',
        'ai_cs_page_hero_bg_url',
        'ai_cs_page_hero_overlay_opacity',

        'ai_cs_page_logo_label',
        'ai_cs_page_logo_title',
        'ai_cs_page_logo_subtitle',

        'ai_cs_page_feature_banner_title',
        'ai_cs_page_feature_banner_subtitle',
        'ai_cs_page_feature_banner_image',

        'ai_cs_page_featured_label',
        'ai_cs_page_featured_title',
        'ai_cs_page_featured_subtitle',

        'ai_cs_page_all_label',
        'ai_cs_page_all_title',
        'ai_cs_page_all_subtitle',

        'ai_cs_page_cta_label',
        'ai_cs_page_cta_title',
        'ai_cs_page_cta_subtitle',
        'ai_cs_page_cta_button_text',
        'ai_cs_page_cta_button_link',
        'ai_cs_page_cta_secondary_text',
        'ai_cs_page_cta_secondary_link',
    ];

    foreach ($fields as $field) {
        set_setting($field, trim((string)($_POST[$field] ?? '')));
    }

    set_setting('ai_cs_page_show_logo_strip', isset($_POST['ai_cs_page_show_logo_strip']) ? '1' : '0');
    set_setting('ai_cs_page_show_featured_section', isset($_POST['ai_cs_page_show_featured_section']) ? '1' : '0');
    set_setting('ai_cs_page_show_all_section', isset($_POST['ai_cs_page_show_all_section']) ? '1' : '0');
    set_setting('ai_cs_page_show_cta_section', isset($_POST['ai_cs_page_show_cta_section']) ? '1' : '0');

    if (!empty($_FILES['ai_cs_page_hero_bg_file']['name'])) {
        $uploaded = ai_cs_page_upload_file($_FILES['ai_cs_page_hero_bg_file'], 'ai_cs_hero');
        if ($uploaded !== null) {
            set_setting('ai_cs_page_hero_bg_url', $uploaded);
        } else {
            $error = 'Hero background upload failed.';
        }
    }

    if (!empty($_FILES['ai_cs_page_feature_banner_image_file']['name'])) {
        $uploaded = ai_cs_page_upload_file($_FILES['ai_cs_page_feature_banner_image_file'], 'ai_cs_feature');
        if ($uploaded !== null) {
            set_setting('ai_cs_page_feature_banner_image', $uploaded);
        } else {
            $error = 'Feature banner upload failed.';
        }
    }

    if ($error === '') {
        $message = 'AI Customer Service page settings saved successfully.';
    }
}

admin_header('AI CS Page Builder');
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0;">AI CS Page Builder — Phase 2</h3>
            <p class="muted" style="margin:6px 0 0;">Control hero visuals, section visibility, feature banner, and CTA blocks from one place.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="/admin/client_logos.php" class="btn-secondary">Client Logos</a>
            <a href="/admin/ai_cs_showcases.php" class="btn-secondary">AI CS Showcases</a>
            <a href="/ai-customer-service-demo.php" class="btn" target="_blank">View Page</a>
        </div>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="card" style="margin-top:20px;border-color:#bbf7d0;background:#ecfdf3;color:#166534;">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="card" style="margin-top:20px;border-color:#fecaca;background:#fef2f2;color:#991b1b;">
        <?= h($error) ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" style="margin-top:20px;">
    <?= csrf_input() ?>

    <div style="display:grid;gap:20px;">
        <div class="card">
            <h3 style="margin-top:0;">Hero Section</h3>
            <div class="form-grid">
                <div class="field">
                    <label>Hero Label</label>
                    <input type="text" name="ai_cs_page_hero_label" value="<?= h(get_setting('ai_cs_page_hero_label', 'Real Business Deployment')) ?>">
                </div>

                <div class="field">
                    <label>Hero Overlay Opacity</label>
                    <input type="text" name="ai_cs_page_hero_overlay_opacity" value="<?= h(get_setting('ai_cs_page_hero_overlay_opacity', '0.88')) ?>">
                </div>

                <div class="field full">
                    <label>Hero Title</label>
                    <input type="text" name="ai_cs_page_hero_title" value="<?= h(get_setting('ai_cs_page_hero_title', 'AI customer service already supporting real businesses')) ?>">
                </div>

                <div class="field full">
                    <label>Hero Subtitle</label>
                    <textarea name="ai_cs_page_hero_subtitle"><?= h(get_setting('ai_cs_page_hero_subtitle', 'AiServe.my helps businesses improve inquiry handling, lead capture, service consistency, and workflow responsiveness through practical AI deployment.')) ?></textarea>
                </div>

                <div class="field full">
                    <label>Hero Background URL</label>
                    <input type="text" name="ai_cs_page_hero_bg_url" value="<?= h(get_setting('ai_cs_page_hero_bg_url', '')) ?>" id="heroBgUrl">
                </div>

                <div class="field full">
                    <label>Hero Background Upload</label>
                    <input type="file" name="ai_cs_page_hero_bg_file" accept="image/*">
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Section Visibility</h3>
            <div class="form-grid">
                <div class="field">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="ai_cs_page_show_logo_strip" value="1" <?= get_setting('ai_cs_page_show_logo_strip', '1') === '1' ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                        Show Client Logo Strip
                    </label>
                </div>

                <div class="field">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="ai_cs_page_show_featured_section" value="1" <?= get_setting('ai_cs_page_show_featured_section', '1') === '1' ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                        Show Featured Showcases
                    </label>
                </div>

                <div class="field">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="ai_cs_page_show_all_section" value="1" <?= get_setting('ai_cs_page_show_all_section', '1') === '1' ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                        Show All Showcases
                    </label>
                </div>

                <div class="field">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="ai_cs_page_show_cta_section" value="1" <?= get_setting('ai_cs_page_show_cta_section', '1') === '1' ? 'checked' : '' ?> style="width:auto;min-height:auto;">
                        Show CTA Section
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Client Logo Strip Section</h3>
            <div class="form-grid">
                <div class="field">
                    <label>Section Label</label>
                    <input type="text" name="ai_cs_page_logo_label" value="<?= h(get_setting('ai_cs_page_logo_label', 'Selected Organisations')) ?>">
                </div>
                <div class="field full">
                    <label>Section Title</label>
                    <input type="text" name="ai_cs_page_logo_title" value="<?= h(get_setting('ai_cs_page_logo_title', 'Trusted by real businesses')) ?>">
                </div>
                <div class="field full">
                    <label>Section Subtitle</label>
                    <textarea name="ai_cs_page_logo_subtitle"><?= h(get_setting('ai_cs_page_logo_subtitle', 'Selected brands and organisations we support through AI customer service and business enablement.')) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Feature Banner</h3>
            <div class="form-grid">
                <div class="field full">
                    <label>Banner Title</label>
                    <input type="text" name="ai_cs_page_feature_banner_title" value="<?= h(get_setting('ai_cs_page_feature_banner_title', 'AI customer service built from real business use cases')) ?>">
                </div>
                <div class="field full">
                    <label>Banner Subtitle</label>
                    <textarea name="ai_cs_page_feature_banner_subtitle"><?= h(get_setting('ai_cs_page_feature_banner_subtitle', 'From restaurants and travel operators to organisations and multi-branch businesses, AiServe.my helps turn customer interaction into a more structured, scalable, and intelligent service layer.')) ?></textarea>
                </div>
                <div class="field full">
                    <label>Banner Image URL</label>
                    <input type="text" name="ai_cs_page_feature_banner_image" value="<?= h(get_setting('ai_cs_page_feature_banner_image', '')) ?>">
                </div>
                <div class="field full">
                    <label>Banner Image Upload</label>
                    <input type="file" name="ai_cs_page_feature_banner_image_file" accept="image/*">
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Featured Showcase Section</h3>
            <div class="form-grid">
                <div class="field">
                    <label>Section Label</label>
                    <input type="text" name="ai_cs_page_featured_label" value="<?= h(get_setting('ai_cs_page_featured_label', 'Featured Use Cases')) ?>">
                </div>
                <div class="field full">
                    <label>Section Title</label>
                    <input type="text" name="ai_cs_page_featured_title" value="<?= h(get_setting('ai_cs_page_featured_title', 'Selected business deployments')) ?>">
                </div>
                <div class="field full">
                    <label>Section Subtitle</label>
                    <textarea name="ai_cs_page_featured_subtitle"><?= h(get_setting('ai_cs_page_featured_subtitle', 'Real examples of AI customer service adapted to different business models and inquiry flows.')) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">All Showcases Section</h3>
            <div class="form-grid">
                <div class="field">
                    <label>Section Label</label>
                    <input type="text" name="ai_cs_page_all_label" value="<?= h(get_setting('ai_cs_page_all_label', 'All Showcases')) ?>">
                </div>
                <div class="field full">
                    <label>Section Title</label>
                    <input type="text" name="ai_cs_page_all_title" value="<?= h(get_setting('ai_cs_page_all_title', 'More examples across industries')) ?>">
                </div>
                <div class="field full">
                    <label>Section Subtitle</label>
                    <textarea name="ai_cs_page_all_subtitle"><?= h(get_setting('ai_cs_page_all_subtitle', 'Expand this page any time from admin with more clients, sectors, and visuals.')) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">CTA Section</h3>
            <div class="form-grid">
                <div class="field">
                    <label>CTA Label</label>
                    <input type="text" name="ai_cs_page_cta_label" value="<?= h(get_setting('ai_cs_page_cta_label', 'AiServe.my')) ?>">
                </div>
                <div class="field full">
                    <label>CTA Title</label>
                    <input type="text" name="ai_cs_page_cta_title" value="<?= h(get_setting('ai_cs_page_cta_title', 'Want AI customer service built around your business?')) ?>">
                </div>
                <div class="field full">
                    <label>CTA Subtitle</label>
                    <textarea name="ai_cs_page_cta_subtitle"><?= h(get_setting('ai_cs_page_cta_subtitle', 'We help businesses deploy AI customer service in a practical way, based on real inquiry patterns, workflow requirements, and service goals.')) ?></textarea>
                </div>

                <div class="field">
                    <label>Primary Button Text</label>
                    <input type="text" name="ai_cs_page_cta_button_text" value="<?= h(get_setting('ai_cs_page_cta_button_text', 'Talk to Us')) ?>">
                </div>

                <div class="field">
                    <label>Primary Button Link</label>
                    <input type="text" name="ai_cs_page_cta_button_link" value="<?= h(get_setting('ai_cs_page_cta_button_link', '/contact.php')) ?>">
                </div>

                <div class="field">
                    <label>Secondary Button Text</label>
                    <input type="text" name="ai_cs_page_cta_secondary_text" value="<?= h(get_setting('ai_cs_page_cta_secondary_text', 'View Demos')) ?>">
                </div>

                <div class="field">
                    <label>Secondary Button Link</label>
                    <input type="text" name="ai_cs_page_cta_secondary_link" value="<?= h(get_setting('ai_cs_page_cta_secondary_link', '/demos.php')) ?>">
                </div>
            </div>
        </div>

        <div class="field full">
            <button type="submit" class="btn">Save AI CS Page Settings</button>
        </div>
    </div>
</form>

<?php admin_footer(); ?>