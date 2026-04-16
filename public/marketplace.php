<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();

$db       = getDB();
$cat_id   = (int)($_GET['cat'] ?? 0);
$search   = sanitize_string($_GET['q'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per      = 12;

// Categories
$categories = $db->query("SELECT * FROM marketplace_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();

// Products query
$where    = ["mp.status = 'active'", "mp.deleted_at IS NULL"];
$params   = [];
if ($cat_id) { $where[] = "mp.category_id = ?"; $params[] = $cat_id; }
if ($search) { $where[] = "(mp.title LIKE ? OR mp.description LIKE ?)"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
$where_sql = implode(' AND ', $where);

$total_stmt = $db->prepare("SELECT COUNT(*) FROM marketplace_products mp WHERE {$where_sql}");
$total_stmt->execute($params);
$total = (int)$total_stmt->fetchColumn();

$url_p = APP_URL . '/marketplace?page={page}' . ($cat_id ? "&cat={$cat_id}" : '') . ($search ? '&q=' . urlencode($search) : '');
$pag   = paginate($total, $per, $page, $url_p);

$prod_params = array_merge($params, [$per, $pag['offset']]);
$stmt = $db->prepare("
    SELECT mp.*, mc.name AS cat_name, m.company_name
    FROM marketplace_products mp
    JOIN marketplace_categories mc ON mc.id = mp.category_id
    JOIN merchants m ON m.id = mp.merchant_id
    WHERE {$where_sql}
    ORDER BY mp.is_featured DESC, mp.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($prod_params);
$products = $stmt->fetchAll();

// Featured
$featured = $db->query("SELECT mp.*, mc.name AS cat_name, m.company_name FROM marketplace_products mp JOIN marketplace_categories mc ON mc.id=mp.category_id JOIN merchants m ON m.id=mp.merchant_id WHERE mp.status='active' AND mp.is_featured=1 AND mp.deleted_at IS NULL ORDER BY RAND() LIMIT 4")->fetchAll();

layout_head('Pasaran Maya');
layout_header(auth_user() ?: null);
echo '<main style="max-width:1200px;margin:0 auto;padding:24px 16px;">';
layout_flash();
?>

<!-- Hero -->
<div class="hero-kasih" style="border-radius:var(--border-radius);margin-bottom:24px;padding:50px 24px;">
  <div class="hero-tagline" style="font-size:2rem;">Pasaran Maya Kasih Gold</div>
  <p class="hero-subtitle">Guna Gold Points untuk tebus produk &amp; servis berkualiti dari pedagang berlesen.</p>
  <form method="GET" style="display:flex;gap:8px;justify-content:center;max-width:400px;margin:0 auto;">
    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Cari produk..." class="input-kasih" style="flex:1;">
    <button type="submit" class="btn-gold">Cari</button>
  </form>
</div>

<!-- Categories -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
  <a href="<?= APP_URL ?>/marketplace" class="<?= !$cat_id ? 'btn-gold' : 'btn-gold-outline' ?> btn-sm">🛍️ Semua</a>
  <?php foreach ($categories as $cat): ?>
  <a href="<?= APP_URL ?>/marketplace?cat=<?= $cat['id'] ?>" class="<?= $cat_id===$cat['id'] ? 'btn-gold' : 'btn-gold-outline' ?> btn-sm">
    <?= h($cat['icon'] ?? '') ?> <?= h($cat['name']) ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Featured (only on homepage) -->
<?php if (!$cat_id && !$search && $page === 1 && !empty($featured)): ?>
<div style="margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="section-title" style="margin:0;">✦ Produk Pilihan</div>
  </div>
  <div class="marketplace-grid">
    <?php foreach ($featured as $prod): ?>
    <a href="<?= APP_URL ?>/product?id=<?= $prod['id'] ?>" class="product-card" style="position:relative;">
      <div class="product-img-placeholder"><?= h($prod['image'] ? '<img src="' . UPLOAD_URL . '/products/' . h($prod['image']) . '" alt="" style="width:100%;height:175px;object-fit:cover;">' : '🏷️') ?></div>
      <span class="featured-badge">✦ Pilihan</span>
      <div class="product-body">
        <div class="product-cat"><?= h($prod['cat_name']) ?></div>
        <div class="product-title"><?= h($prod['title']) ?></div>
        <div class="product-price"><?= gold_format_points($prod['points_price']) ?> pts</div>
        <div class="product-rm">≈ <?= gold_format_rm($prod['rm_reference_value']) ?> &nbsp;|&nbsp; <?= h($prod['company_name']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <hr style="margin:20px 0;border:none;border-top:1px solid #E5E7EB;">
</div>
<?php endif; ?>

<!-- All products -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="section-title" style="margin:0;">
    <?= $cat_id ? h($categories[array_search($cat_id, array_column($categories,'id'))]['name'] ?? 'Produk') : 'Semua Produk' ?>
    <span style="font-size:0.8rem;color:#9CA3AF;font-weight:400;">(<?= $total ?> item)</span>
  </div>
</div>

<?php if (empty($products)): ?>
  <div style="text-align:center;padding:48px;color:#9CA3AF;">
    <div style="font-size:3rem;margin-bottom:12px;">🔍</div>
    <p>Tiada produk dijumpai<?= $search ? ' untuk "' . h($search) . '"' : '' ?>.</p>
  </div>
<?php else: ?>
  <div class="marketplace-grid" style="margin-bottom:24px;">
    <?php foreach ($products as $prod): ?>
    <a href="<?= APP_URL ?>/product?id=<?= $prod['id'] ?>" class="product-card" style="position:relative;">
      <div class="product-img-placeholder"><?= $prod['is_featured'] ? '<span class="featured-badge">✦</span>' : '' ?>🏷️</div>
      <div class="product-body">
        <div class="product-cat"><?= h($prod['cat_name']) ?></div>
        <div class="product-title"><?= h($prod['title']) ?></div>
        <div class="product-price"><?= gold_format_points($prod['points_price']) ?> pts</div>
        <div class="product-rm">≈ <?= gold_format_rm($prod['rm_reference_value']) ?> &nbsp;|&nbsp; <?= h($prod['company_name']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?= pagination_html($pag) ?>
<?php endif; ?>

</main>
<?php layout_footer(); ?>
