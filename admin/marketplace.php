<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pid    = (int)$_POST['product_id'];
    $action = sanitize_string($_POST['action'] ?? '');
    if ($pid && $action === 'suspend') {
        $db->prepare("UPDATE marketplace_products SET status='suspended' WHERE id=?")->execute([$pid]);
        flash_set('main','Produk digantung.','warning');
    } elseif ($pid && $action === 'activate') {
        $db->prepare("UPDATE marketplace_products SET status='active' WHERE id=?")->execute([$pid]);
        flash_set('main','Produk diaktifkan.','success');
    }
    redirect(APP_URL . '/admin/marketplace');
}

$page = max(1,(int)($_GET['page']??1)); $per = 20;
$search = sanitize_string($_GET['q']??'');
$status_f = sanitize_string($_GET['status']??'');
$where = ['mp.deleted_at IS NULL']; $params=[];
if ($search) { $where[] = "mp.title LIKE ?"; $params[] = "%$search%"; }
if ($status_f) { $where[] = "mp.status=?"; $params[] = $status_f; }
$ws = implode(' AND ', $where);

$cnt = $db->prepare("SELECT COUNT(*) FROM marketplace_products mp WHERE $ws"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag = paginate($total,$per,$page,APP_URL.'/admin/marketplace?page={page}'.($status_f?'&status='.$status_f:'').($search?'&q='.urlencode($search):''));

$stmt = $db->prepare("SELECT mp.*, mc.name AS cat, m.company_name FROM marketplace_products mp JOIN marketplace_categories mc ON mc.id=mp.category_id JOIN merchants m ON m.id=mp.merchant_id WHERE $ws ORDER BY mp.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$per,$pag['offset']])); $products = $stmt->fetchAll();

layout_begin_admin('Pasaran Maya — Moderasi');
?>
<div class="page-title">🛍️ Pasaran Maya — Moderasi</div>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <a href="<?= APP_URL ?>/admin/marketplace" class="<?= !$status_f?'btn-gold':'btn-gold-outline' ?> btn-sm">Semua</a>
  <a href="?status=active"    class="<?= $status_f==='active'   ?'btn-gold':'btn-gold-outline' ?> btn-sm">✅ Aktif</a>
  <a href="?status=draft"     class="<?= $status_f==='draft'    ?'btn-gold':'btn-gold-outline' ?> btn-sm">📝 Draf</a>
  <a href="?status=suspended" class="<?= $status_f==='suspended'?'btn-gold':'btn-gold-outline' ?> btn-sm">🚫 Digantung</a>
  <form method="GET" style="display:flex;gap:6px;margin-left:auto;">
    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Cari produk..." class="input-kasih" style="width:200px;">
    <?php if ($status_f): ?><input type="hidden" name="status" value="<?= h($status_f) ?>"><?php endif; ?>
    <button type="submit" class="btn-gold btn-sm">Cari</button>
  </form>
</div>
<?= flash_html('main') ?>
<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Tajuk</th><th>Pedagang</th><th>Kategori</th><th>Harga (pts)</th><th>Stok</th><th>Status</th><th>Pilihan</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td style="font-size:0.85rem;font-weight:600;"><?= h($p['title']) ?></td>
          <td style="font-size:0.82rem;"><?= h($p['company_name']) ?></td>
          <td style="font-size:0.78rem;color:#9CA3AF;"><?= h($p['cat']) ?></td>
          <td style="font-weight:700;color:var(--gold-dark);"><?= gold_format_points($p['points_price']) ?></td>
          <td style="font-size:0.82rem;"><?= $p['stock_qty'] ?? '∞' ?></td>
          <td><?= status_badge($p['status']) ?></td>
          <td><?= $p['is_featured'] ? '<span class="badge badge-paid">Pilihan</span>' : '' ?></td>
          <td>
            <form method="POST" style="display:flex;gap:4px;">
              <?= csrf_field() ?>
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
              <?php if ($p['status']==='active'): ?>
                <button name="action" value="suspend" class="btn-danger btn-sm" style="padding:3px 8px;">Gantung</button>
              <?php else: ?>
                <button name="action" value="activate" class="btn-gold btn-sm" style="padding:3px 8px;">Aktif</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
</div>
<?php layout_end_admin(); ?>
