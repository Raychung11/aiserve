<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/merchant_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/layout.php';

$db      = getDB();
$user_id = auth_id();
$user    = auth_user();

$m_stmt = $db->prepare("SELECT * FROM merchants WHERE user_id=?");
$m_stmt->execute([$user_id]);
$merchant = $m_stmt->fetch();
if (!$merchant) { flash_set('main','Data pedagang tidak dijumpai.','error'); redirect(APP_URL.'/login'); }

// Referral stats
$ref_url  = APP_URL . '/register?ref=' . ($user['referral_code'] ?? '');
$rates    = get_referral_settings();
$balance  = get_wallet_balance($user_id);

// Direct referrals
$page = max(1,(int)($_GET['page'] ?? 1));
$per  = 20;
$ct   = $db->prepare("SELECT COUNT(*) FROM referrals r JOIN users u ON u.id=r.referred_user_id WHERE r.referrer_user_id=?"); $ct->execute([$user_id]); $total_refs = (int)$ct->fetchColumn();
$pag  = paginate($total_refs, $per, $page, APP_URL.'/merchant/referrals?page={page}');
$ref_stmt = $db->prepare("SELECT r.*,u.full_name,u.email,u.created_at AS joined_at FROM referrals r JOIN users u ON u.id=r.referred_user_id WHERE r.referrer_user_id=? ORDER BY r.created_at DESC LIMIT ?,?");
$ref_stmt->execute([$user_id, $pag['offset'], $per]);
$referrals = $ref_stmt->fetchAll();

// Commission totals
$comm_stmt = $db->prepare("SELECT SUM(points_earned) AS total_pts, SUM(rm_value) AS total_rm, COUNT(*) AS total_count FROM referral_commissions WHERE beneficiary_user_id=? AND status='credited'");
$comm_stmt->execute([$user_id]);
$comm_totals = $comm_stmt->fetch();

// Recent commissions
$rc_stmt = $db->prepare("SELECT rc.*,u.full_name AS source_name FROM referral_commissions rc JOIN users u ON u.id=rc.source_user_id WHERE rc.beneficiary_user_id=? ORDER BY rc.created_at DESC LIMIT 10");
$rc_stmt->execute([$user_id]);
$recent_comms = $rc_stmt->fetchAll();

layout_begin_merchant('Program Rujukan');
?>
<div class="page-title">🤝 Program Rujukan</div>

<!-- Referral Link Card -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title">🔗 Pautan Rujukan Anda</div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" class="form-input" value="<?= h($ref_url) ?>" readonly style="flex:1;min-width:240px;background:#F9FAFB;">
    <button class="btn-gold" data-copy="<?= h($ref_url) ?>">Salin Pautan</button>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:10px 16px;font-size:0.85rem;">
      <strong>Kod Rujukan:</strong> <span style="font-family:monospace;font-size:1rem;color:var(--gold-dark);"><?= h($user['referral_code'] ?? '-') ?></span>
    </div>
  </div>
</div>

<!-- Commission Rates + Stats -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
  <div class="card-kasih">
    <div class="section-title">💎 Kadar Komisen</div>
    <?php foreach ([1,2,3] as $lvl): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #F3F4F6;">
      <div>
        <span style="font-weight:700;color:var(--gold-dark);">Level <?= $lvl ?></span>
        <span style="font-size:0.78rem;color:#9CA3AF;margin-left:6px;"><?= $lvl===1?'Rujukan Terus':($lvl===2?'2 Peringkat':'3 Peringkat') ?></span>
      </div>
      <div style="font-weight:700;font-size:1.1rem;color:var(--gold);"><?= $rates[$lvl] ?? '0' ?>%</div>
    </div>
    <?php endforeach; ?>
    <p style="font-size:0.75rem;color:#9CA3AF;margin-top:10px;">Komisen dikreditkan automatik apabila ahli rujukan membeli emas.</p>
  </div>
  <div class="card-kasih">
    <div class="section-title">📊 Statistik Komisen</div>
    <div class="stat-card" style="margin-bottom:10px;">
      <div class="stat-value"><?= (int)($comm_totals['total_count'] ?? 0) ?></div>
      <div class="stat-label">Jumlah Komisen Diterima</div>
    </div>
    <div class="stat-card stat-card-green">
      <div class="stat-value"><?= gold_format_points($comm_totals['total_pts'] ?? '0') ?></div>
      <div class="stat-label">Jumlah Mata Komisen</div>
    </div>
    <div style="margin-top:10px;font-size:0.85rem;color:#4B5563;">
      Nilai RM: <strong><?= gold_format_rm($comm_totals['total_rm'] ?? '0') ?></strong>
    </div>
  </div>
</div>

<!-- Recent Commissions -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title">💰 Komisen Terkini</div>
  <?php if (empty($recent_comms)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:16px 0;font-size:0.875rem;">Belum ada komisen.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Sumber</th><th>Level</th><th>Kadar</th><th>Mata</th><th>Nilai RM</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($recent_comms as $c): ?>
        <tr>
          <td style="font-size:0.85rem;"><?= h($c['source_name']) ?></td>
          <td><span style="background:#FEF3C7;color:#92400E;border-radius:4px;padding:2px 8px;font-size:0.75rem;font-weight:600;">L<?= $c['level'] ?></span></td>
          <td style="font-size:0.85rem;color:#6B7280;"><?= $c['rate_percent'] ?>%</td>
          <td style="font-weight:600;color:var(--gold-dark);">+<?= gold_format_points($c['points_earned']) ?></td>
          <td style="font-size:0.85rem;"><?= gold_format_rm($c['rm_value']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($c['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Direct Referrals List -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="section-title" style="margin:0;">👥 Ahli Rujukan Terus (<?= $total_refs ?>)</div>
  </div>
  <?php if (empty($referrals)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:16px 0;font-size:0.875rem;">Belum ada ahli yang mendaftar melalui anda.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#</th><th>Nama</th><th>E-mel</th><th>Tarikh Daftar</th></tr></thead>
      <tbody>
        <?php foreach ($referrals as $r): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;"><?= $r['id'] ?></td>
          <td style="font-weight:600;font-size:0.875rem;"><?= h($r['full_name']) ?></td>
          <td style="font-size:0.8rem;color:#6B7280;"><?= h($r['email']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($r['joined_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>
<?php layout_end_merchant(); ?>
