<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../inc/functions.php';

$rows = db()->query("SELECT * FROM media_library ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Media Picker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{font-family:Inter,system-ui,sans-serif;margin:20px;background:#f7f4ff}
        .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
        .item{background:#fff;border:1px solid #e8defd;border-radius:16px;padding:14px}
        .item img{width:100%;height:160px;object-fit:cover;border-radius:12px;margin-bottom:10px}
        button{padding:10px 14px;border-radius:10px;border:1px solid #e8defd;background:#fff;color:#6d28d9;font-weight:700;cursor:pointer}
        @media (max-width: 900px){.grid{grid-template-columns:repeat(2,1fr)}}
        @media (max-width: 600px){.grid{grid-template-columns:1fr}}
    </style>
    <script>
        function choose(url) {
            if (window.opener && typeof window.opener.receiveMediaUrl === 'function') {
                window.opener.receiveMediaUrl(url);
                window.close();
            } else {
                alert(url);
            }
        }
    </script>
</head>
<body>
    <h2>Media Picker</h2>
    <div class="grid">
        <?php foreach ($rows as $file): ?>
            <div class="item">
                <?php if (strpos((string)$file['file_type'], 'image/') === 0): ?>
                    <img src="<?= h($file['file_url']) ?>" alt="">
                <?php endif; ?>
                <div style="font-weight:700;font-size:14px;word-break:break-word;"><?= h($file['file_name']) ?></div>
                <div style="font-size:13px;color:#6f6785;margin:6px 0;"><?= h($file['file_url']) ?></div>
                <button onclick="choose('<?= h($file['file_url']) ?>')">Use This</button>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>