<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();

$db      = getDB();
$prod_id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT mp.*, mc.name AS cat_name, m.company_name, m.id AS merchant_id_real FROM marketplace_products mp JOIN marketplace_categories mc ON mc.id=mp.category_id JOIN merchants m ON m.id=mp.merchant_id WHERE mp.id=? AND mp.status='active' AND mp.deleted_at IS NULL");
$stmt->execute([$prod_id]);
$product = $stmt->fetch();

if (!$product) {
    flash_set('main','Produk tidak dijumpai.','error');
    redirect(APP_URL . '/marketplace');
}

$errors = [];
$user_id = auth_check() ? auth_id() : 0;
$balance = $user_id ? get_wallet_balance($user_id) : null;
$price   = get_active_gold_price();

// Handle purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && auth_check()) {
    csrf_verify();
    if (!auth_is_admin() && !auth_is_merchant()) {
        $qty     = max(1, (int)($_POST['qty'] ?? 1));
        $total_pts = bcmul((string)$product['points_price'], (string)$qty, GOLD_POINT_DECIMALS);
        $total_rm  = gold_rm_from_points($total_pts, $price ? (string)$price['price_per_g'] : '390');

        if ((float)$total_pts > (float)($balance['points'] ?? '0')) {
            $errors['buy'] = 'Baki mata tidak mencukupi. Baki anda: ' . gold_format_points($balance['points'] ?? '0') . ' pts.';
        } elseif ($product['stock_qty'] !== null && $product['stock_qty'] < $qty) {
            $errors['buy'] = 'Stok tidak mencukupi.';
        }

        if (empty($errors)) {
            $db->beginTransaction();
            try {
                $price_snap = $price ? (string)$price['price_per_g'] : '390.0000';
                // Create order
                $db->prepare("INSERT INTO marketplace_orders (buyer_user_id,merchant_id,status,total_points,total_rm_value,created_at,updated_at) VALUES (?,?,'pending',?,?,NOW(),NOW())")
                   ->execute([$user_id, $product['merchant_id'], $total_pts, $total_rm]);
                $order_id = (int)$db->lastInsertId();

                // Order item
                $db->prepare("INSERT INTO marketplace_order_items (order_id,product_id,qty,points_price,rm_reference_value,product_title,created_at) VALUES (?,?,?,?,?,?,NOW())")
                   ->execute([$order_id, $product['id'], $qty, $product['points_price'], $product['rm_reference_value'], $product['title']]);

                // Debit buyer
                $buyer_wallet = get_wallet($user_id);
                if (!$buyer_wallet) $buyer_wid = ensure_wallet_exists($user_id);
                else $buyer_wid = (int)$buyer_wallet['id'];

                $grams = gold_grams_from_points($total_pts);
                $bid = ledger_debit($buyer_wid, $user_id, $total_pts, $grams, $total_rm, $price_snap, 'marketplace_spend', $order_id, 'Pembelian: ' . $product['title']);

                // Credit merchant
                $merch_user_stmt = $db->prepare("SELECT user_id FROM merchants WHERE id=?");
                $merch_user_stmt->execute([$product['merchant_id']]);
                $merch_user = $merch_user_stmt->fetch();
                $merch_wid = ensure_wallet_exists((int)($merch_user['user_id'] ?? 0));
                $mid = ledger_credit($merch_wid, (int)($merch_user['user_id'] ?? 0), $total_pts, $grams, $total_rm, $price_snap, 'marketplace_receive', $order_id, 'Jualan: ' . $product['title']);

                $db->prepare("UPDATE marketplace_orders SET status='paid_by_points',buyer_ledger_id=?,merchant_ledger_id=?,updated_at=NOW() WHERE id=?")->execute([$bid,$mid,$order_id]);
                // Reduce stock if tracked
                if ($product['stock_qty'] !== null) {
                    $db->prepare("UPDATE marketplace_products SET stock_qty=stock_qty-? WHERE id=?")->execute([$qty,$product['id']]);
                }
                $db->commit();
                audit_log($user_id,'user','marketplace_purchase','marketplace_orders',$order_id,null,['product'=>$product['title'],'pts'=>$total_pts]);
                flash_set('main','Pembelian berjaya! Pesanan #' . $order_id . ' sedang diproses oleh pedagang.','success');
                redirect(APP_URL . '/wallet');
            } catch(\Throwable $e) {
                $db->rollBack();
                $errors['buy'] = 'Pembelian gagal: ' . $e->getMessage();
            }
        }
    }
}

layout_head(h($product['title']));
layout_header(auth_user() ?: null);
echo '<main style="max-width:900px;margin:0 auto;padding:24px 16px;">';
layout_flash();
?>
<div style="margin-bottom:16px;font-size:0.85rem;color:#9CA3AF;">
  <a href="<?= APP_URL ?>/marketplace">Pasaran Maya</a> › 
  <a href="<?= APP_URL ?>/marketplace?cat=<?= $product['category_id'] ?>"><?= h($product['cat_name']) ?></a> › 
  <?= h($product['title']) ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;" class="md:grid-cols-2 grid-cols-1">
  <!-- Product image -->
  <div class="card-kasih" style="padding:0;overflow:hidden;">
    <div class="product-img-placeholder" style="height:320px;font-size:5rem;">🏷️</div>
    <?php if ($product['is_featured']): ?><div style="padding:8px 16px;background:#FEFCE8;font-size:0.8rem;color:var(--gold-dark);font-weight:600;">✦ Produk Pilihan</div><?php endif; ?>
  </div>

  <!-- Product info -->
  <div>
    <div class="card-kasih card-gold">
      <div style="font-size:0.78rem;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;"><?= h($product['cat_name']) ?></div>
      <h1 style="font-size:1.4rem;font-weight:800;color:var(--kasih-dark);margin-bottom:4px;"><?= h($product['title']) ?></h1>
      <div style="font-size:0.85rem;color:#6B7280;margin-bottom:16px;">oleh <?= h($product['company_name']) ?></div>

      <div style="background:var(--kasih-bg);border-radius:8px;padding:16px;margin-bottom:16px;">
        <div style="font-size:0.75rem;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;">Harga</div>
        <div style="font-size:2rem;font-weight:900;color:var(--gold-dark);"><?= gold_format_points($product['points_price']) ?> <span style="font-size:1rem;font-weight:400;">pts</span></div>
        <div style="font-size:0.85rem;color:#6B7280;">≈ <?= gold_format_rm($product['rm_reference_value']) ?></div>
      </div>

      <?php if ($product['stock_qty'] !== null): ?>
      <div style="font-size:0.82rem;color:#6B7280;margin-bottom:12px;">Stok tersedia: <strong><?= $product['stock_qty'] ?></strong></div>
      <?php endif; ?>

      <?php if (!empty($errors['buy'])): ?>
        <div class="alert alert-error"><?= h($errors['buy']) ?></div>
      <?php endif; ?>

      <?php if (auth_check() && !auth_is_admin() && !auth_is_merchant()): ?>
        <?php if ($balance): ?>
        <div style="font-size:0.82rem;color:#6B7280;margin-bottom:12px;">Baki anda: <strong style="color:var(--gold-dark);"><?= gold_format_points($balance['points']) ?> pts</strong></div>
        <?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="qty" value="1">
          <button type="submit" class="btn-gold btn-block btn-lg">🛍️ Beli dengan Gold Points</button>
        </form>
      <?php elseif (!auth_check()): ?>
        <a href="<?= APP_URL ?>/login" class="btn-gold btn-block btn-lg">Log Masuk untuk Membeli</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($product['description']): ?>
<div class="card-kasih" style="margin-top:20px;">
  <div class="section-title">Keterangan Produk</div>
  <div style="font-size:0.9rem;line-height:1.8;color:#374151;"><?= nl2br(h($product['description'])) ?></div>
</div>
<?php endif; ?>

</main>
<?php layout_footer(); ?>
