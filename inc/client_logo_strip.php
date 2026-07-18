<?php
declare(strict_types=1);

$stmt = db()->query("
    SELECT *
    FROM client_logo_strips
    WHERE status = 'published'
    ORDER BY sort_order ASC, id DESC
");
$clientLogos = $stmt->fetchAll();

$logoLabel = get_setting('ai_cs_page_logo_label', 'Selected Organisations');
$logoTitle = get_setting('ai_cs_page_logo_title', 'Trusted by real businesses');
$logoSubtitle = get_setting('ai_cs_page_logo_subtitle', 'Selected brands and organisations we support through AI customer service and business enablement.');
?>

<?php if ($clientLogos): ?>
<section class="section" style="padding-top:14px;padding-bottom:14px;">
    <div class="container">
        <div class="card" style="padding:22px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
                <div>
                    <div class="label"><?= h($logoLabel) ?></div>
                    <h2 style="margin:0 0 6px 0;font-size:28px;line-height:1.15;"><?= h($logoTitle) ?></h2>
                    <p style="margin:0;color:var(--muted);"><?= h($logoSubtitle) ?></p>
                </div>
            </div>

            <div class="client-logo-strip">
                <?php foreach ($clientLogos as $logo): ?>
                    <?php
                    $companyName = (string)($logo['company_name'] ?? '');
                    $logoImage = (string)($logo['logo_image'] ?? '');
                    $websiteUrl = trim((string)($logo['website_url'] ?? ''));
                    ?>
                    <div class="client-logo-item">
                        <?php if ($websiteUrl !== ''): ?>
                            <a href="<?= h($websiteUrl) ?>" target="_blank" class="client-logo-box">
                                <?php if ($logoImage !== ''): ?>
                                    <img src="<?= h($logoImage) ?>" alt="<?= h($companyName) ?>">
                                <?php else: ?>
                                    <span><?= h($companyName) ?></span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <div class="client-logo-box">
                                <?php if ($logoImage !== ''): ?>
                                    <img src="<?= h($logoImage) ?>" alt="<?= h($companyName) ?>">
                                <?php else: ?>
                                    <span><?= h($companyName) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="small" style="margin-top:8px;text-align:center;font-weight:700;">
                            <?= h($companyName) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<style>
.client-logo-strip{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:16px;
    align-items:stretch;
}
.client-logo-item{min-width:0}
.client-logo-box{
    width:100%;
    min-height:92px;
    border:1px solid var(--line);
    border-radius:18px;
    background:#fff;
    display:grid;
    place-items:center;
    padding:16px;
    box-shadow:var(--shadow);
}
.client-logo-box img{
    max-width:100%;
    max-height:52px;
    object-fit:contain;
    display:block;
}
.client-logo-box span{
    font-weight:800;
    color:var(--dark);
    text-align:center;
    font-size:14px;
}
@media (max-width: 1100px){
    .client-logo-strip{grid-template-columns:repeat(3,1fr);}
}
@media (max-width: 760px){
    .client-logo-strip{grid-template-columns:repeat(2,1fr);}
}
</style>
<?php endif; ?>