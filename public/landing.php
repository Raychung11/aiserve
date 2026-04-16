<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
if (auth_check()) {
    $role = auth_role();
    if ($role === 'super_admin') redirect(APP_URL . '/admin');
    elseif ($role === 'merchant') redirect(APP_URL . '/merchant');
    else redirect(APP_URL . '/dashboard');
}

$price    = get_active_gold_price();
$db       = getDB();
$campaigns = $db->query("SELECT * FROM campaigns WHERE is_active=1 ORDER BY created_at DESC LIMIT 3")->fetchAll();
$featured = $db->query("SELECT mp.*, mc.name AS cat_name FROM marketplace_products mp JOIN marketplace_categories mc ON mc.id=mp.category_id WHERE mp.status='active' AND mp.is_featured=1 ORDER BY mp.created_at DESC LIMIT 4")->fetchAll();

layout_head('Emas Mudah, Kaya Mudah');
layout_header(null);
?>

<!-- Hero -->
<section class="hero-kasih">
  <div style="position:relative;z-index:1;">
    <div style="font-size:0.8rem;letter-spacing:0.15em;text-transform:uppercase;color:rgba(201,168,76,0.7);margin-bottom:12px;">Kasih Gold Easy × SLV Group × Kasih AP Gold</div>
    <h1 class="hero-tagline">Mulakan Simpanan Emas<br>Digital Hari Ini</h1>
    <p class="hero-subtitle">Top up serendah RM5 dan kumpul Gold Points bila-bila masa. Platform simpanan emas digital yang mudah, dipercayai dan mesra Syariah.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/register" class="btn-gold btn-lg">Mulakan Sekarang →</a>
      <a href="<?= APP_URL ?>/marketplace" class="btn-gold-outline btn-lg" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.3);color:#fff;">Lihat Pasaran Maya</a>
    </div>
    <?php if ($price): ?>
    <div style="margin-top:28px;display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,0.08);border-radius:999px;padding:8px 20px;font-size:0.85rem;">
      <span style="color:rgba(255,255,255,0.6);">Harga Emas Semasa:</span>
      <span style="color:var(--gold-light);font-weight:800;">RM <?= number_format((float)$price['price_per_g'],2) ?>/g</span>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Features -->
<section style="max-width:1100px;margin:60px auto;padding:0 16px;">
  <div style="text-align:center;margin-bottom:40px;">
    <h2 style="font-size:1.8rem;font-weight:800;color:var(--kasih-dark);">Kenapa Kasih Gold Easy?</h2>
    <p style="color:#6B7280;margin-top:8px;">Ekosistem simpanan emas digital yang lengkap untuk individu dan keluarga Malaysia</p>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;">
    <?php
    $features = [
      ['💛', 'Simpan Emas Digital', 'Beli Gold Points dengan mudah mulai RM5. Setiap mata = 0.01g emas.'],
      ['🛡️', 'Mesra Syariah', 'Dibangunkan berdasarkan prinsip muamalat bertanggungjawab.'],
      ['🎯', 'Simpanan Bersasar', 'Sertai kempen simpanan: pengebumian, pendidikan, koperasi, dan lebih.'],
      ['🛍️', 'Pasaran Maya', 'Tebus Gold Points untuk produk & servis dari pedagang berlesen.'],
      ['📢', 'Program Rujukan', 'Jana komisen hingga 3 tahap (5%, 3%, 1%) bila kenalan anda membeli.'],
      ['🤖', 'Pembantu AI', 'AI yang membantu anda memahami simpanan emas dan panduan transaksi.'],
    ];
    foreach ($features as [$icon, $title, $desc]):
    ?>
    <div class="card-kasih" style="text-align:center;">
      <div style="font-size:2.2rem;margin-bottom:10px;"><?= $icon ?></div>
      <div style="font-weight:700;font-size:0.95rem;color:var(--kasih-dark);margin-bottom:6px;"><?= $title ?></div>
      <div style="font-size:0.82rem;color:#6B7280;line-height:1.6;"><?= $desc ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- How it works -->
<section style="background:var(--kasih-dark);padding:60px 24px;">
  <div style="max-width:800px;margin:0 auto;text-align:center;">
    <h2 style="font-size:1.6rem;font-weight:800;color:var(--gold-light);margin-bottom:8px;">Cara Ia Berfungsi</h2>
    <p style="color:rgba(255,255,255,0.6);margin-bottom:40px;">3 langkah mudah untuk mula simpan emas</p>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
      <?php
      $steps = [
        ['1', '📱', 'Daftar & Log Masuk', 'Buka akaun percuma dalam minit.'],
        ['2', '💳', 'Beli Gold Points', 'Masukkan jumlah RM, bayar dan mata dikreditkan.'],
        ['3', '🎯', 'Simpan atau Tebus', 'Kumpul mata atau tebus di Pasaran Maya.'],
      ];
      foreach ($steps as [$num, $icon, $title, $desc]):
      ?>
      <div style="text-align:center;">
        <div style="width:48px;height:48px;border-radius:50%;background:var(--gold);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:800;margin:0 auto 12px;"><?= $num ?></div>
        <div style="font-size:1.6rem;margin-bottom:6px;"><?= $icon ?></div>
        <div style="font-weight:700;color:#fff;margin-bottom:4px;font-size:0.9rem;"><?= $title ?></div>
        <div style="font-size:0.78rem;color:rgba(255,255,255,0.55);"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <a href="<?= APP_URL ?>/register" class="btn-gold btn-lg" style="margin-top:32px;display:inline-flex;">Daftar Percuma →</a>
  </div>
</section>

<!-- Active Campaigns -->
<?php if (!empty($campaigns)): ?>
<section style="max-width:1100px;margin:60px auto;padding:0 16px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <h2 style="font-size:1.4rem;font-weight:800;color:var(--kasih-dark);">🎯 Kempen Simpanan Aktif</h2>
    <a href="<?= APP_URL ?>/campaigns" class="btn-gold-outline btn-sm">Lihat Semua</a>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
    <?php foreach ($campaigns as $c): ?>
    <div class="card-kasih card-gold">
      <div style="font-weight:700;font-size:1rem;margin-bottom:6px;"><?= h($c['title']) ?></div>
      <?php if ($c['description']): ?><p style="font-size:0.82rem;color:#6B7280;margin-bottom:12px;"><?= h(substr($c['description'],0,100)) ?>…</p><?php endif; ?>
      <?php if ($c['target_points']): ?><div style="font-size:0.78rem;color:var(--gold-dark);font-weight:600;">Sasaran: <?= gold_format_points($c['target_points']) ?> pts</div><?php endif; ?>
      <a href="<?= APP_URL ?>/campaigns" class="btn-gold btn-sm" style="margin-top:12px;display:inline-flex;"><?= h($c['cta_text'] ?: 'Sertai Kempen') ?></a>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section style="background:linear-gradient(135deg,#FEFCE8,#FFF9E5);padding:60px 24px;text-align:center;">
  <h2 style="font-size:1.6rem;font-weight:800;color:var(--kasih-dark);margin-bottom:8px;">Mulakan Simpanan Emas Anda Hari Ini</h2>
  <p style="color:#6B7280;margin-bottom:24px;">Sertai ribuan pengguna yang sedang membina kekayaan melalui simpanan emas digital.</p>
  <a href="<?= APP_URL ?>/register" class="btn-gold btn-lg">Daftar Percuma — Bermula RM5</a>
  <p style="font-size:0.78rem;color:#9CA3AF;margin-top:12px;"><?= h(get_setting('shariah_disclaimer','')) ?></p>
</section>

<?php layout_footer(); ?>
