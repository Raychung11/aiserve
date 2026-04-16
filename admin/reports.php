<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$from  = sanitize_string($_GET['from'] ?? date('Y-m-01'));
$to    = sanitize_string($_GET['to']   ?? date('Y-m-d'));

// Summary stats
$q = function(string $sql, array $p=[]) use($db) { $s=$db->prepare($sql); $s->execute($p); return $s->fetchColumn(); };

$stats = [
    'total_rm'        => $q("SELECT COALESCE(SUM(rm_amount),0) FROM gold_purchases WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ?"         ,[$from,$to]),
    'total_pts'       => $q("SELECT COALESCE(SUM(points_credited),0) FROM gold_purchases WHERE purchase_status='credited' AND DATE(created_at) BETWEEN ? AND ?",[$from,$to]),
    'new_users'       => $q("SELECT COUNT(*) FROM users WHERE role='user' AND DATE(created_at) BETWEEN ? AND ?"                                               ,[$from,$to]),
    'transfers_pts'   => $q("SELECT COALESCE(SUM(points),0) FROM wallet_transfers WHERE transfer_status='completed' AND DATE(created_at) BETWEEN ? AND ?"     ,[$from,$to]),
    'marketplace_gm'  => $q("SELECT COALESCE(SUM(total_points),0) FROM marketplace_orders WHERE status NOT IN ('cancelled') AND DATE(created_at) BETWEEN ? AND ?"  ,[$from,$to]),
    'referral_rm'     => $q("SELECT COALESCE(SUM(rm_value),0) FROM referral_commissions WHERE status='credited' AND DATE(created_at) BETWEEN ? AND ?"         ,[$from,$to]),
];

// Daily purchases for simple chart data
$daily = $db->prepare("SELECT DATE(created_at) AS d, SUM(rm_amount) AS rm, COUNT(*) AS cnt FROM gold_purchases WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY d");
$daily->execute([$from,$to]);
$daily_data = $daily->fetchAll();

$export = isset($_GET['export']);
if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="kasih_gold_report_'.date('Ymd').'.csv"');
    echo "Tarikh,Jumlah Pembelian RM,Bil Transaksi\n";
    foreach ($daily_data as $r) echo h($r['d']) . ',' . $r['rm'] . ',' . $r['cnt'] . "\n";
    exit;
}

layout_begin_admin('Laporan');
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
  <div class="page-title" style="margin:0;">📈 Laporan Platform</div>
  <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <input type="date" name="from" value="<?= h($from) ?>" class="input-kasih" style="width:150px;">
    <span style="color:#6B7280;">hingga</span>
    <input type="date" name="to"   value="<?= h($to)   ?>" class="input-kasih" style="width:150px;">
    <button type="submit" class="btn-gold btn-sm">Tapis</button>
    <a href="?from=<?= h($from) ?>&to=<?= h($to) ?>&export=1" class="btn-gold-outline btn-sm">⬇️ CSV</a>
  </form>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px;">
  <div class="stat-card"><div class="stat-value">RM <?= number_format((float)$stats['total_rm'],0) ?></div><div class="stat-label">Jumlah Pembelian RM</div></div>
  <div class="stat-card stat-card-blue"><div class="stat-value"><?= gold_format_points($stats['total_pts']) ?></div><div class="stat-label">Mata Dikeluarkan</div></div>
  <div class="stat-card stat-card-green"><div class="stat-value"><?= number_format((int)$stats['new_users']) ?></div><div class="stat-label">Pengguna Baharu</div></div>
  <div class="stat-card stat-card-purple"><div class="stat-value"><?= gold_format_points($stats['transfers_pts']) ?></div><div class="stat-label">Pindahan (pts)</div></div>
  <div class="stat-card"><div class="stat-value"><?= gold_format_points($stats['marketplace_gm']) ?></div><div class="stat-label">GMV Pasaran (pts)</div></div>
  <div class="stat-card stat-card-red"><div class="stat-value">RM <?= number_format((float)$stats['referral_rm'],2) ?></div><div class="stat-label">Komisen Rujukan</div></div>
</div>

<!-- Daily table -->
<div class="card-kasih">
  <div class="section-title">📊 Pembelian Harian</div>
  <?php if (empty($daily_data)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:24px;">Tiada data untuk tempoh ini.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Tarikh</th><th>Jumlah RM</th><th>Bil Transaksi</th></tr></thead>
      <tbody>
        <?php foreach ($daily_data as $r): ?>
        <tr>
          <td><?= format_date($r['d'],'d M Y') ?></td>
          <td style="font-weight:700;color:var(--gold-dark);">RM <?= number_format((float)$r['rm'],2) ?></td>
          <td><?= $r['cnt'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php layout_end_admin(); ?>
