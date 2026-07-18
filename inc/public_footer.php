<?php
$footerCompany   = get_setting('footer_company_name', 'SLV Group Sdn Bhd');
$footerTagline   = get_setting('footer_tagline', 'AI-powered business operating systems for modern companies.');
$footerAddress   = get_setting('footer_address', '');
$footerPhone     = get_setting('footer_phone', '');
$footerEmail     = get_setting('footer_email', '');
$footerCopyright = get_setting('footer_copyright', '© 2026 SLV Group Sdn Bhd. All rights reserved.');

$siteLogoUrl = get_setting('site_logo_url', '');
$siteLogoAlt = get_setting('site_logo_alt', 'AiServe.my');
?>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="brand" style="margin-bottom:14px;">
                    <?php if ($siteLogoUrl !== ''): ?>
                        <img src="<?= h($siteLogoUrl) ?>" alt="<?= h($siteLogoAlt) ?>" class="brand-logo">
                    <?php else: ?>
                        <span class="brand-mark">A</span>
                    <?php endif; ?>

                    <div class="brand-text">
                        <span class="brand-title"><?= h($footerCompany) ?></span>
                        <span class="brand-subtitle">Powered by AiServe.my</span>
                    </div>
                </div>

                <p class="small" style="max-width:520px;line-height:1.8;margin:0 0 14px 0;">
                    <?= h($footerTagline) ?>
                </p>

                <?php if ($footerAddress !== ''): ?>
                    <p class="small" style="line-height:1.8;margin:0 0 12px 0;">
                        <?= nl2br(h($footerAddress)) ?>
                    </p>
                <?php endif; ?>

                <?php if ($footerPhone !== ''): ?>
                    <p class="small" style="margin:0 0 8px 0;">
                        <strong style="color:var(--dark);">Phone:</strong> <?= h($footerPhone) ?>
                    </p>
                <?php endif; ?>

                <?php if ($footerEmail !== ''): ?>
                    <p class="small" style="margin:0;">
                        <strong style="color:var(--dark);">Email:</strong> <?= h($footerEmail) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div>
                <h4 class="footer-title">Company</h4>
                <div class="footer-links">
                    <a href="/about.php">About</a>
                    <a href="/solutions.php">Solutions</a>
                    <a href="/industries.php">Industries</a>
                    <a href="/contact.php">Contact</a>
                </div>
            </div>

            <div>
                <h4 class="footer-title">Resources</h4>
                <div class="footer-links">
                    <a href="/blog.php">Blog</a>
                    <a href="/search.php">Search</a>
                    <a href="/sitemap.xml">Sitemap</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom small">
            <?= h($footerCopyright) ?>
        </div>
    </div>
</footer>
<?php require_once __DIR__ . '/chat_widget.php'; ?>
</body>
</html>