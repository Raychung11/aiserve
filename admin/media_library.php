<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$rows = db()->query("SELECT * FROM media_library ORDER BY id DESC")->fetchAll();

admin_header('Media Library');
?>

<div class="card">
    <div class="top-actions">
        <a href="/admin/upload_media.php" class="btn">Upload Media</a>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Uploaded Files</h3>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;" class="media-grid">
        <?php if (!$rows): ?>
            <p class="muted">No media uploaded yet.</p>
        <?php else: ?>
            <?php foreach ($rows as $file): ?>
                <div style="padding:14px;border:1px solid #e8defd;border-radius:16px;background:#fff;">
                    <?php if (strpos((string)$file['file_type'], 'image/') === 0): ?>
                        <img src="<?= h($file['file_url']) ?>" alt="" style="width:100%;height:160px;object-fit:cover;border-radius:12px;margin-bottom:10px;">
                    <?php endif; ?>
                    <div style="font-weight:700;font-size:14px;word-break:break-word;"><?= h($file['file_name']) ?></div>
                    <div class="muted" style="font-size:13px;"><?= h($file['file_type']) ?></div>
                    <div style="margin-top:10px;">
                        <a href="<?= h($file['file_url']) ?>" target="_blank" class="btn-secondary">Open</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
@media (max-width: 1100px){
    .media-grid{grid-template-columns:repeat(2,1fr)!important;}
}
@media (max-width: 760px){
    .media-grid{grid-template-columns:1fr!important;}
}
</style>

<?php admin_footer(); ?>