<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db = getDB(); $admin = auth_user();

// Manual credit action (admin confirm payment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pid    = (int)$_POST['purchase_id'];
    $action = sanitize_string($_POST['action'] ?? '');
    $stmt   = $db->prepare("SELECT * FROM gold_purchases WHERE id=? AND payment_status='pending'");
    $stmt->execute([$pid]); $p = $stmt->fetch();
    if ($p && $action === 'confirm_payment') {
        $db->prepare("UPDATE gold_purchases SET payment_status='paid',purchase_status='processing',paid_at=NOW() WHERE id=?")->execute([$pid]);
        $wid = ensure_wallet_exists((int)$p['user_id']);
        $desc = 'Pembelian Emas — RM ' . number_format((float)$p['rm_amount'],2) . ' (Admin sahkan)';
        $lid = ledger_credit($wid,(int)$p['user_id'],$p['points_credited'],$p['grams_credited'],$p['rm_amount'],$p['price_per_g_snapshot'],'buy_credit',$pid,$desc);
        $db->prepare("UPDATE gold_purchases SET purchase_status='credited',ledger_entry_id=? WHERE id=?")->execute([$lid,$pid]);
        process_referral_commissions((int)$p['user_id'],'gold_purchase',$pid,$p['points_credited'],$p['price_per_g_snapshot']);
        audit_log((int)$admin['id'],'super_admin','purchase_manual_confirmed','gold_purchases',$pid,null,['points'=>$p['points_credited']]);
        flash_set('main','Pembayaran disahkan dan Gold Points dikreditkan.','success');
    } elseif ($p && $action === 'cancel') {
        $db->prepare("UPDATE gold_purchases SET payment_status='cancelled',purchase_status='failed' WHERE id=?")->execute([$pid]);
        audit_log((int)$admin['id'],'super_admin','purchase_cancelled','gold_purchases',$pid,null,null);
        flash_set('main','Pembelian dibatalkan.','warning');
    }
    redirect(APP_URL . '/admin/purchases');
}

$status_f = sanitize_string($_GET['status'] ?? '');
$page = max(1,(int)($_GET['page']??1)); $per = 20;
$where = ['1=1']; $params = [];
if ($status_f) { $where[] = "gp.payment_status=?"; $params[] = $status_f; }
$ws = implode(' AND ', $where);
$cnt = $db->prepare("SELECT COUNT(*) FROM gold_purchases gp WHERE $ws"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag = paginate($total,$per,$page,APP_URL.'/admin/purchases?page={page}'.($status_f?'&status='.$status_f:''));

$stmt = $db->prepare("SELECT gp.*, u.full_name, u.email FROM gold_purchases gp JOIN users u ON u.id=gp.user_id WHERE $ws ORDER BY gp.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$per,$pag['offset']]));
$purchases = $stmt->fetchAll();

layout_begin_admin('Semua Pembelian');
?>
<div class="page-title">🛒 Semua Pembelian</div>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <a href="<?= APP_URL ?>/admin/purchases" class="<?= !$status_f?'btn-gold':'btn-gold-outline' ?> btn-sm">Semua</a>
  <a href="<?= APP_URL ?>/admin/purchases?status=pending" class="<?= $status_f==='pending'?'btn-gold':'btn-gold-outline' ?> btn-sm">⏳ Tertangguh</a>
  <a href="<?= APP_URL ?>/admin/purchases?status=paid" class="<?= $status_f==='paid'?'btn-gold':'btn-gold-outline' ?> btn-sm">✅ Dibayar</a>
  <a href="<?= APP_URL ?>/admin/purchases?status=cancelled" class="<?= $status_f==='cancelled'?'btn-gold':'btn-gold-outline' ?> btn-sm">❌ Dibatalkan</a>
</div>
<?= flash_html('main') ?>
<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#ID</th><th>Pengguna</th><th>RM</th><th>Mata</th><th>Harga/g</th><th>Pembayaran</th><th>Pembelian</th><th>Tarikh</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($purchases as $p): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $p['id'] ?></td>
          <td><div><?= h($p['full_name']) ?></div><div style="font-size:0.75rem;color:#9CA3AF;"><?= h($p['email']) ?></div></td>
          <td style="font-weight:700;">RM <?= number_format((float)$p['rm_amount'],2) ?></td>
          <td style="color:var(--gold-dark);"><?= gold_format_points($p['points_credited']) ?></td>
          <td style="font-size:0.82rem;">RM <?= number_format((float)$p['price_per_g_snapshot'],4) ?></td>
          <td><?= status_badge($p['payment_status']) ?></td>
          <td><?= status_badge($p['purchase_status']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($p['created_at']) ?></td>
          <td>
            <?php if ($p['payment_status'] === 'pending'): ?>
            <form method="POST" style="display:flex;gap:4px;">
              <?= csrf_field() ?>
              <input type="hidden" name="purchase_id" value="<?= $p['id'] ?>">
              <button name="action" value="confirm_payment" class="btn-gold btn-sm" style="padding:3px 8px;" onclick="return confirm('Sahkan pembayaran ini?')">✅ Sahkan</button>
              <button name="action" value="cancel" class="btn-danger btn-sm" style="padding:3px 8px;" onclick="return confirm('Batalkan pembelian ini?')">❌</button>
            </form>
            <?php else: echo '—'; endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
</div>
<?php layout_end_admin(); ?>
