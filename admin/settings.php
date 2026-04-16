<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $keys = ['site_name','tagline','support_whatsapp','min_topup_rm','max_topup_rm','referral_enabled',
             'marketplace_enabled','campaign_module_enabled','ai_assistant_enabled','shariah_disclaimer',
             'payout_min_points','merchant_approval_required','bank_name','bank_account','bank_account_name'];
    // Also referral rates
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            set_setting($k, sanitize_string($_POST[$k], 2000), (int)$admin['id']);
        }
    }
    // Referral rates
    foreach ([1,2,3] as $lvl) {
        $rate = (float)($_POST["ref_rate_{$lvl}"] ?? 0);
        if ($rate >= 0 && $rate <= 100) {
            $db->prepare("UPDATE referral_settings SET rate_percent=?, updated_by=?, updated_at=NOW() WHERE level=?")->execute([$rate, $admin['id'], $lvl]);
        }
    }
    audit_log((int)$admin['id'],'super_admin','settings_updated','settings',0,null,['updated_keys'=>$keys]);
    flash_set('main','Tetapan berjaya disimpan.','success');
    redirect(APP_URL . '/admin/settings');
}

// Load current settings
$s = [];
$all = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($all as $row) $s[$row['setting_key']] = $row['setting_value'];

$ref_rates = get_referral_settings();

layout_begin_admin('Tetapan Sistem');
?>
<div class="page-title">⚙️ Tetapan Sistem</div>
<?= flash_html('main') ?>

<form method="POST">
  <?= csrf_field() ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="md:grid-cols-2 grid-cols-1">

    <!-- General -->
    <div class="card-kasih card-gold">
      <div class="section-title">🏠 Umum</div>
      <div class="form-group"><label class="label-kasih">Nama Platform</label><input type="text" name="site_name" class="input-kasih" value="<?= h($s['site_name']??'') ?>"></div>
      <div class="form-group"><label class="label-kasih">Tagline</label><input type="text" name="tagline" class="input-kasih" value="<?= h($s['tagline']??'') ?>"></div>
      <div class="form-group"><label class="label-kasih">WhatsApp Sokongan</label><input type="text" name="support_whatsapp" class="input-kasih" value="<?= h($s['support_whatsapp']??'') ?>" placeholder="+601234567890"></div>
    </div>

    <!-- Transaction limits -->
    <div class="card-kasih card-gold">
      <div class="section-title">💰 Had Transaksi</div>
      <div class="form-group"><label class="label-kasih">Minimum Top-up (RM)</label><input type="number" name="min_topup_rm" class="input-kasih" step="0.01" value="<?= h($s['min_topup_rm']??'5') ?>"></div>
      <div class="form-group"><label class="label-kasih">Maksimum Top-up (RM)</label><input type="number" name="max_topup_rm" class="input-kasih" step="0.01" value="<?= h($s['max_topup_rm']??'50000') ?>"></div>
      <div class="form-group"><label class="label-kasih">Minimum Bayaran (pts)</label><input type="number" name="payout_min_points" class="input-kasih" value="<?= h($s['payout_min_points']??'1000') ?>"></div>
    </div>

    <!-- Module toggles -->
    <div class="card-kasih">
      <div class="section-title">🔧 Modul Sistem</div>
      <?php
      $toggles = [
        ['referral_enabled','Aktifkan Sistem Rujukan'],
        ['marketplace_enabled','Aktifkan Pasaran Maya'],
        ['campaign_module_enabled','Aktifkan Modul Kempen'],
        ['ai_assistant_enabled','Aktifkan Pembantu AI'],
        ['merchant_approval_required','Kelulusan Admin untuk Pedagang'],
      ];
      foreach ($toggles as [$k,$label]): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #F3F4F6;">
        <label style="font-size:0.875rem;font-weight:500;"><?= $label ?></label>
        <select name="<?= $k ?>" class="select-kasih" style="width:100px;">
          <option value="1" <?= ($s[$k]??'1')==='1'?'selected':'' ?>>Aktif</option>
          <option value="0" <?= ($s[$k]??'1')==='0'?'selected':'' ?>>Tidak Aktif</option>
        </select>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Referral rates -->
    <div class="card-kasih">
      <div class="section-title">📢 Kadar Komisen Rujukan</div>
      <?php foreach ([1,2,3] as $lvl): ?>
      <div class="form-group">
        <label class="label-kasih">Tahap <?= $lvl ?> (%)</label>
        <div class="input-group">
          <input type="number" name="ref_rate_<?= $lvl ?>" class="input-kasih" step="0.01" min="0" max="100" value="<?= h($ref_rates[$lvl] ?? '0') ?>">
          <span class="input-group-prefix" style="border-left:none;border-radius:0 8px 8px 0;">%</span>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="help-text">L1 = rujukan anda, L2 = rujukan rakan anda, L3 = 3 tahap ke atas.</div>
    </div>

  </div>

  <!-- Bank details -->
  <div class="card-kasih" style="margin-top:16px;">
    <div class="section-title">🏦 Maklumat Bank Penerima Pembayaran</div>
    <p style="font-size:0.82rem;color:#6B7280;margin-bottom:14px;">Maklumat ini dipaparkan kepada pengguna semasa proses pembayaran manual.</p>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
      <div class="form-group"><label class="label-kasih">Nama Bank</label><input type="text" name="bank_name" class="input-kasih" value="<?= h($s['bank_name']??'Maybank') ?>" placeholder="Maybank"></div>
      <div class="form-group"><label class="label-kasih">No. Akaun</label><input type="text" name="bank_account" class="input-kasih" value="<?= h($s['bank_account']??'') ?>" placeholder="1234-5678-9012"></div>
      <div class="form-group"><label class="label-kasih">Nama Pemegang Akaun</label><input type="text" name="bank_account_name" class="input-kasih" value="<?= h($s['bank_account_name']??'') ?>" placeholder="Kasih Gold Easy Sdn Bhd"></div>
    </div>
  </div>

  <!-- Shariah disclaimer -->
  <div class="card-kasih" style="margin-top:16px;">
    <div class="section-title">🕌 Penafian Syariah</div>
    <div class="form-group">
      <label class="label-kasih">Teks Penafian</label>
      <textarea name="shariah_disclaimer" class="textarea-kasih" rows="4"><?= h($s['shariah_disclaimer']??'') ?></textarea>
      <span class="help-text">Teks ini dipaparkan di pelbagai bahagian platform.</span>
    </div>
  </div>

  <div style="margin-top:20px;text-align:right;">
    <button type="submit" class="btn-gold btn-lg">💾 Simpan Semua Tetapan</button>
  </div>
</form>
<?php layout_end_admin(); ?>
