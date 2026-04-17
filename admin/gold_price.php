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
    $action = $_POST['action'] ?? 'set_buy_price';

    // ── Set market price (users buy gold from us) ─────────────────────────────
    if ($action === 'set_buy_price') {
        $price_val = sanitize_string($_POST['price_per_g'] ?? '');
        $notes     = sanitize_string($_POST['notes'] ?? '', 300);

        if (!is_numeric($price_val) || (float)$price_val <= 0)
            $errors['price_per_g'] = 'Sila masukkan harga yang sah (nombor positif).';
        elseif ((float)$price_val < 100 || (float)$price_val > 9999)
            $errors['price_per_g'] = 'Harga mesti antara RM100 dan RM9999 per gram.';

        if (empty($errors)) {
            $old_price = get_active_gold_price();
            $db->query("UPDATE gold_prices SET status='superseded' WHERE status='active'");
            $db->prepare("INSERT INTO gold_prices (price_per_g, effective_at, status, notes, created_by, created_at) VALUES (?, NOW(), 'active', ?, ?, NOW())")
               ->execute([$price_val, $notes, $admin['id']]);
            $new_id = (int)$db->lastInsertId();
            audit_log((int)$admin['id'], 'super_admin', 'gold_price_set', 'gold_prices', $new_id,
                $old_price ? ['price_per_g' => $old_price['price_per_g']] : null,
                ['price_per_g' => $price_val]);
            flash_set('main', 'Harga jual emas dikemaskini: RM ' . number_format((float)$price_val,2) . '/g.', 'success');
            redirect(APP_URL . '/admin/gold-price');
        }
    }

    // ── Set sell-back price (we buy gold from users) ──────────────────────────
    elseif ($action === 'set_sell_price') {
        $sell_val   = sanitize_string($_POST['sell_price_per_g'] ?? '');
        $sell_notes = sanitize_string($_POST['sell_notes'] ?? '', 300);

        if (!is_numeric($sell_val) || (float)$sell_val <= 0)
            $errors['sell_price_per_g'] = 'Sila masukkan harga belian yang sah.';
        elseif ((float)$sell_val < 50 || (float)$sell_val > 9999)
            $errors['sell_price_per_g'] = 'Harga mesti antara RM50 dan RM9999 per gram.';

        // Warn if sell price >= buy price
        $cur_buy = get_active_gold_price();
        if (empty($errors) && $cur_buy && (float)$sell_val >= (float)$cur_buy['price_per_g']) {
            $errors['sell_price_per_g'] = 'Harga belian tidak boleh sama atau melebihi harga jual (RM ' . number_format((float)$cur_buy['price_per_g'],2) . '/g).';
        }

        if (empty($errors)) {
            $db->query("UPDATE gold_sell_prices SET status='superseded' WHERE status='active'");
            $db->prepare("INSERT INTO gold_sell_prices (price_per_g, status, notes, created_by, effective_at, created_at) VALUES (?,'active',?,?,NOW(),NOW())")
               ->execute([$sell_val, $sell_notes, $admin['id']]);
            flash_set('main', 'Harga belian emas dikemaskini: RM ' . number_format((float)$sell_val,2) . '/g.', 'success');
            redirect(APP_URL . '/admin/gold-price');
        }
    }
}

$current_buy_price  = get_active_gold_price();
$current_sell_price = get_active_sell_price();

// Spread calculation
$spread = ($current_buy_price && $current_sell_price)
    ? number_format((float)$current_buy_price['price_per_g'] - (float)$current_sell_price['price_per_g'], 2)
    : null;

$buy_history  = $db->query("SELECT gp.*, u.full_name FROM gold_prices gp LEFT JOIN users u ON u.id=gp.created_by ORDER BY gp.effective_at DESC LIMIT 15")->fetchAll();
$sell_history = $db->query("SELECT gsp.*, u.full_name FROM gold_sell_prices gsp LEFT JOIN users u ON u.id=gsp.created_by ORDER BY gsp.effective_at DESC LIMIT 15")->fetchAll();

layout_begin_admin('Pengurusan Harga Emas');
?>
<div class="page-title">💛 Pengurusan Harga Emas</div>
<?= flash_html('main') ?>

<!-- Live price board -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">

  <div class="price-card">
    <div class="price-label">Harga Jual (Pengguna Beli)</div>
    <?php if ($current_buy_price): ?>
      <div class="price-big" style="color:#065F46;">RM <?= number_format((float)$current_buy_price['price_per_g'],2) ?><span style="font-size:1rem;font-weight:400;color:#9CA3AF;">/g</span></div>
      <div class="price-date">Dikemaskini: <?= format_date($current_buy_price['effective_at']) ?></div>
    <?php else: ?>
      <div style="color:#DC2626;font-weight:700;">⚠️ Belum ditetapkan</div>
    <?php endif; ?>
  </div>

  <div class="price-card" style="border-color:#FCA5A5;">
    <div class="price-label" style="color:#991B1B;">Harga Belian (Kami Beli)</div>
    <?php if ($current_sell_price): ?>
      <div class="price-big" style="color:#DC2626;">RM <?= number_format((float)$current_sell_price['price_per_g'],2) ?><span style="font-size:1rem;font-weight:400;color:#9CA3AF;">/g</span></div>
      <div class="price-date">Dikemaskini: <?= format_date($current_sell_price['effective_at']) ?></div>
    <?php else: ?>
      <div style="color:#DC2626;font-weight:700;">⚠️ Belum ditetapkan</div>
      <div style="font-size:0.78rem;color:#6B7280;margin-top:4px;">Pengguna tidak dapat menjual emas.</div>
    <?php endif; ?>
  </div>

  <div class="price-card" style="border-color:#F59E0B;background:linear-gradient(135deg,#fff,#FFFBEB);">
    <div class="price-label" style="color:#92400E;">Spread (Margin)</div>
    <?php if ($spread !== null): ?>
      <div class="price-big" style="color:#92400E;font-size:2rem;">RM <?= $spread ?><span style="font-size:1rem;font-weight:400;color:#9CA3AF;">/g</span></div>
      <?php
        $pct = $current_buy_price ? round(((float)$current_buy_price['price_per_g'] - (float)$current_sell_price['price_per_g']) / (float)$current_buy_price['price_per_g'] * 100, 2) : 0;
      ?>
      <div class="price-date"><?= $pct ?>% daripada harga jual</div>
    <?php else: ?>
      <div style="color:#9CA3AF;font-size:0.9rem;">Tetapkan kedua-dua harga</div>
    <?php endif; ?>
  </div>

</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

  <!-- Set buy price (market price) -->
  <div class="card-kasih card-gold">
    <div class="section-title">📈 Tetapkan Harga Jual (Pengguna Beli Emas)</div>
    <p style="font-size:0.8rem;color:#6B7280;margin-bottom:14px;">Harga yang dikenakan kepada pengguna apabila membeli emas.</p>
    <?= validation_errors($errors) ?>
    <form method="POST" onsubmit="return confirm('Sahkan harga jual baharu: RM ' + document.getElementById('price_per_g').value + '/g?')">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="set_buy_price">
      <div class="form-group">
        <label class="label-kasih">Harga Jual per Gram (RM) <span class="required">*</span></label>
        <div class="input-group">
          <span class="input-group-prefix">RM</span>
          <input type="number" id="price_per_g" name="price_per_g" class="input-kasih"
                 step="0.0001" min="100" max="9999" placeholder="690.0000"
                 value="<?= h($_POST['price_per_g'] ?? ($current_buy_price ? $current_buy_price['price_per_g'] : '')) ?>" required>
        </div>
        <?php if (isset($errors['price_per_g'])): ?><span class="error-text"><?= h($errors['price_per_g']) ?></span><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="label-kasih">Nota (Pilihan)</label>
        <input type="text" name="notes" class="input-kasih" placeholder="Contoh: Harga pasaran 17 Apr" maxlength="300" value="<?= h($_POST['notes'] ?? '') ?>">
      </div>
      <button type="submit" class="btn-gold btn-block">✅ Tetapkan Harga Jual</button>
    </form>
  </div>

  <!-- Set sell-back price (we buy from user) -->
  <div class="card-kasih" style="border-top:4px solid #EF4444;">
    <div class="section-title" style="color:#991B1B;">📉 Tetapkan Harga Belian (Kami Beli dari Pengguna)</div>
    <p style="font-size:0.8rem;color:#6B7280;margin-bottom:14px;">Harga yang dibayar kepada pengguna apabila mereka menjual emas kepada kami. Mesti lebih rendah daripada harga jual.</p>
    <?php if (isset($errors['sell_price_per_g'])): ?>
      <div class="alert alert-error" style="margin-bottom:12px;"><?= h($errors['sell_price_per_g']) ?></div>
    <?php endif; ?>
    <form method="POST" onsubmit="return confirm('Sahkan harga belian baharu: RM ' + document.getElementById('sell_price_per_g').value + '/g?')">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="set_sell_price">
      <div class="form-group">
        <label class="label-kasih">Harga Belian per Gram (RM) <span class="required">*</span></label>
        <div class="input-group">
          <span class="input-group-prefix">RM</span>
          <input type="number" id="sell_price_per_g" name="sell_price_per_g" class="input-kasih"
                 step="0.0001" min="50" max="9999" placeholder="660.0000"
                 value="<?= h($_POST['sell_price_per_g'] ?? ($current_sell_price ? $current_sell_price['price_per_g'] : '')) ?>" required>
        </div>
        <?php if ($current_buy_price): ?>
          <span class="help-text">Mesti kurang daripada harga jual: RM <?= number_format((float)$current_buy_price['price_per_g'],2) ?>/g</span>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="label-kasih">Nota (Pilihan)</label>
        <input type="text" name="sell_notes" class="input-kasih" placeholder="Contoh: Harga belian harian" maxlength="300" value="<?= h($_POST['sell_notes'] ?? '') ?>">
      </div>
      <button type="submit" class="btn-block" style="background:#DC2626;color:#fff;padding:10px 22px;border-radius:8px;font-weight:600;border:none;cursor:pointer;font-size:0.9rem;width:100%;">💰 Tetapkan Harga Belian</button>
    </form>
  </div>

</div>

<!-- Price histories side by side -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

  <div class="card-kasih">
    <div class="section-title">📊 Sejarah Harga Jual</div>
    <div class="table-responsive">
      <table class="table-kasih">
        <thead><tr><th>Harga RM/g</th><th>Status</th><th>Ditetapkan Oleh</th><th>Tarikh</th></tr></thead>
        <tbody>
          <?php foreach ($buy_history as $hp): ?>
          <tr>
            <td style="font-weight:700;color:var(--gold-dark);">RM <?= number_format((float)$hp['price_per_g'],2) ?></td>
            <td><?= status_badge($hp['status']) ?></td>
            <td style="font-size:0.8rem;"><?= h($hp['full_name'] ?? 'Sistem') ?></td>
            <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($hp['effective_at']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($buy_history)): ?><tr><td colspan="4" style="text-align:center;color:#9CA3AF;padding:16px;">Tiada rekod</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-kasih">
    <div class="section-title" style="color:#991B1B;">📊 Sejarah Harga Belian</div>
    <div class="table-responsive">
      <table class="table-kasih">
        <thead><tr><th>Harga RM/g</th><th>Status</th><th>Ditetapkan Oleh</th><th>Tarikh</th></tr></thead>
        <tbody>
          <?php foreach ($sell_history as $sp): ?>
          <tr>
            <td style="font-weight:700;color:#DC2626;">RM <?= number_format((float)$sp['price_per_g'],2) ?></td>
            <td><?= status_badge($sp['status']) ?></td>
            <td style="font-size:0.8rem;"><?= h($sp['full_name'] ?? 'Sistem') ?></td>
            <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($sp['effective_at']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($sell_history)): ?><tr><td colspan="4" style="text-align:center;color:#9CA3AF;padding:16px;">Belum ditetapkan</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php layout_end_admin(); ?>
