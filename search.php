<?php
require_once __DIR__ . '/inc/public_search_helper.php';

$q = trim($_GET['q'] ?? '');

$pageTitle = 'Search | AiServe.my';
$pageDescription = 'Search AiServe.my content.';
include __DIR__ . '/inc/public_header.php';

$results = public_search_results($q);
?>

<section class="hero" style="padding-bottom:20px;">
    <div class="container" style="max-width:920px;">
        <div class="eyebrow">Search</div>
        <h1>Search AiServe.my</h1>

        <form method="get" action="/search.php" style="margin-top:18px;">
            <div class="form-grid">
                <div class="field full">
                    <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search blog posts, case studies, and landing pages">
                </div>
                <div class="field full">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </div>
        </form>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:920px;">
        <?php if ($q === ''): ?>
            <div class="card"><p>Enter a keyword to search.</p></div>
        <?php elseif (!$results): ?>
            <div class="card"><p>No results found for <strong><?= h($q) ?></strong>.</p></div>
        <?php else: ?>
            <div class="grid-3" style="grid-template-columns:1fr;">
                <?php foreach ($results as $item): ?>
                    <div class="card">
                        <div class="eyebrow" style="margin-bottom:10px;"><?= h($item['type']) ?></div>
                        <h3><?= h($item['title']) ?></h3>
                        <p><?= h($item['summary']) ?></p>
                        <div style="margin-top:14px;">
                            <a href="<?= h($item['url']) ?>" class="btn btn-secondary">Open</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/inc/public_footer.php'; ?>