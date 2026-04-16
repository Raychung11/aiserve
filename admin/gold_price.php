<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

$db      = getDB();
$admin   = auth_user();
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $price_val = sanitize_string($_POST['price_per_g'] ?? '');
    $notes     = sanitize_string($_POST['notes'] ?? '', 300);

    if (!is_numeric($price_val) || (float)$price_val <= 0) {
        $errors['price_per_g'] = 'Sila masukkan harga yang sah (nombor positif).';
    } elseif ((float)$price_val < 100 || (float)$price_val > 9999) {
        $errors['price_per_g'] = 'Harga mesti antara RM100 dan RM9999 per gram.';
    }

    if (empty($errors)) {
        $old_price = get_active_gold_price();
        // Supersede current active price
        $db->query("UPDATE gold_prices SET status='superseded' WHERE status='active'");
        // Insert new
        $db->prepare("INSERT INTO gold_prices (price_per_g, effective_at, status, notes, created_by, created_at) VALUES (?, NOW(), 'active', ?, ?, NOW())")
           ->execute([$price_val, $notes, $admin['id']]);
        $new_id = (int)$db->lastInsertId();
        audit_log((int)$admin['id'], 'super_admin', 'gold_price_set', 'gold_prices', $new_id,
            $old_price ? ['price_per_g' => $old_price['price_per_g']] : null,
            ['price_per_g' => $price_val]);
        flash_set('main', 'Harga emas berjaya dikemaskini kepada RM ' . number_format((float)$price_val,2) . '/g.', 'success');
        redirect(APP_URL . '/admin/gold-price');
    }
}

$current_price = get_active_gold_price();
// History
$history = $db->query("SELECT gp.*, u.full_name FROM gold_prices gp LEFT JOIN users u ON u.id=gp.created_by ORDER BY gp.effective_at DESC LIMIT 20")->fetchAll();

layout_begin_admin('Pengurusan Harga Emas');
?>
<div class="page-title">💛 Pengurusan Harga Emas</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;" class="md:grid-cols-2 grid-cols-1">
  <!-- Current price -->
  <div class="price-card">
    <div class="price-label">Harga Emas Aktif Sekarang</div>
    <?php if ($current_price): ?>
      <div class="price-big">RM <?= number_format((float)$current_price['price_per_g'], 2) ?><span style="font-size:1rem;font-weight:400;color:#9CA3AF;">/g</span></div>
      <div class="price-date">Dikemaskini: <?= format_date($current_price['effective_at']) ?></div>
      <?php if ($current_price['notes']): ?><div style="font-size:0.82rem;color:#6B7280;margin-top:6px;font-style:italic;">"<?= h($current_price['notes']) ?>"</div><?php endif; ?>
    <?php else: ?>
      <div style="color:#DC2626;font-weight:700;font-size:1.2rem;">⚠️ Tiada harga aktif</div>
      <div style="font-size:0.8rem;color:#6B7280;margin-top:4px;">Pengguna tidak boleh membeli emas sehingga harga ditetapkan.</div>
    <?php endif; ?>
  </div>

  <!-- Set new price form -->
  <div class="card-kasih card-gold">
    <div class="section-title">Tetapkan Harga Baharu</div>
    <?= flash_html('main') ?>
    <?= validation_errors($errors) ?>
    <form method="POST" onsubmit="return confirm('Sahkan tetapkan harga emas baharu: RM ' + document.getElementById(\'price_per_g\').value + '/g? Ini akan menggantikan harga aktif semasa.')">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="label-kasih" for="price_per_g">Harga per Gram (RM) <span class="required">*</span></label>
        <div class="input-group">
          <span class="input-group-prefix">RM</span>
          <input type="number" id="price_per_g" name="price_per_g" class="input-kasih"
                 step="0.0001" min="100" max="9999" placeholder="390.0000"
                 value="<?= h($_POST['price_per_g'] ?? ($current_price ? $current_price['price_per_g'] : '')) ?>" required>
        </div>
        <?php if (isset($errors['price_per_g'])): ?><span class="error-text"><?= h($errors['price_per_g']) ?></span><?php endif; ?>
        <span class="help-text">Format: 390.0000 (sehingga 4 titik perpuluhan)</span>
      </div>
      <div class="form-group">
        <label class="label-kasih" for="notes">Nota (Pilihan)</label>
        <input type="text" id="notes" name="notes" class="input-kasih" placeholder="Contoh: Harga pasaran semasa" maxlength="300" value="<?= h($_POST['notes'] ?? '') ?>">
      </div>
      <div class="alert alert-warning" style="font-size:0.8rem;">
        ⚠️ Harga baharu akan menggantikan harga aktif semasa. Semua transaksi baharu akan menggunakan harga ini.
      </div>
      <button type="submit" class="btn-gold btn-block">✅ Tetapkan Harga Baharu</button>
    </form>
  </div>
</div>

<!-- Price history -->
<div class="card-kasih">
  <div class="section-title">📊 Sejarah Harga Emas</div>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Harga (RM/g)</th><th>Status</th><th>Nota</th><th>Ditetapkan Oleh</th><th>Tarikh Efektif</th></tr></thead>
      <tbody>
        <?php foreach ($history as $hp): ?>
        <tr>
          <td style="font-weight:700;font-size:1rem;color:var(--gold-dark);">RM <?= number_format((float)$hp['price_per_g'],4) ?></td>
          <td><?= status_badge($hp['status']) ?></td>
          <td style="font-size:0.82rem;color:#6B7280;max-width:200px;"><?= h($hp['notes'] ?? '—') ?></td>
          <td style="font-size:0.82rem;"><?= h($hp['full_name'] ?? 'Sistem') ?></td>
          <td style="font-size:0.78rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($hp['effective_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_end_admin(); ?>
