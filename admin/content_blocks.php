<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';
require_once __DIR__ . '/../inc/content_blocks.php';

$message = '';

$defaultBlocks = [
    'home_hero_title' => [
        'title' => 'Homepage Hero Title',
        'content' => 'The AI Business Layer for Modern Companies'
    ],
    'home_hero_text' => [
        'title' => 'Homepage Hero Text',
        'content' => 'AiServe.io helps businesses move beyond websites and static systems by building AI-powered business layers for service, workflow, reporting, and operational intelligence.'
    ],
    'home_product_title' => [
        'title' => 'Homepage Product Title',
        'content' => 'One AI-powered business layer across service, workflow, and intelligence'
    ],
    'home_product_text' => [
        'title' => 'Homepage Product Text',
        'content' => 'Ai-BOS is the proprietary business operating layer by AiServe.io. It connects customer interaction, workflow, reporting, and decision support into one practical system.'
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($defaultBlocks as $key => $meta) {
        $postedTitle = trim($_POST[$key . '_title'] ?? $meta['title']);
        $postedContent = trim($_POST[$key . '_content'] ?? '');
        save_block($key, $postedTitle, $postedContent);
    }

    $message = 'Content blocks updated successfully.';
}

admin_header('Content Blocks');
?>

<div class="card">
    <h3 style="margin-top:0;">Homepage Content Blocks</h3>
    <p class="muted">Edit important homepage text without changing PHP files.</p>

    <?php if ($message !== ''): ?>
        <div class="pill" style="margin-bottom:14px;"><?= h($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_input() ?>

        <?php foreach ($defaultBlocks as $key => $meta): ?>
            <div class="card" style="margin-top:16px;">
                <div class="form-grid">
                    <div class="field full">
                        <label><?= h($meta['title']) ?> - Title</label>
                        <input type="text" name="<?= h($key) ?>_title" value="<?= h(get_block_title($key, $meta['title'])) ?>">
                    </div>
                    <div class="field full">
                        <label><?= h($meta['title']) ?> - Content</label>
                        <textarea name="<?= h($key) ?>_content"><?= h(get_block($key, $meta['content'])) ?></textarea>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="margin-top:18px;">
            <button type="submit" class="btn">Save Content Blocks</button>
        </div>
    </form>
</div>

<?php admin_footer(); ?>