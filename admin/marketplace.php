<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();

// ── POST actions ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pid    = (int)($_POST['product_id'] ?? 0);
    $action = sanitize_string($_POST['action'] ?? '');

    $chk = $db->prepare("SELECT id, status, is_featured FROM marketplace_products WHERE id = ? AND deleted_at IS NULL");
    $chk->execute([$pid]);
    $prod = $chk->fetch();

    if ($prod) {
        if ($action === 'suspend') {
            $db->prepare("UPDATE marketplace_products SET status='suspended', updated_at=NOW() WHERE id=?")->execute([$pid]);
            audit_log((int)$admin['id'], 'super_admin', 'product_suspended', 'marketplace_products', $pid, ['status'=>$prod['status']], ['status'=>'suspended']);
            flash_set('main', 'Produk #' . $pid . ' digantung.', 'warning');
        } elseif ($action === 'activate') {
            $db->prepare("UPDATE marketplace_products SET status='active', updated_at=NOW() WHERE id=?")->execute([$pid]);
            audit_log((int)$admin['id'], 'super_admin', 'product_activated', 'marketplace_products', $pid, ['status'=>$prod['status']], ['status'=>'active']);
            flash_set('main', 'Produk #' . $pid . ' diaktifkan.', 'success');
        } elseif ($action === 'toggle_featured') {
            $new_val = $prod['is_featured'] ? 0 : 1;
            $db->prepare("UPDATE marketplace_products SET is_featured=?, updated_at=NOW() WHERE id=?")->execute([$new_val, $pid]);
            flash_set('main', 'Status pilihan produk #' . $pid . ' dikemaskini.', 'success');
        } elseif ($action === 'delete') {
            $db->prepare("UPDATE marketplace_products SET deleted_at=NOW() WHERE id=?")->execute([$pid]);
            audit_log((int)$admin['id'], 'super_admin', 'product_deleted', 'marketplace_products', $pid, null, null);
            flash_set('main', 'Produk #' . $pid . ' dipadam.', 'warning');
        }
    }
    redirect(APP_URL . '/admin/marketplace');
}

// ── Filters ───────────────────────────────────────────────────────────────────
$search   = sanitize_string($_GET['q'] ?? '');
$status_f = sanitize_string($_GET['status'] ?? '');
$cat_f    = (int)($_GET['cat'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));
$per      = 20;

$where  = ['mp.deleted_at IS NULL'];
$params = [];
if ($search) {
    $where[]  = '(mp.title LIKE ? OR m.company_name LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($status_f) { $where[] = 'mp.status = ?';      $params[] = $status_f; }
if ($cat_f)    { $where[] = 'mp.category_id = ?'; $params[] = $cat_f; }
$ws = implode(' AND ', $where);

// Stats
$stats = $db->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status='active'    THEN 1 ELSE 0 END) AS active,
    SUM(CASE WHEN status='draft'     THEN 1 ELSE 0 END) AS draft,
    SUM(CASE WHEN status='suspended' THEN 1 ELSE 0 END) AS suspended,
    SUM(CASE WHEN status='sold_out'  THEN 1 ELSE 0 END) AS sold_out,
    SUM(CASE WHEN is_featured=1      THEN 1 ELSE 0 END) AS featured
FROM marketplace_products WHERE deleted_at IS NULL")->fetch();

// Categories for filter
$cats = $db->query("SELECT id, name FROM marketplace_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();

// Total + paginate
$cnt = $db->prepare("SELECT COUNT(*) FROM marketplace_products mp JOIN merchants m ON m.id=mp.merchant_id WHERE {$ws}");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();

$url_base = APP_URL . '/admin/marketplace?status=' . urlencode($status_f) . '&cat=' . $cat_f . '&q=' . urlencode($search) . '&page={page}';
$pag = paginate($total, $per, $page, $url_base);

$stmt = $db->prepare("SELECT mp.*, mc.name AS cat_name, m.company_name
    FROM marketplace_products mp
    LEFT JOIN marketplace_categories mc ON mc.id = mp.category_id
    JOIN merchants m ON m.id = mp.merchant_id
    WHERE {$ws}
    ORDER BY mp.created_at DESC
    LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$per, $pag['offset']]));
$products = $stmt->fetchAll();

layout_begin_admin('Pasaran Maya — Moderasi');
?>
<div class="page-title">🛍️ Pasaran Maya — Moderasi</div>
<?= flash_html('main') ?>

<!-- Stats Bar -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin-bottom:20px;">
  <div class="stat-card"><div class="stat-value"><?= (int)$stats['total'] ?></div><div class="stat-label">Jumlah Produk</div></div>
  <div class="stat-card stat-card-green"><div class="stat-value"><?= (int)$stats['active'] ?></div><div class="stat-label">Aktif</div></div>
  <div class="stat-card"><div class="stat-value"><?= (int)$stats['draft'] ?></div><div class="stat-label">Draf</div></div>
  <div class="stat-card stat-card-blue"><div class="stat-value"><?= (int)$stats['featured'] ?></div><div class="stat-label">Produk Pilihan</div></div>
  <div class="stat-card" style="border-left:3px solid #EF4444;"><div class="stat-value"><?= (int)$stats['suspended'] ?></div><div class="stat-label">Digantung</div></div>
  <div class="stat-card"><div class="stat-value"><?= (int)$stats['sold_out'] ?></div><div class="stat-label">Habis Dijual</div></div>
</div>

<!-- Filters -->
<div class="card-kasih" style="margin-bottom:16px;">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label class="form-label" style="font-size:0.78rem;">Cari Produk / Pedagang</label>
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Nama produk atau pedagang..." class="form-input" style="min-width:200px;">
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Status</label>
      <select name="status" class="form-input">
        <option value="">Semua Status</option>
        <option value="active"    <?= $status_f==='active'    ? 'selected':'' ?>>✅ Aktif</option>
        <option value="draft"     <?= $status_f==='draft'     ? 'selected':'' ?>>📝 Draf</option>
        <option value="sold_out"  <?= $status_f==='sold_out'  ? 'selected':'' ?>>📦 Habis</option>
        <option value="suspended" <?= $status_f==='suspended' ? 'selected':'' ?>>🚫 Digantung</option>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Kategori</label>
      <select name="cat" class="form-input">
        <option value="0">Semua Kategori</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $cat_f===$c['id'] ? 'selected':'' ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:6px;">
      <button type="submit" class="btn-gold btn-sm">Tapis</button>
      <a href="<?= APP_URL ?>/admin/marketplace" class="btn-gold-outline btn-sm">Reset</a>
    </div>
  </form>
</div>

<!-- Products Table -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="section-title" style="margin:0;">Jumlah: <?= $total ?> produk</div>
  </div>

  <?php if (empty($products)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:32px 0;font-size:0.875rem;">Tiada produk dijumpai.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead>
        <tr>
          <th style="width:52px;">Gambar</th>
          <th>Produk</th>
          <th>Pedagang</th>
          <th>Kategori</th>
          <th>Harga (pts)</th>
          <th>Nilai RM</th>
          <th>Stok</th>
          <th>Status</th>
          <th>Pilihan</th>
          <th>Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td style="padding:8px;">
            <?php if ($p['image']): ?>
              <img src="<?= APP_URL ?>/uploads/products/<?= h($p['image']) ?>"
                   style="width:44px;height:44px;object-fit:cover;border-radius:8px;display:block;">
            <?php else: ?>
              <div style="width:44px;height:44px;background:#F3F4F6;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">📦</div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;font-size:0.875rem;"><?= h($p['title']) ?></div>
            <div style="font-size:0.72rem;color:#9CA3AF;">#<?= $p['id'] ?></div>
          </td>
          <td style="font-size:0.82rem;"><?= h($p['company_name']) ?></td>
          <td style="font-size:0.78rem;color:#6B7280;"><?= h($p['cat_name'] ?? '—') ?></td>
          <td style="font-weight:700;color:var(--gold-dark);"><?= gold_format_points($p['points_price']) ?></td>
          <td style="font-size:0.82rem;color:#6B7280;"><?= gold_format_rm((string)$p['rm_reference_value']) ?></td>
          <td style="font-size:0.85rem;">
            <?php if ($p['stock_qty'] === null): ?>
              <span style="color:#9CA3AF;">∞</span>
            <?php elseif ((int)$p['stock_qty'] === 0): ?>
              <span style="color:#EF4444;font-weight:600;">0</span>
            <?php else: ?>
              <?= (int)$p['stock_qty'] ?>
            <?php endif; ?>
          </td>
          <td><?= status_badge($p['status']) ?></td>
          <td style="text-align:center;">
            <?php if ($p['is_featured']): ?>
              <span style="background:#FEF3C7;color:#92400E;border-radius:9999px;padding:2px 10px;font-size:0.72rem;font-weight:700;">⭐ Pilihan</span>
            <?php else: ?>
              <span style="color:#D1D5DB;font-size:0.75rem;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" style="display:flex;gap:4px;flex-wrap:wrap;">
              <?= csrf_field() ?>
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>">

              <?php if ($p['status'] === 'active'): ?>
                <button name="action" value="suspend" class="btn-sm"
                        style="background:#FEE2E2;color:#991B1B;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:0.78rem;"
                        onclick="return confirm('Gantung produk ini?')">Gantung</button>
              <?php elseif (in_array($p['status'], ['draft','suspended','sold_out'])): ?>
                <button name="action" value="activate" class="btn-gold btn-sm"
                        style="font-size:0.78rem;">Aktifkan</button>
              <?php endif; ?>

              <button name="action" value="toggle_featured" class="btn-gold-outline btn-sm"
                      style="font-size:0.78rem;" title="<?= $p['is_featured'] ? 'Buang dari pilihan' : 'Jadikan produk pilihan' ?>">
                <?= $p['is_featured'] ? '★ Buang' : '☆ Pilihan' ?>
              </button>

              <button name="action" value="delete" class="btn-sm"
                      style="background:#F3F4F6;color:#6B7280;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:0.78rem;"
                      onclick="return confirm('Padam produk ini secara kekal?')">🗑</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>
<?php layout_end_admin(); ?>
