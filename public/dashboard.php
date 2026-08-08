<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_login('/login');
if (auth_is_admin())    redirect(APP_URL . '/admin');
if (auth_is_merchant()) redirect(APP_URL . '/merchant');

$user    = auth_user();
$user_id = auth_id();
$db      = getDB();

// ── KYC status ────────────────────────────────────────────────────────────────
$kyc_stmt = $db->prepare("SELECT status, rejection_reason FROM kyc_submissions WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
$kyc_stmt->execute([$user_id]);
$kyc_info = $kyc_stmt->fetch();

// ── Portfolio data ────────────────────────────────────────────────────────────
$balance   = get_wallet_balance($user_id);
$price     = get_active_gold_price();
$sell_p    = get_active_sell_price();
$price_g   = $price ? (float)$price['price_per_g'] : 0;
$sell_g    = $sell_p ? (float)$sell_p['price_per_g'] : $price_g;

// Active deposits
$dep_stmt = $db->prepare("SELECT * FROM gold_deposits WHERE user_id=? AND status IN ('active','pawned') ORDER BY created_at DESC");
$dep_stmt->execute([$user_id]);
$my_deposits = $dep_stmt->fetchAll();
$total_deposit_grams = array_sum(array_column($my_deposits, 'gold_grams'));
$total_deposit_rm    = $total_deposit_grams * $sell_g;

// Interest earned (paid)
$int_stmt = $db->prepare("SELECT COALESCE(SUM(interest_grams),0) AS g, COALESCE(SUM(interest_rm),0) AS rm FROM gold_deposit_interest gdi JOIN gold_deposits gd ON gd.id=gdi.deposit_id WHERE gd.user_id=? AND gdi.payout_status='paid'");
$int_stmt->execute([$user_id]);
$int_earned = $int_stmt->fetch();

// Active Ar-Rahnu
$ar_stmt = $db->prepare("SELECT * FROM ar_rahnu_applications WHERE user_id=? AND status IN ('pending','approved','active') ORDER BY created_at DESC");
$ar_stmt->execute([$user_id]);
$my_arrahu = $ar_stmt->fetchAll();
$total_ar_financed = array_sum(array_column(array_filter($my_arrahu, fn($a)=>$a['status']==='active'), 'financing_approved'));

// Physical gold pending
$pg_stmt = $db->prepare("SELECT COUNT(*) FROM gold_physical_redemptions WHERE user_id=? AND status IN ('pending','approved','ready')");
$pg_stmt->execute([$user_id]);
$pg_pending = (int)$pg_stmt->fetchColumn();

// ── Total portfolio value ─────────────────────────────────────────────────────
$digital_rm   = (float)$balance['grams'] * $sell_g;
$portfolio_rm = $digital_rm + $total_deposit_rm;

// ── Recent transactions ────────────────────────────────────────────────────────
$txn_stmt = $db->prepare("SELECT * FROM wallet_ledger WHERE user_id=? ORDER BY created_at DESC LIMIT 6");
$txn_stmt->execute([$user_id]);
$recent_txns = $txn_stmt->fetchAll();

// ── My campaigns ──────────────────────────────────────────────────────────────
$camp_stmt = $db->prepare("SELECT uc.*, c.title, c.target_points FROM user_campaigns uc JOIN campaigns c ON c.id=uc.campaign_id WHERE uc.user_id=? AND c.is_active=1 ORDER BY uc.joined_at DESC LIMIT 3");
$camp_stmt->execute([$user_id]);
$my_campaigns = $camp_stmt->fetchAll();

// ── Featured products ─────────────────────────────────────────────────────────
$prod_stmt = $db->query("SELECT mp.*, mc.name AS cat_name FROM marketplace_products mp JOIN marketplace_categories mc ON mc.id=mp.category_id WHERE mp.status='active' AND mp.is_featured=1 ORDER BY mp.created_at DESC LIMIT 4");
$featured_products = $prod_stmt->fetchAll();

layout_begin_user('Dashboard');
?>
<style>
.portfolio-header { background:linear-gradient(135deg,#1a0e00,#2d1a00); border-radius:16px; padding:28px; margin-bottom:20px; }
.portfolio-total-label { font-size:.75rem;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.12em;margin-bottom:4px; }
.portfolio-total-val { font-size:2.4rem;font-weight:900;color:#fff;line-height:1; }
.portfolio-total-grams { font-size:.9rem;color:rgba(201,168,76,.8);margin-top:4px; }

.pf-card { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.1); border-radius:12px; padding:16px; }
.pf-card-lbl { font-size:.68rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px; }
.pf-card-val { font-size:1.15rem;font-weight:800;color:#fff; }
.pf-card-sub { font-size:.72rem;color:rgba(255,255,255,.45);margin-top:2px; }
.pf-card.highlight { background:rgba(201,168,76,.15);border-color:rgba(201,168,76,.3); }

.pos-card { border:1px solid #E5E7EB;border-radius:12px;padding:16px;transition:box-shadow .15s; }
.pos-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); }
.pos-type { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:3px 10px;border-radius:999px;display:inline-block;margin-bottom:8px; }
.pos-type.digital  { background:#FEF3C7;color:#92400E; }
.pos-type.deposit  { background:#D1FAE5;color:#065F46; }
.pos-type.arrahu   { background:#DBEAFE;color:#1E40AF; }
.pos-type.physical { background:#FFF7ED;color:#B45309; }

.qa-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:24px; }
.qa-btn { background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:14px 10px;text-align:center;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s; display:block; }
.qa-btn:hover { border-color:var(--gold-border);box-shadow:0 2px 10px rgba(180,120,0,.1); }
.qa-icon { font-size:1.7rem;margin-bottom:5px; }
.qa-lbl { font-size:.78rem;font-weight:700;color:var(--kasih-dark); }
.qa-sub { font-size:.68rem;color:#9CA3AF;margin-top:1px; }

.txn-row { display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #F3F4F6; }
.txn-row:last-child { border-bottom:none; }
.txn-dot { width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0; }
.txn-dot.credit { background:#D1FAE5; }
.txn-dot.debit  { background:#FEE2E2; }
</style>

<!-- ── KYC alerts ──────────────────────────────────────────────────────────── -->
<?php if (!$kyc_info): ?>
<div class="alert alert-warning" style="margin-bottom:16px;display:flex;align-items:center;gap:12px;">
  <span style="font-size:1.5rem;">🪪</span>
  <div style="flex:1;"><strong>Pengesahan Identiti (eKYC) Diperlukan</strong><br><span style="font-size:.85rem;">Sila selesaikan eKYC untuk mengakses semua ciri platform.</span></div>
  <a href="<?= APP_URL ?>/kyc" class="btn-gold btn-sm" style="white-space:nowrap;">Mula eKYC →</a>
</div>
<?php elseif ($kyc_info['status'] === 'pending'): ?>
<div class="alert" style="background:#FFFBEB;border-left:4px solid #F59E0B;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
  <span style="font-size:1.5rem;">⏳</span>
  <div><strong style="color:#92400E;">eKYC Sedang Disemak</strong><br><span style="font-size:.85rem;color:#6B7280;">Permohonan anda sedang disemak. Proses ini mengambil masa 1–3 hari bekerja.</span></div>
</div>
<?php elseif ($kyc_info['status'] === 'rejected'): ?>
<div class="alert alert-error" style="margin-bottom:16px;display:flex;align-items:center;gap:12px;">
  <span style="font-size:1.5rem;">❌</span>
  <div style="flex:1;"><strong>eKYC Ditolak</strong> — <?= h($kyc_info['rejection_reason'] ?: 'Sila semak dan hantar semula.') ?></div>
  <a href="<?= APP_URL ?>/kyc" class="btn-gold btn-sm" style="white-space:nowrap;">Hantar Semula →</a>
</div>
<?php endif; ?>

<!-- ══ PORTFOLIO HEADER ══════════════════════════════════════════════════════ -->
<div class="portfolio-header">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
    <div>
      <div class="portfolio-total-label">Jumlah Portfolio Emas</div>
      <div class="portfolio-total-val">RM <?= number_format($portfolio_rm, 2) ?></div>
      <div class="portfolio-total-grams">≈ <?= number_format((float)$balance['grams'] + $total_deposit_grams, 4) ?>g emas • Harga: RM <?= number_format($sell_g, 2) ?>/g</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/buy-gold" class="btn-gold btn-sm">💛 Beli Emas</a>
      <a href="<?= APP_URL ?>/gold-deposit" class="btn-gold-outline btn-sm" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);color:#fff;">🏦 Deposit</a>
    </div>
  </div>

  <!-- Portfolio breakdown -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-top:20px;">
    <div class="pf-card highlight">
      <div class="pf-card-lbl">💰 Emas Digital</div>
      <div class="pf-card-val"><?= gold_format_grams($balance['grams']) ?></div>
      <div class="pf-card-sub"><?= gold_format_points($balance['points']) ?> pts • RM <?= number_format($digital_rm, 2) ?></div>
    </div>
    <div class="pf-card <?= $total_deposit_grams > 0 ? 'highlight' : '' ?>">
      <div class="pf-card-lbl">🏦 Deposit Fizikal</div>
      <div class="pf-card-val"><?= number_format($total_deposit_grams, 4) ?>g</div>
      <div class="pf-card-sub">RM <?= number_format($total_deposit_rm, 2) ?> • <?= count($my_deposits) ?> deposit</div>
    </div>
    <div class="pf-card">
      <div class="pf-card-lbl">💰 Faedah Diterima</div>
      <div class="pf-card-val"><?= number_format((float)$int_earned['g'], 4) ?>g</div>
      <div class="pf-card-sub">≈ RM <?= number_format((float)$int_earned['rm'], 2) ?></div>
    </div>
    <?php if (!empty($my_arrahu)): ?>
    <div class="pf-card">
      <div class="pf-card-lbl">🕌 Ar Rahnu Aktif</div>
      <div class="pf-card-val"><?= count($my_arrahu) ?> permohonan</div>
      <div class="pf-card-sub">RM <?= number_format($total_ar_financed, 2) ?> dibiayai</div>
    </div>
    <?php endif; ?>
    <?php if ($pg_pending > 0): ?>
    <div class="pf-card">
      <div class="pf-card-lbl">🥇 Emas Fizikal</div>
      <div class="pf-card-val"><?= $pg_pending ?> pesanan</div>
      <div class="pf-card-sub">Menunggu kutip</div>
    </div>
    <?php endif; ?>
    <div class="pf-card">
      <div class="pf-card-lbl">📢 Kod Rujukan</div>
      <div class="pf-card-val" style="font-size:.85rem;font-family:monospace;letter-spacing:.08em;"><?= h($user['referral_code'] ?? '-') ?></div>
      <div class="pf-card-sub" style="cursor:pointer;" onclick="navigator.clipboard.writeText('<?= APP_URL.'/register?ref='.($user['referral_code']??'') ?>');this.textContent='✅ Disalin!'">📋 Salin pautan rujukan</div>
    </div>
  </div>
</div>

<!-- ══ POSITIONS ════════════════════════════════════════════════════════════ -->
<?php if (!empty($my_deposits) || !empty($my_arrahu)): ?>
<div style="margin-bottom:20px;">
  <div class="section-title" style="margin-bottom:12px;">📊 Kedudukan Semasa</div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;">

    <!-- Digital wallet position -->
    <div class="pos-card">
      <span class="pos-type digital">💰 Emas Digital</span>
      <div style="font-size:1.3rem;font-weight:800;color:var(--kasih-dark);"><?= gold_format_grams($balance['grams']) ?></div>
      <div style="font-size:.82rem;color:#6B7280;margin:4px 0;"><?= gold_format_points($balance['points']) ?> pts • RM <?= number_format($digital_rm, 2) ?></div>
      <div style="display:flex;gap:6px;margin-top:10px;">
        <a href="<?= APP_URL ?>/buy-gold" class="btn-gold btn-sm" style="font-size:.75rem;">Beli</a>
        <a href="<?= APP_URL ?>/sell-gold" class="btn-gold-outline btn-sm" style="font-size:.75rem;">Jual</a>
        <a href="<?= APP_URL ?>/wallet" class="btn-gold-outline btn-sm" style="font-size:.75rem;">Sejarah</a>
      </div>
    </div>

    <!-- Deposit positions -->
    <?php foreach ($my_deposits as $dep):
      $dep_rm = (float)$dep['gold_grams'] * $sell_g;
      $monthly_earn = (float)$dep['gold_grams'] * ((float)$dep['interest_rate_snapshot'] / 100);
    ?>
    <div class="pos-card">
      <span class="pos-type deposit">🏦 Deposit <?= h($dep['gold_purity']) ?></span>
      <div style="font-size:.72rem;font-family:monospace;color:#9CA3AF;margin-bottom:4px;"><?= h($dep['deposit_ref']) ?></div>
      <div style="font-size:1.3rem;font-weight:800;color:var(--kasih-dark);"><?= gold_format_grams($dep['gold_grams']) ?></div>
      <div style="font-size:.82rem;color:#6B7280;margin:4px 0;">RM <?= number_format($dep_rm, 2) ?> • <?= $dep['interest_rate_snapshot'] ?>%/bln</div>
      <div style="font-size:.78rem;color:#065F46;font-weight:600;margin-bottom:10px;">Faedah bulanan: ~<?= number_format($monthly_earn, 4) ?>g</div>
      <?php if ($dep['status'] === 'active'): ?>
      <a href="<?= APP_URL ?>/ar-rahnu?deposit_id=<?= $dep['id'] ?>" class="btn-gold-outline btn-sm" style="font-size:.75rem;">🕌 Gadai</a>
      <?php else: ?>
      <span style="font-size:.72rem;background:#DBEAFE;color:#1E40AF;padding:3px 10px;border-radius:999px;font-weight:600;">🔒 Digadai</span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Ar Rahnu positions -->
    <?php foreach ($my_arrahu as $ar): ?>
    <div class="pos-card">
      <span class="pos-type arrahu">🕌 Ar Rahnu #<?= $ar['id'] ?></span>
      <div style="font-size:1rem;font-weight:700;color:var(--kasih-dark);"><?= gold_format_grams($ar['gold_grams']) ?> digadai</div>
      <div style="font-size:.82rem;color:#6B7280;margin:4px 0;">
        <?php if ($ar['status']==='active' && $ar['financing_approved']): ?>
          Biayai: <strong>RM <?= number_format((float)$ar['financing_approved'],2) ?></strong>
          <?php if ($ar['maturity_date']): ?> • Tamat: <?= date('d M Y', strtotime($ar['maturity_date'])) ?><?php endif; ?>
        <?php elseif ($ar['status']==='pending'): ?>
          <span style="color:#92400E;">⏳ Menunggu kelulusan</span>
        <?php elseif ($ar['status']==='approved'): ?>
          <span style="color:#065F46;">✅ Diluluskan — menunggu disbursement</span>
        <?php endif; ?>
      </div>
      <?php if ($ar['monthly_ujrah_rate']): ?><div style="font-size:.75rem;color:#1E40AF;">Ujrah: <?= $ar['monthly_ujrah_rate'] ?>%/bln</div><?php endif; ?>
    </div>
    <?php endforeach; ?>

  </div>
</div>
<?php endif; ?>

<!-- ══ QUICK ACTIONS ═════════════════════════════════════════════════════════ -->
<div class="qa-grid">
  <?php foreach ([
    ['💛','Beli Emas',     '/buy-gold',      'Tambah pts'],
    ['💵','Jual Emas',     '/sell-gold',     'Tukar ke RM'],
    ['🏦','Deposit Emas',  '/gold-deposit',  'Simpan fizikal'],
    ['🕌','Ar Rahnu',      '/ar-rahnu',      'Gadai emas'],
    ['🥇','Emas Fizikal',  '/physical-gold', 'Plat 0.2g'],
    ['↔️','Pindah',        '/transfer',      'Hantar pts'],
    ['🛍️','Pasaran',       '/marketplace',   'Tebus produk'],
    ['🎯','Kempen',        '/campaigns',     'Simpan bersasar'],
    ['📢','Rujukan',       '/referrals',     'Jana komisen'],
    ['🪪','eKYC',          '/kyc',           'Sahkan IC'],
  ] as [$icon,$lbl,$path,$sub]): ?>
  <a href="<?= APP_URL . $path ?>" class="qa-btn">
    <div class="qa-icon"><?= $icon ?></div>
    <div class="qa-lbl"><?= $lbl ?></div>
    <div class="qa-sub"><?= $sub ?></div>
  </a>
  <?php endforeach; ?>
</div>

<!-- ══ BOTTOM GRID ═══════════════════════════════════════════════════════════ -->
<div class="dash-two-col">

  <!-- Recent transactions -->
  <div class="card-kasih">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <div class="section-title" style="margin:0;">📊 Transaksi Terkini</div>
      <a href="<?= APP_URL ?>/wallet" class="btn-gold-outline btn-sm">Semua →</a>
    </div>
    <?php if (empty($recent_txns)): ?>
      <p style="color:#9CA3AF;font-size:.875rem;text-align:center;padding:20px 0;">Tiada transaksi lagi.</p>
    <?php else: ?>
      <?php foreach ($recent_txns as $txn): ?>
      <div class="txn-row">
        <div class="txn-dot <?= $txn['direction'] ?>"><?= $txn['direction']==='credit'?'⬆️':'⬇️' ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.83rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($txn['description']) ?></div>
          <div style="font-size:.72rem;color:#9CA3AF;"><?= format_date($txn['created_at']) ?></div>
        </div>
        <div style="font-size:.83rem;font-weight:700;white-space:nowrap;color:<?= $txn['direction']==='credit'?'#065F46':'#991B1B' ?>;">
          <?= $txn['direction']==='credit'?'+':'-' ?><?= gold_format_points($txn['points']) ?> pts
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Campaigns -->
  <div class="card-kasih">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <div class="section-title" style="margin:0;">🎯 Kempen Saya</div>
      <a href="<?= APP_URL ?>/campaigns" class="btn-gold-outline btn-sm">Semua →</a>
    </div>
    <?php if (empty($my_campaigns)): ?>
    <div style="text-align:center;padding:16px 0;">
      <p style="color:#9CA3AF;font-size:.875rem;margin-bottom:12px;">Anda belum menyertai mana-mana kempen.</p>
      <a href="<?= APP_URL ?>/campaigns" class="btn-gold btn-sm">Lihat Kempen</a>
    </div>
    <?php else: ?>
      <?php foreach ($my_campaigns as $uc):
        $progress = $uc['target_points'] > 0 ? min(100, round(($uc['current_points'] / $uc['target_points']) * 100, 1)) : 0;
      ?>
      <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span style="font-size:.85rem;font-weight:600;"><?= h($uc['title']) ?></span>
          <span style="font-size:.75rem;color:var(--gold-dark);font-weight:700;"><?= $progress ?>%</span>
        </div>
        <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $progress ?>%;"></div></div>
        <div style="font-size:.72rem;color:#9CA3AF;margin-top:3px;"><?= gold_format_points($uc['current_points']) ?> / <?= gold_format_points($uc['target_points']) ?> pts</div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<!-- Featured Products -->
<?php if (!empty($featured_products)): ?>
<div class="card-kasih" style="margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
    <div class="section-title" style="margin:0;">🛍️ Produk Pilihan</div>
    <a href="<?= APP_URL ?>/marketplace" class="btn-gold-outline btn-sm">Lihat Semua</a>
  </div>
  <div class="marketplace-grid">
    <?php foreach ($featured_products as $prod): ?>
    <a href="<?= APP_URL ?>/product?id=<?= $prod['id'] ?>" class="product-card" style="position:relative;">
      <div class="product-img-placeholder"><?= $prod['image'] ? '' : '🏷️' ?></div>
      <span class="featured-badge">✦ Pilihan</span>
      <div class="product-body">
        <div class="product-cat"><?= h($prod['cat_name']) ?></div>
        <div class="product-title"><?= h($prod['title']) ?></div>
        <div class="product-price"><?= gold_format_points($prod['points_price']) ?> pts</div>
        <div class="product-rm">≈ <?= gold_format_rm($prod['rm_reference_value']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php layout_end_user(); ?>
