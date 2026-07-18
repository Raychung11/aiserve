<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$message = '';
$error = '';

if (!function_exists('homepage_upload_file')) {
    function homepage_upload_file(array $file, string $prefix = 'asset'): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return null;
        }

        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/svg+xml' => 'svg',
        ];

        $mime = mime_content_type($tmpPath) ?: '';
        if (!isset($allowedMime[$mime])) {
            return null;
        }

        $ext = $allowedMime[$mime];
        $uploadDir = __DIR__ . '/../uploads/media/';

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            return null;
        }

        return '/uploads/media/' . $filename;
    }
}

$currentLogoUrl = get_setting('site_logo_url', '');
$currentHeroBgUrl = get_setting('hero_banner_bg_url', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fields = [
        'site_logo_url',
        'site_logo_alt',
        'hero_banner_title',
        'hero_banner_subtitle',
        'hero_banner_cta_text',
        'hero_banner_cta_link',
        'hero_banner_bg_url',
        'footer_company_name',
        'footer_tagline',
        'footer_address',
        'footer_phone',
        'footer_email',
        'footer_copyright',

        // style controls
        'header_brand_title',
        'header_brand_subtitle',
        'logo_max_height',
        'hero_overlay_opacity',
        'hero_text_color',
        'footer_bg_color',
        'footer_text_color',
    ];

    foreach ($fields as $field) {
        set_setting($field, trim((string)($_POST[$field] ?? '')));
    }

    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
        set_setting('site_logo_url', '');
        $currentLogoUrl = '';
    }

    if (isset($_POST['remove_hero_bg']) && $_POST['remove_hero_bg'] === '1') {
        set_setting('hero_banner_bg_url', '');
        $currentHeroBgUrl = '';
    }

    if (!empty($_FILES['site_logo_file']['name'])) {
        $uploadedLogo = homepage_upload_file($_FILES['site_logo_file'], 'logo');
        if ($uploadedLogo !== null) {
            set_setting('site_logo_url', $uploadedLogo);
            $currentLogoUrl = $uploadedLogo;
        } else {
            $error = 'Logo upload failed. Please use JPG, PNG, WEBP, GIF, or SVG.';
        }
    }

    if (!empty($_FILES['hero_banner_bg_file']['name'])) {
        $uploadedHero = homepage_upload_file($_FILES['hero_banner_bg_file'], 'hero');
        if ($uploadedHero !== null) {
            set_setting('hero_banner_bg_url', $uploadedHero);
            $currentHeroBgUrl = $uploadedHero;
        } else {
            $error = 'Hero background upload failed. Please use JPG, PNG, WEBP, GIF, or SVG.';
        }
    }

    if ($error === '') {
        $message = 'Homepage settings saved successfully.';
    }
}

admin_header('Homepage Settings');
?>

<div class="card">
    <h3 style="margin-top:0;">Homepage Branding Settings</h3>

    <?php if ($message !== ''): ?>
        <div class="pill" style="margin-bottom:14px;"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="pill" style="margin-bottom:14px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="homepageSettingsForm">
        <?= csrf_input() ?>

        <div style="display:grid;gap:20px;">
            <!-- Logo -->
            <div class="card" style="padding:18px;">
                <h4 style="margin-top:0;margin-bottom:12px;">Logo Settings</h4>

                <div style="display:grid;grid-template-columns:1.1fr .9fr;gap:18px;" class="upload-grid">
                    <div>
                        <label style="display:block;margin-bottom:8px;font-weight:700;">Logo Upload</label>

                        <div id="logoDropzone" style="
                            border:2px dashed #d8c9fb;
                            border-radius:18px;
                            padding:22px;
                            background:#faf8ff;
                            text-align:center;
                            cursor:pointer;
                        ">
                            <input type="file" name="site_logo_file" id="site_logo_file" accept="image/*" style="display:none;">
                            <div style="font-weight:700;color:#6d28d9;">Drag & drop logo here</div>
                            <div class="muted" style="margin-top:4px;">or click to upload</div>
                            <div class="muted" style="font-size:13px;margin-top:8px;">Recommended: transparent PNG or SVG</div>
                        </div>

                        <div class="field full" style="margin-top:14px;">
                            <label>Logo URL</label>
                            <input type="text" name="site_logo_url" id="site_logo_url" value="<?= h(get_setting('site_logo_url')) ?>" placeholder="/uploads/media/logo.png">
                        </div>

                        <div class="field" style="margin-top:14px;">
                            <label>Logo Alt Text</label>
                            <input type="text" name="site_logo_alt" value="<?= h(get_setting('site_logo_alt', 'AiServe.my')) ?>">
                        </div>

                        <div class="field" style="margin-top:14px;">
                            <label>Logo Max Height (px)</label>
                            <input type="text" name="logo_max_height" value="<?= h(get_setting('logo_max_height', '46')) ?>" placeholder="46">
                        </div>

                        <div style="margin-top:14px;">
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="remove_logo" value="1" style="width:auto;min-height:auto;">
                                Remove current logo
                            </label>
                        </div>
                    </div>

                    <div>
                        <label style="display:block;margin-bottom:8px;font-weight:700;">Logo Preview</label>
                        <div style="
                            min-height:180px;
                            border:1px solid #e8defd;
                            border-radius:18px;
                            background:#fff;
                            display:grid;
                            place-items:center;
                            padding:20px;
                        ">
                            <img
                                id="logoPreview"
                                src="<?= h($currentLogoUrl !== '' ? $currentLogoUrl : '') ?>"
                                alt="Logo Preview"
                                style="max-width:100%;max-height:120px;<?= $currentLogoUrl === '' ? 'display:none;' : '' ?>"
                            >
                            <div id="logoPreviewFallback" class="muted" style="<?= $currentLogoUrl !== '' ? 'display:none;' : '' ?>">
                                No logo uploaded
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header / hero brand -->
            <div class="card" style="padding:18px;">
                <h4 style="margin-top:0;margin-bottom:12px;">Header & Brand Text</h4>

                <div class="form-grid">
                    <div class="field">
                        <label>Header Brand Title</label>
                        <input type="text" name="header_brand_title" value="<?= h(get_setting('header_brand_title', 'AiServe.my')) ?>">
                    </div>

                    <div class="field">
                        <label>Header Brand Subtitle</label>
                        <input type="text" name="header_brand_subtitle" value="<?= h(get_setting('header_brand_subtitle', 'AI Business Operating System')) ?>">
                    </div>
                </div>
            </div>

            <!-- Hero -->
            <div class="card" style="padding:18px;">
                <h4 style="margin-top:0;margin-bottom:12px;">Hero Banner</h4>

                <div style="display:grid;grid-template-columns:1.1fr .9fr;gap:18px;" class="upload-grid">
                    <div>
                        <div class="field full">
                            <label>Hero Banner Title</label>
                            <input type="text" name="hero_banner_title" value="<?= h(get_setting('hero_banner_title')) ?>">
                        </div>

                        <div class="field full" style="margin-top:14px;">
                            <label>Hero Banner Subtitle</label>
                            <textarea name="hero_banner_subtitle"><?= h(get_setting('hero_banner_subtitle')) ?></textarea>
                        </div>

                        <div class="form-grid" style="margin-top:14px;">
                            <div class="field">
                                <label>Hero CTA Text</label>
                                <input type="text" name="hero_banner_cta_text" value="<?= h(get_setting('hero_banner_cta_text', 'Get Started')) ?>">
                            </div>

                            <div class="field">
                                <label>Hero CTA Link</label>
                                <input type="text" name="hero_banner_cta_link" value="<?= h(get_setting('hero_banner_cta_link', '/contact.php')) ?>">
                            </div>
                        </div>

                        <div style="margin-top:14px;">
                            <label style="display:block;margin-bottom:8px;font-weight:700;">Hero Background Upload</label>

                            <div id="heroDropzone" style="
                                border:2px dashed #d8c9fb;
                                border-radius:18px;
                                padding:22px;
                                background:#faf8ff;
                                text-align:center;
                                cursor:pointer;
                            ">
                                <input type="file" name="hero_banner_bg_file" id="hero_banner_bg_file" accept="image/*" style="display:none;">
                                <div style="font-weight:700;color:#6d28d9;">Drag & drop hero image here</div>
                                <div class="muted" style="margin-top:4px;">or click to upload</div>
                            </div>
                        </div>

                        <div class="field full" style="margin-top:14px;">
                            <label>Hero Background Image URL</label>
                            <input type="text" name="hero_banner_bg_url" id="hero_banner_bg_url" value="<?= h(get_setting('hero_banner_bg_url')) ?>" placeholder="/uploads/media/hero-banner.jpg">
                        </div>

                        <div class="form-grid" style="margin-top:14px;">
                            <div class="field">
                                <label>Hero Overlay Opacity</label>
                                <input type="text" name="hero_overlay_opacity" value="<?= h(get_setting('hero_overlay_opacity', '0.88')) ?>" placeholder="0.88">
                            </div>

                            <div class="field">
                                <label>Hero Text Color</label>
                                <input type="text" name="hero_text_color" value="<?= h(get_setting('hero_text_color', '#ffffff')) ?>" placeholder="#ffffff">
                            </div>
                        </div>

                        <div style="margin-top:14px;">
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="remove_hero_bg" value="1" style="width:auto;min-height:auto;">
                                Remove hero background
                            </label>
                        </div>
                    </div>

                    <div>
                        <label style="display:block;margin-bottom:8px;font-weight:700;">Hero Preview</label>
                        <div style="
                            min-height:260px;
                            border:1px solid #e8defd;
                            border-radius:20px;
                            overflow:hidden;
                            background:
                                linear-gradient(135deg, rgba(109,40,217,.92), rgba(139,92,246,.82))
                                <?php if ($currentHeroBgUrl !== ''): ?>, url('<?= h($currentHeroBgUrl) ?>')<?php endif; ?>;
                            background-size:cover;
                            background-position:center;
                            color:#fff;
                            padding:22px;
                            display:flex;
                            flex-direction:column;
                            justify-content:flex-end;
                        " id="heroPreviewCard">
                            <div style="display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.14);font-size:12px;font-weight:700;margin-bottom:12px;width:max-content;">
                                AI + BI
                            </div>
                            <div id="heroPreviewTitle" style="font-size:26px;font-weight:800;line-height:1.12;margin-bottom:10px;">
                                <?= h(get_setting('hero_banner_title', 'The AI Business Layer for Modern Companies')) ?>
                            </div>
                            <div id="heroPreviewSubtitle" style="font-size:14px;line-height:1.6;color:rgba(255,255,255,.92);">
                                <?= h(get_setting('hero_banner_subtitle', 'AiServe.my helps businesses move beyond static systems with AI-powered workflow, service, reporting, and intelligence.')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="card" style="padding:18px;">
                <h4 style="margin-top:0;margin-bottom:12px;">Footer Settings</h4>

                <div class="form-grid">
                    <div class="field full">
                        <label>Footer Company Name</label>
                        <input type="text" name="footer_company_name" value="<?= h(get_setting('footer_company_name', 'SLV Group Sdn Bhd')) ?>">
                    </div>

                    <div class="field full">
                        <label>Footer Tagline</label>
                        <textarea name="footer_tagline"><?= h(get_setting('footer_tagline')) ?></textarea>
                    </div>

                    <div class="field full">
                        <label>Footer Address</label>
                        <textarea name="footer_address"><?= h(get_setting('footer_address')) ?></textarea>
                    </div>

                    <div class="field">
                        <label>Footer Phone</label>
                        <input type="text" name="footer_phone" value="<?= h(get_setting('footer_phone')) ?>">
                    </div>

                    <div class="field">
                        <label>Footer Email</label>
                        <input type="text" name="footer_email" value="<?= h(get_setting('footer_email')) ?>">
                    </div>

                    <div class="field">
                        <label>Footer Background Color</label>
                        <input type="text" name="footer_bg_color" value="<?= h(get_setting('footer_bg_color', '#140d22')) ?>" placeholder="#140d22">
                    </div>

                    <div class="field">
                        <label>Footer Text Color</label>
                        <input type="text" name="footer_text_color" value="<?= h(get_setting('footer_text_color', '#ddd6fe')) ?>" placeholder="#ddd6fe">
                    </div>

                    <div class="field full">
                        <label>Footer Copyright</label>
                        <input type="text" name="footer_copyright" value="<?= h(get_setting('footer_copyright')) ?>">
                    </div>
                </div>
            </div>

            <div class="field full">
                <button type="submit" class="btn">Save Homepage Settings</button>
            </div>
        </div>
    </form>
</div>

<style>
@media (max-width: 900px){
    .upload-grid{
        grid-template-columns:1fr !important;
    }
}
</style>

<script>
(function(){
    const logoDropzone = document.getElementById('logoDropzone');
    const logoInput = document.getElementById('site_logo_file');
    const logoUrlInput = document.getElementById('site_logo_url');
    const logoPreview = document.getElementById('logoPreview');
    const logoPreviewFallback = document.getElementById('logoPreviewFallback');

    const heroDropzone = document.getElementById('heroDropzone');
    const heroInput = document.getElementById('hero_banner_bg_file');
    const heroUrlInput = document.getElementById('hero_banner_bg_url');
    const heroPreviewCard = document.getElementById('heroPreviewCard');
    const heroTitleInput = document.querySelector('input[name="hero_banner_title"]');
    const heroSubtitleInput = document.querySelector('textarea[name="hero_banner_subtitle"]');
    const heroPreviewTitle = document.getElementById('heroPreviewTitle');
    const heroPreviewSubtitle = document.getElementById('heroPreviewSubtitle');

    function bindDropzone(dropzone, input) {
        if (!dropzone || !input) return;

        dropzone.addEventListener('click', () => input.click());

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#8b5cf6';
            dropzone.style.background = '#f5f0ff';
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.style.borderColor = '#d8c9fb';
            dropzone.style.background = '#faf8ff';
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#d8c9fb';
            dropzone.style.background = '#faf8ff';
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    bindDropzone(logoDropzone, logoInput);
    bindDropzone(heroDropzone, heroInput);

    if (logoInput) {
        logoInput.addEventListener('change', function(){
            const file = this.files && this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e){
                if (logoPreview) {
                    logoPreview.src = e.target.result;
                    logoPreview.style.display = 'block';
                }
                if (logoPreviewFallback) {
                    logoPreviewFallback.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        });
    }

    if (logoUrlInput) {
        logoUrlInput.addEventListener('input', function(){
            const val = this.value.trim();
            if (val !== '' && logoPreview) {
                logoPreview.src = val;
                logoPreview.style.display = 'block';
                if (logoPreviewFallback) logoPreviewFallback.style.display = 'none';
            } else {
                if (logoPreview) logoPreview.style.display = 'none';
                if (logoPreviewFallback) logoPreviewFallback.style.display = 'block';
            }
        });
    }

    if (heroInput) {
        heroInput.addEventListener('change', function(){
            const file = this.files && this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e){
                heroPreviewCard.style.background =
                    "linear-gradient(135deg, rgba(109,40,217,.92), rgba(139,92,246,.82)), url('" + e.target.result + "')";
                heroPreviewCard.style.backgroundSize = "cover";
                heroPreviewCard.style.backgroundPosition = "center";
            };
            reader.readAsDataURL(file);
        });
    }

    if (heroUrlInput) {
        heroUrlInput.addEventListener('input', function(){
            const val = this.value.trim();
            heroPreviewCard.style.background =
                val !== ''
                    ? "linear-gradient(135deg, rgba(109,40,217,.92), rgba(139,92,246,.82)), url('" + val + "')"
                    : "linear-gradient(135deg, rgba(109,40,217,.92), rgba(139,92,246,.82))";
            heroPreviewCard.style.backgroundSize = "cover";
            heroPreviewCard.style.backgroundPosition = "center";
        });
    }

    if (heroTitleInput) {
        heroTitleInput.addEventListener('input', function(){
            heroPreviewTitle.textContent = this.value || 'Hero title preview';
        });
    }

    if (heroSubtitleInput) {
        heroSubtitleInput.addEventListener('input', function(){
            heroPreviewSubtitle.textContent = this.value || 'Hero subtitle preview';
        });
    }
})();
</script>

<?php admin_footer(); ?>