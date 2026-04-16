<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
$user_id = auth_check() ? auth_id() : 0;
$db      = getDB();

// Handle join campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id && isset($_POST['join_campaign_id'])) {
    csrf_verify();
    $cid = (int)$_POST['join_campaign_id'];
    $chk = $db->prepare("SELECT id FROM user_campaigns WHERE user_id=? AND campaign_id=?");
    $chk->execute([$user_id, $cid]);
    if (!$chk->fetch()) {
        $db->prepare("INSERT INTO user_campaigns (user_id,campaign_id,current_points,joined_at) VALUES (?,?,0,NOW())")->execute([$user_id,$cid]);
        flash_set('main','Anda telah menyertai kempen ini! Mula simpan emas hari ini.','success');
    } else {
        flash_set('main','Anda sudah menyertai kempen ini.','info');
    }
    redirect(APP_URL . '/campaigns');
}

// Get campaigns
$campaigns = $db->query("SELECT * FROM campaigns WHERE is_active=1 ORDER BY created_at DESC")->fetchAll();

// Get user's joined campaigns
$joined = [];
if ($user_id) {
    $j = $db->prepare("SELECT campaign_id, current_points FROM user_campaigns WHERE user_id=?");
    $j->execute([$user_id]);
    foreach ($j->fetchAll() as $row) $joined[$row['campaign_id']] = $row['current_points'];
}

$type_icons = [
    'funeral_savings' => ['🕌', 'Tabung Pengebumian'],
    'family_savings'  => ['👨‍👩‍👧', 'Simpanan Keluarga'],
    'child_savings'   => ['👶', 'Simpanan Anak'],
    'senior_care'     => ['👴', 'Penjagaan Warga Emas'],
    'koperasi'        => ['🏛️', 'Koperasi'],
    'merchant_promo'  => ['🏪', 'Promosi Pedagang'],
    'general'         => ['🎯', 'Kempen Umum'],
];

layout_head('Kempen Simpanan');
layout_header(auth_user() ?: null);
echo '<main style="max-width:1000px;margin:0 auto;padding:24px 16px;">';
layout_flash();
?>

<div class="hero-kasih" style="border-radius:var(--border-radius);margin-bottom:24px;padding:50px 24px;text-align:center;">
  <div class="hero-tagline" style="font-size:1.8rem;">Kempen Simpanan Emas</div>
  <p class="hero-subtitle">Tetapkan matlamat simpanan dan capainya langkah demi langkah bersama komuniti.</p>
</div>

<?php if (empty($campaigns)): ?>
  <div style="text-align:center;padding:48px;color:#9CA3AF;">Tiada kempen aktif buat masa ini.</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">
  <?php foreach ($campaigns as $c):
    [$icon, $type_label] = $type_icons[$c['type']] ?? ['🎯', 'Kempen'];
    $is_joined  = isset($joined[$c['id']]);
    $my_points  = $is_joined ? $joined[$c['id']] : 0;
    $progress   = $c['target_points'] > 0 ? min(100, round(($my_points / $c['target_points']) * 100, 1)) : 0;
    $price      = get_active_gold_price();
    $target_rm  = $c['target_rm_estimate'] ?? ($price && $c['target_points'] ? gold_rm_from_points((string)$c['target_points'], (string)$price['price_per_g']) : '0');
  ?>
  <div class="card-kasih card-gold">
    <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;">
      <div style="font-size:2rem;flex-shrink:0;"><?= $icon ?></div>
      <div>
        <div style="font-size:0.7rem;color:var(--gold-dark);font-weight:600;text-transform:uppercase;letter-spacing:0.08em;"><?= $type_label ?></div>
        <div style="font-weight:700;font-size:1rem;color:var(--kasih-dark);line-height:1.3;"><?= h($c['title']) ?></div>
      </div>
    </div>

    <?php if ($c['description']): ?>
    <p style="font-size:0.82rem;color:#6B7280;margin-bottom:14px;line-height:1.6;"><?= h(substr($c['description'], 0, 140)) . (strlen($c['description'])>140?'…':'') ?></p>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;">
      <?php if ($c['target_points']): ?>
      <div style="background:#F9FAFB;border-radius:8px;padding:10px;text-align:center;">
        <div style="font-size:0.68rem;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.06em;">Sasaran</div>
        <div style="font-size:0.9rem;font-weight:700;color:var(--gold-dark);"><?= gold_format_points($c['target_points']) ?> pts</div>
        <div style="font-size:0.72rem;color:#9CA3AF;">≈ <?= gold_format_rm($target_rm) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($c['monthly_suggested_contribution']): ?>
      <div style="background:#F9FAFB;border-radius:8px;padding:10px;text-align:center;">
        <div style="font-size:0.68rem;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.06em;">Cadangan Bulanan</div>
        <div style="font-size:0.9rem;font-weight:700;color:var(--kasih-dark);"><?= gold_format_points($c['monthly_suggested_contribution']) ?> pts</div>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($is_joined && $c['target_points']): ?>
    <div style="margin-bottom:12px;">
      <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:#6B7280;margin-bottom:4px;">
        <span>Kemajuan saya</span><span><?= $progress ?>%</span>
      </div>
      <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $progress ?>%;"></div></div>
      <div style="font-size:0.72rem;color:#9CA3AF;margin-top:3px;"><?= gold_format_points($my_points) ?> / <?= gold_format_points($c['target_points']) ?> pts</div>
    </div>
    <?php endif; ?>

    <?php if ($c['target_date']): ?>
    <div style="font-size:0.78rem;color:#9CA3AF;margin-bottom:10px;">🗓️ Sasaran: <?= format_date($c['target_date'], 'd M Y') ?></div>
    <?php endif; ?>

    <?php if ($user_id): ?>
      <?php if ($is_joined): ?>
        <div style="display:flex;gap:8px;">
          <span class="badge badge-active" style="flex:1;justify-content:center;">✓ Telah Sertai</span>
          <a href="<?= APP_URL ?>/buy-gold" class="btn-gold btn-sm">Tambah Simpanan</a>
        </div>
      <?php else: ?>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="join_campaign_id" value="<?= $c['id'] ?>">
          <button type="submit" class="btn-gold btn-block"><?= h($c['cta_text'] ?: 'Sertai Kempen') ?></button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <a href="<?= APP_URL ?>/register" class="btn-gold btn-block">Daftar &amp; Sertai</a>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card-kasih" style="margin-top:24px;background:linear-gradient(135deg,var(--kasih-dark),#16213E);color:#fff;">
  <div style="font-size:0.75rem;color:rgba(240,208,112,0.8);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px;">Peringatan Penting</div>
  <p style="font-size:0.85rem;color:rgba(255,255,255,0.7);line-height:1.7;"><?= h(get_setting('shariah_disclaimer','')) ?></p>
</div>

</main>
<?php layout_footer(); ?>
