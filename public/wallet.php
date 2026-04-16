<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_login(APP_URL . '/login');
if (auth_is_admin()) redirect(APP_URL . '/admin');

$user_id = auth_id();
$balance = get_wallet_balance($user_id);
$price   = get_active_gold_price();

$db  = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 15;
$type = sanitize_string($_GET['type'] ?? '');

$where = "wl.user_id = ?";
$params = [$user_id];
if ($type) { $where .= " AND wl.source_type = ?"; $params[] = $type; }

$total_stmt = $db->prepare("SELECT COUNT(*) FROM wallet_ledger wl WHERE {$where}");
$total_stmt->execute($params);
$total = (int)$total_stmt->fetchColumn();

$pag = paginate($total, $per, $page, APP_URL . '/wallet?page={page}' . ($type ? '&type=' . urlencode($type) : ''));

$stmt = $db->prepare("SELECT wl.* FROM wallet_ledger wl WHERE {$where} ORDER BY wl.created_at DESC LIMIT ? OFFSET ?");
$params[] = $per; $params[] = $pag['offset'];
$stmt->execute($params);
$ledger = $stmt->fetchAll();

$type_icons = [
    'buy_credit' => ['⬆️', 'Beli Emas', 'credit'],
    'transfer_in' => ['⬆️', 'Pindahan Masuk', 'credit'],
    'transfer_out' => ['⬇️', 'Pindahan Keluar', 'debit'],
    'referral_bonus' => ['⭐', 'Bonus Rujukan', 'credit'],
    'marketplace_spend' => ['⬇️', 'Pembelian Pasaran', 'debit'],
    'marketplace_receive' => ['⬆️', 'Jualan Pasaran', 'credit'],
    'admin_adjustment' => ['⚙️', 'Pelarasan Admin', 'credit'],
    'payout_debit' => ['⬇️', 'Pengeluaran', 'debit'],
    'refund_credit' => ['↩️', 'Bayaran Balik', 'credit'],
    'campaign_bonus' => ['🎯', 'Bonus Kempen', 'credit'],
];

layout_begin_user('Wallet Saya');
?>
<div class="page-title">💛 Wallet Saya</div>

<!-- Wallet Balance Card -->
<div class="card-wallet" style="margin-bottom:24px;max-width:600px;">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
    <div>
      <div class="wallet-balance-label">Baki Gold Points</div>
      <div class="wallet-balance-points"><?= gold_format_points($balance['points']) ?> <span style="font-size:1.1rem;font-weight:400;">pts</span></div>
      <div class="wallet-balance-grams">≈ <?= gold_format_grams($balance['grams']) ?></div>
      <div class="wallet-balance-rm"><?= gold_format_rm($balance['rm_value']) ?></div>
      <div class="wallet-price-note">
        <?= $price ? ('Harga: RM ' . number_format((float)$price['price_per_g'],2) . '/g') : 'Harga belum ditetapkan' ?>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <a href="<?= APP_URL ?>/buy-gold" class="btn-gold btn-sm">💛 Beli Emas</a>
      <a href="<?= APP_URL ?>/transfer" style="background:rgba(255,255,255,0.12);border:1.5px solid rgba(255,255,255,0.3);color:#fff;padding:6px 14px;border-radius:6px;font-size:0.8rem;font-weight:600;text-align:center;">↔️ Pindah</a>
    </div>
  </div>
</div>

<!-- Filter -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <a href="<?= APP_URL ?>/wallet" class="<?= !$type ? 'btn-gold' : 'btn-gold-outline' ?> btn-sm">Semua</a>
  <a href="<?= APP_URL ?>/wallet?type=buy_credit" class="<?= $type==='buy_credit' ? 'btn-gold' : 'btn-gold-outline' ?> btn-sm">💛 Beli</a>
  <a href="<?= APP_URL ?>/wallet?type=transfer_in" class="<?= $type==='transfer_in' ? 'btn-gold' : 'btn-gold-outline' ?> btn-sm">⬆️ Masuk</a>
  <a href="<?= APP_URL ?>/wallet?type=transfer_out" class="<?= $type==='transfer_out' ? 'btn-gold' : 'btn-gold-outline' ?> btn-sm">⬇️ Keluar</a>
  <a href="<?= APP_URL ?>/wallet?type=referral_bonus" class="<?= $type==='referral_bonus' ? 'btn-gold' : 'btn-gold-outline' ?> btn-sm">⭐ Rujukan</a>
</div>

<!-- Ledger -->
<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr>
        <th>Jenis</th><th>Keterangan</th><th>Mata</th><th>Gram</th><th>Nilai RM</th><th>Tarikh</th>
      </tr></thead>
      <tbody>
        <?php if (empty($ledger)): ?>
        <tr><td colspan="6" style="text-align:center;padding:30px;color:#9CA3AF;">Tiada transaksi.</td></tr>
        <?php else: ?>
        <?php foreach ($ledger as $row):
          [$icon, $label, $dir] = $type_icons[$row['source_type']] ?? ['•', $row['source_type'], $row['direction']];
        ?>
        <tr>
          <td><span style="white-space:nowrap;"><?= $icon ?> <?= h($label) ?></span></td>
          <td style="max-width:200px;font-size:0.82rem;"><?= h($row['description']) ?></td>
          <td class="txn-amount <?= $dir ?>" style="white-space:nowrap;">
            <?= $row['direction']==='credit' ? '+' : '−' ?><?= gold_format_points($row['points']) ?>
          </td>
          <td style="font-size:0.82rem;"><?= gold_format_grams($row['grams']) ?></td>
          <td style="font-size:0.82rem;"><?= gold_format_rm($row['rm_reference_value']) ?></td>
          <td style="font-size:0.78rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($row['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
</div>
<?php layout_end_user(); ?>
