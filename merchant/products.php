<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/merchant_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

$db      = getDB();
$user_id = auth_id();

$m_stmt = $db->prepare("SELECT * FROM merchants WHERE user_id=?");
$m_stmt->execute([$user_id]);
$merchant = $m_stmt->fetch();
if (!$merchant) { flash_set('main','Data pedagang tidak dijumpai.','error'); redirect(APP_URL.'/login'); }
$merchant_id = (int)$merchant['id'];

$errors = [];
$success = '';

// ── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        if ($merchant['status'] !== 'active') {
            $errors[] = 'Akaun pedagang anda belum aktif. Tidak boleh tambah/edit produk.';
        } else {
            $name        = sanitize_string($_POST['product_name'] ?? '');
            $desc        = sanitize_string($_POST['description'] ?? '');
            $price_pts   = sanitize_decimal($_POST['price_points'] ?? '0');
            $stock       = (int)($_POST['stock_quantity'] ?? 0);
            $cat_id      = (int)($_POST['category_id'] ?? 0);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $status      = $_POST['status'] ?? 'active';

            if (!$name)             $errors[] = 'Nama produk diperlukan.';
            if ((float)$price_pts <= 0) $errors[] = 'Harga mata mesti lebih dari 0.';
            if ($stock < 0)         $errors[] = 'Stok tidak boleh negatif.';

            if (empty($errors)) {
                // Handle image upload
                $image_filename = null;
                if (!empty($_FILES['product_image']['name'])) {
                    $uploaded = upload_file('product_image', ['image/jpeg','image/png','image/webp'], __DIR__.'/../uploads/products/', 3145728);
                    if ($uploaded) $image_filename = $uploaded;
                    else $errors[] = 'Gambar tidak sah (PNG/JPG/WebP, maks 3MB).';
                }

                if (empty($errors)) {
                    if ($action === 'add') {
                        $sql = "INSERT INTO marketplace_products (merchant_id,category_id,name,description,price_points,stock_quantity,is_featured,status,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$merchant_id,$cat_id,$name,$desc,$price_pts,$stock,$is_featured,$status]);
                        $new_id = (int)$db->lastInsertId();
                        if ($image_filename) {
                            $db->prepare("UPDATE marketplace_products SET image_url=? WHERE id=?")->execute([$image_filename, $new_id]);
                        }
                        audit_log($user_id,'merchant','product_added','marketplace_products',$new_id,null,['name'=>$name]);
                        flash_set('main','Produk berjaya ditambah.','success');
                    } else {
                        $edit_id = (int)($_POST['product_id'] ?? 0);
                        $chk = $db->prepare("SELECT id FROM marketplace_products WHERE id=? AND merchant_id=?");
                        $chk->execute([$edit_id,$merchant_id]);
                        if (!$chk->fetch()) { flash_set('main','Produk tidak dijumpai.','error'); redirect(APP_URL.'/merchant/products'); }

                        $set = "category_id=?,name=?,description=?,price_points=?,stock_quantity=?,is_featured=?,status=?,updated_at=NOW()";
                        $params = [$cat_id,$name,$desc,$price_pts,$stock,$is_featured,$status];
                        if ($image_filename) { $set .= ",image_url=?"; $params[] = $image_filename; }
                        $params[] = $edit_id;
                        $db->prepare("UPDATE marketplace_products SET {$set} WHERE id=?")->execute($params);
                        audit_log($user_id,'merchant','product_updated','marketplace_products',$edit_id,null,['name'=>$name]);
                        flash_set('main','Produk berjaya dikemaskini.','success');
                    }
                    redirect(APP_URL.'/merchant/products');
                }
            }
        }
    } elseif ($action === 'delete') {
        $del_id = (int)($_POST['product_id'] ?? 0);
        $chk = $db->prepare("SELECT id FROM marketplace_products WHERE id=? AND merchant_id=?");
        $chk->execute([$del_id,$merchant_id]);
        if ($chk->fetch()) {
            $db->prepare("UPDATE marketplace_products SET deleted_at=NOW() WHERE id=?")->execute([$del_id]);
            audit_log($user_id,'merchant','product_deleted','marketplace_products',$del_id,null,null);
            flash_set('main','Produk dipadam.','success');
        }
        redirect(APP_URL.'/merchant/products');
    }
}

// ── Fetch data ────────────────────────────────────────────────────────────────
$q       = trim($_GET['q'] ?? '');
$page    = max(1,(int)($_GET['page'] ?? 1));
$per     = 15;

$where  = "mp.merchant_id = ? AND mp.deleted_at IS NULL";
$params = [$merchant_id];
if ($q) { $where .= " AND mp.name LIKE ?"; $params[] = "%{$q}%"; }

$total = (int)$db->prepare("SELECT COUNT(*) FROM marketplace_products mp WHERE {$where}")->execute($params) ? 0 : 0;
$ct = $db->prepare("SELECT COUNT(*) FROM marketplace_products mp WHERE {$where}"); $ct->execute($params); $total = (int)$ct->fetchColumn();

$pag    = paginate($total, $per, $page, APP_URL.'/merchant/products?q='.urlencode($q).'&page={page}');
$params_paged = array_merge($params, [$pag['offset'], $per]);
$prod_stmt = $db->prepare("SELECT mp.*,mc.name AS cat_name FROM marketplace_products mp LEFT JOIN marketplace_categories mc ON mc.id=mp.category_id WHERE {$where} ORDER BY mp.created_at DESC LIMIT ?,?");
$prod_stmt->execute($params_paged);
$products = $prod_stmt->fetchAll();

$cats = $db->query("SELECT id,name FROM marketplace_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();

// Edit mode
$edit_product = null;
if (isset($_GET['edit'])) {
    $ep = $db->prepare("SELECT * FROM marketplace_products WHERE id=? AND merchant_id=?");
    $ep->execute([(int)$_GET['edit'], $merchant_id]);
    $edit_product = $ep->fetch() ?: null;
}

layout_begin_merchant('Produk Saya');
?>
<div class="page-title">📦 Produk Saya</div>
<?= flash_html('main') ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?= implode('<br>',$errors) ?></div><?php endif; ?>

<!-- Add / Edit Form -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title"><?= $edit_product ? '✏️ Edit Produk' : '➕ Tambah Produk Baharu' ?></div>
  <?php if ($merchant['status'] !== 'active'): ?>
    <p style="color:#9CA3AF;font-size:0.875rem;">Akaun pedagang belum aktif. Tidak boleh tambah produk.</p>
  <?php else: ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $edit_product ? 'edit' : 'add' ?>">
    <?php if ($edit_product): ?><input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>"><?php endif; ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <label class="form-label">Nama Produk *</label>
        <input type="text" name="product_name" class="form-input" required value="<?= h($edit_product['name'] ?? '') ?>">
      </div>
      <div>
        <label class="form-label">Kategori</label>
        <select name="category_id" class="form-input">
          <option value="0">-- Pilih Kategori --</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($edit_product && $edit_product['category_id']==$c['id']) ? 'selected' : '' ?>><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Harga (Mata Emas) *</label>
        <input type="number" name="price_points" class="form-input" step="0.01" min="0.01" required value="<?= h($edit_product['price_points'] ?? '') ?>">
      </div>
      <div>
        <label class="form-label">Stok</label>
        <input type="number" name="stock_quantity" class="form-input" min="0" value="<?= h($edit_product['stock_quantity'] ?? '0') ?>">
      </div>
      <div>
        <label class="form-label">Status</label>
        <select name="status" class="form-input">
          <option value="active" <?= ($edit_product['status']??'active')==='active' ? 'selected' : '' ?>>Aktif</option>
          <option value="inactive" <?= ($edit_product['status']??'')==='inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
        </select>
      </div>
      <div>
        <label class="form-label">Gambar Produk (PNG/JPG/WebP, maks 3MB)</label>
        <input type="file" name="product_image" class="form-input" accept="image/jpeg,image/png,image/webp">
        <?php if ($edit_product && $edit_product['image_url']): ?>
          <img src="<?= APP_URL ?>/uploads/products/<?= h($edit_product['image_url']) ?>" style="height:50px;margin-top:6px;border-radius:6px;">
        <?php endif; ?>
      </div>
      <div style="grid-column:1/-1;">
        <label class="form-label">Penerangan</label>
        <textarea name="description" class="form-input" rows="3"><?= h($edit_product['description'] ?? '') ?></textarea>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="is_featured" id="is_featured" value="1" <?= ($edit_product['is_featured']??0) ? 'checked' : '' ?>>
        <label for="is_featured" class="form-label" style="margin:0;">Produk Pilihan (Featured)</label>
      </div>
    </div>
    <div style="margin-top:16px;display:flex;gap:8px;">
      <button type="submit" class="btn-gold"><?= $edit_product ? 'Simpan Perubahan' : 'Tambah Produk' ?></button>
      <?php if ($edit_product): ?><a href="<?= APP_URL ?>/merchant/products" class="btn-gold-outline">Batal</a><?php endif; ?>
    </div>
  </form>
  <?php endif; ?>
</div>

<!-- Product List -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
    <div class="section-title" style="margin:0;">📋 Senarai Produk (<?= $total ?>)</div>
    <form method="get" style="display:flex;gap:6px;">
      <input type="text" name="q" class="form-input" placeholder="Cari produk..." value="<?= h($q) ?>" style="min-width:180px;">
      <button type="submit" class="btn-gold-outline btn-sm">Cari</button>
    </form>
  </div>
  <?php if (empty($products)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:24px 0;font-size:0.875rem;">Tiada produk lagi.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#</th><th>Gambar</th><th>Nama</th><th>Kategori</th><th>Harga (pts)</th><th>Stok</th><th>Status</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;"><?= $p['id'] ?></td>
          <td>
            <?php if ($p['image_url']): ?>
              <img src="<?= APP_URL ?>/uploads/products/<?= h($p['image_url']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
            <?php else: ?><span style="font-size:1.4rem;">📦</span><?php endif; ?>
          </td>
          <td style="font-weight:600;font-size:0.875rem;"><?= h($p['name']) ?></td>
          <td style="font-size:0.8rem;color:#6B7280;"><?= h($p['cat_name'] ?? '-') ?></td>
          <td style="font-weight:600;color:var(--gold-dark);"><?= gold_format_points($p['price_points']) ?></td>
          <td style="font-size:0.85rem;"><?= (int)$p['stock_quantity'] ?></td>
          <td><?= status_badge($p['status']) ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="<?= APP_URL ?>/merchant/products?edit=<?= $p['id'] ?>" class="btn-gold-outline btn-sm">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Padam produk ini?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn-sm" style="background:#EF4444;color:#fff;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;">Padam</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>
<?php layout_end_merchant(); ?>
