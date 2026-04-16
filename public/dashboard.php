<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_login(APP_URL . '/login');

// Redirect admins/merchants to their dashboards
if (auth_is_admin())    redirect(APP_URL . '/admin');
if (auth_is_merchant()) redirect(APP_URL . '/merchant');

$user    = auth_user();
$user_id = auth_id();
$db      = getDB();

// Wallet balance
$balance = get_wallet_balance($user_id);
$price   = get_active_gold_price();

// Recent transactions (last 5)
$txn_stmt = $db->prepare("
    SELECT wl.*, u.full_name FROM wallet_ledger wl
    LEFT JOIN users u ON u.id = wl.user_id
    WHERE wl.user_id = ?
    ORDER BY wl.created_at DESC LIMIT 5
");
$txn_stmt->execute([$user_id]);
$recent_txns = $txn_stmt->fetchAll();

// Active campaigns user has joined
$campaign_stmt = $db->prepare("
    SELECT uc.*, c.title, c.target_points, c.type, c.monthly_suggested_contribution
    FROM user_campaigns uc
    JOIN campaigns c ON c.id = uc.campaign_id
    WHERE uc.user_id = ? AND c.is_active = 1
    ORDER BY uc.joined_at DESC LIMIT 3
");
$campaign_stmt->execute([$user_id]);
$my_campaigns = $campaign_stmt->fetchAll();

// Featured marketplace products
$prod_stmt = $db->query("
    SELECT mp.*, mc.name AS cat_name, m.company_name
    FROM marketplace_products mp
    JOIN marketplace_categories mc ON mc.id = mp.category_id
    JOIN merchants m ON m.id = mp.merchant_id
    WHERE mp.status = 'active' AND mp.is_featured = 1
    ORDER BY mp.created_at DESC LIMIT 4
");
$featured_products = $prod_stmt->fetchAll();

layout_begin_user('Dashboard');
?>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;" class="md:grid-cols-3 grid-cols-1">
  <!-- Wallet Card -->
  <div class="card-wallet" style="grid-column:1/3;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
      <div>
        <div class="wallet-balance-label">Baki Gold Points Anda</div>
        <div class="wallet-balance-points"><?= gold_format_points($balance['points']) ?> <span style="font-size:1.2rem;">pts</span></div>
        <div class="wallet-balance-grams">≈ <?= gold_format_grams($balance['grams']) ?></div>
        <div class="wallet-balance-rm"><?= gold_format_rm($balance['rm_value']) ?></div>
        <div class="wallet-price-note">
          <?php if ($price): ?>
            Berdasarkan harga emas: RM <?= number_format((float)$price['price_per_g'], 2) ?>/g
            (<?= format_date($price['effective_at'], 'd M Y') ?>)
          <?php else: ?>
            Harga emas belum ditetapkan oleh admin.
          <?php endif; ?>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <a href="<?= APP_URL ?>/buy-gold" class="btn-gold btn-sm">💛 Beli Emas</a>
        <a href="<?= APP_URL ?>/transfer" class="btn-gold-outline btn-sm" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.3);color:#fff;">↔️ Pindah</a>
      </div>
    </div>
  </div>

  <!-- Quick stats -->
  <div class="card-kasih" style="display:flex;flex-direction:column;justify-content:space-between;">
    <div class="stat-label">Kod Rujukan Anda</div>
    <div style="font-size:1.4rem;font-weight:800;color:var(--gold-dark);letter-spacing:0.1em;"><?= h($user['referral_code'] ?? '-') ?></div>
    <div style="margin-top:8px;display:flex;gap:8px;">
      <button class="btn-gold-outline btn-sm" data-copy="<?= h(APP_URL . '/register?ref=' . ($user['referral_code'] ?? '')) ?>">📋 Salin</button>
      <a href="<?= APP_URL ?>/referrals" class="btn-gold btn-sm">Lihat Rujukan</a>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:24px;">
  <?php
  $actions = [
    ['💛', 'Beli Emas', '/buy-gold', 'Tambah Gold Points'],
    ['↔️', 'Pindah', '/transfer', 'Hantar kepada rakan'],
    ['🛍️', 'Pasaran', '/marketplace', 'Tebus produk & servis'],
    ['🎯', 'Kempen', '/campaigns', 'Simpanan bersasar'],
    ['📊', 'Wallet', '/wallet', 'Sejarah transaksi'],
    ['📢', 'Rujukan', '/referrals', 'Jana komisen'],
  ];
  foreach ($actions as [$icon, $label, $path, $desc]):
  ?>
  <a href="<?= APP_URL . $path ?>" class="card-kasih" style="text-align:center;text-decoration:none;color:inherit;transition:transform 0.2s;">
    <div style="font-size:1.8rem;margin-bottom:6px;"><?= $icon ?></div>
    <div style="font-weight:700;font-size:0.875rem;color:var(--kasih-dark);"><?= $label ?></div>
    <div style="font-size:0.72rem;color:#9CA3AF;margin-top:2px;"><?= $desc ?></div>
  </a>
  <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;" class="lg:grid-cols-2 grid-cols-1">
  <!-- Recent Transactions -->
  <div class="card-kasih">
    <div class="section-title">📊 Transaksi Terkini</div>
    <?php if (empty($recent_txns)): ?>
      <p style="color:#9CA3AF;font-size:0.875rem;text-align:center;padding:20px 0;">Tiada transaksi lagi.</p>
    <?php else: ?>
      <?php foreach ($recent_txns as $txn): ?>
        <div class="txn-item">
          <div class="txn-icon <?= $txn['direction'] ?>">
            <?= $txn['direction'] === 'credit' ? '⬆️' : '⬇️' ?>
          </div>
          <div style="flex:1;">
            <div class="txn-desc"><?= h($txn['description']) ?></div>
            <div class="txn-date"><?= format_date($txn['created_at']) ?></div>
          </div>
          <div class="txn-amount <?= $txn['direction'] ?>">
            <?= $txn['direction'] === 'credit' ? '+' : '-' ?><?= gold_format_points($txn['points']) ?> pts
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <div style="text-align:center;margin-top:12px;">
      <a href="<?= APP_URL ?>/wallet" class="btn-gold-outline btn-sm">Lihat Semua →</a>
    </div>
  </div>

  <!-- My Campaigns -->
  <div class="card-kasih">
    <div class="section-title">🎯 Kempen Saya</div>
    <?php if (empty($my_campaigns)): ?>
      <div style="text-align:center;padding:16px 0;">
        <p style="color:#9CA3AF;font-size:0.875rem;margin-bottom:12px;">Anda belum menyertai mana-mana kempen.</p>
        <a href="<?= APP_URL ?>/campaigns" class="btn-gold btn-sm">Lihat Kempen</a>
      </div>
    <?php else: ?>
      <?php foreach ($my_campaigns as $uc): 
        $progress = $uc['target_points'] > 0 ? min(100, round(($uc['current_points'] / $uc['target_points']) * 100, 1)) : 0;
      ?>
      <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:4px;">
          <span style="font-size:0.85rem;font-weight:600;"><?= h($uc['title']) ?></span>
          <span style="font-size:0.75rem;color:var(--gold-dark);"><?= $progress ?>%</span>
        </div>
        <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $progress ?>%;"></div></div>
        <div style="font-size:0.72rem;color:#9CA3AF;margin-top:3px;">
          <?= gold_format_points($uc['current_points']) ?> / <?= gold_format_points($uc['target_points']) ?> pts
        </div>
      </div>
      <?php endforeach; ?>
      <a href="<?= APP_URL ?>/campaigns" class="btn-gold-outline btn-sm">Semua Kempen →</a>
    <?php endif; ?>
  </div>
</div>

<!-- Featured Products -->
<?php if (!empty($featured_products)): ?>
<div class="card-kasih" style="margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div class="section-title" style="margin-bottom:0;">🛍️ Produk Pilihan</div>
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
