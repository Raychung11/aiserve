<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();

// ── Handle actions ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    // Add stock (restock)
    if ($action === 'restock') {
        $grams = (float)($_POST['restock_grams'] ?? 0);
        $notes = sanitize_string($_POST['restock_notes'] ?? '', 300);
        if ($grams <= 0) {
            flash_set('main', 'Masukkan jumlah gram yang sah.', 'error');
        } else {
            adjust_gold_stock($grams, 'restock',
                $notes ?: 'Restok manual oleh admin', null, null, (int)$admin['id']);
            audit_log((int)$admin['id'], 'super_admin', 'gold_stock_restock', 'gold_stock', 1, null, ['grams' => $grams]);
            flash_set('main', 'Stok ditambah: +' . number_format($grams, 4) . 'g.', 'success');
        }

    // Manual adjustment (positive or negative)
    } elseif ($action === 'adjust') {
        $grams = (float)($_POST['adjust_grams'] ?? 0);
        $notes = sanitize_string($_POST['adjust_notes'] ?? '', 300);
        if ($grams == 0) {
            flash_set('main', 'Masukkan nilai pelarasan (boleh negatif).', 'error');
        } else {
            adjust_gold_stock($grams, 'adjustment',
                $notes ?: 'Pelarasan manual', null, null, (int)$admin['id']);
            flash_set('main', 'Stok dilaraskan: ' . ($grams > 0 ? '+' : '') . number_format($grams, 4) . 'g.', 'success');
        }

    // Update minimum threshold
    } elseif ($action === 'set_min') {
        $min   = (float)($_POST['min_grams'] ?? 0);
        $alert = isset($_POST['alert_enabled']) ? 1 : 0;
        if ($min < 0) {
            flash_set('main', 'Had minimum tidak boleh negatif.', 'error');
        } else {
            $db->prepare("UPDATE gold_stock SET min_alert_grams=?, alert_enabled=?, updated_at=NOW() WHERE id=1")
               ->execute([$min, $alert]);
            audit_log((int)$admin['id'], 'super_admin', 'gold_stock_min_set', 'gold_stock', 1, null,
                ['min_grams' => $min, 'alert_enabled' => $alert]);
            flash_set('main', 'Had amaran stok ditetapkan: ' . number_format($min, 4) . 'g.', 'success');
        }
    }

    redirect(APP_URL . '/admin/gold-stock');
}

// ── Data ──────────────────────────────────────────────────────────────────────
$stock    = get_gold_stock();
$is_low   = gold_stock_is_low();
$price    = get_active_gold_price();
$rm_value = $price ? number_format((float)$stock['current_grams'] * (float)$price['price_per_g'], 2) : null;

// Movement history
$filter   = $_GET['type'] ?? 'all';
$where    = $filter !== 'all' ? "WHERE movement_type=" . $db->quote($filter) : '';
$movements = $db->query("
    SELECT gsm.*, u.full_name
    FROM gold_stock_movements gsm
    LEFT JOIN users u ON u.id = gsm.created_by
    $where
    ORDER BY gsm.created_at DESC LIMIT 60")->fetchAll();

// Summary stats
$stat = $db->query("SELECT
    SUM(CASE WHEN movement_type='restock'        THEN grams_change ELSE 0 END) AS total_restocked,
    SUM(CASE WHEN movement_type='buyback_in'     THEN grams_change ELSE 0 END) AS total_buyback,
    SUM(CASE WHEN movement_type='redemption_out' THEN ABS(grams_change) ELSE 0 END) AS total_redeemed
    FROM gold_stock_movements")->fetch();

layout_begin_admin('Kawalan Stok Emas');
?>

<div class="page-title">📦 Kawalan Stok Emas Fizikal</div>
<?= flash_html('main') ?>

<!-- Low stock alert -->
<?php if ($is_low): ?>
<div style="background:#FEF2F2;border:2px solid #EF4444;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
  <span style="font-size:1.8rem;">⚠️</span>
  <div>
    <div style="font-weight:700;color:#991B1B;font-size:1rem;">AMARAN: Stok Emas Rendah!</div>
    <div style="font-size:0.85rem;color:#7F1D1D;">
      Stok semasa <strong><?= number_format((float)$stock['current_grams'], 4) ?>g</strong> telah mencapai atau jatuh di bawah had minimum
      <strong><?= number_format((float)$stock['min_alert_grams'], 4) ?>g</strong>.
      Sila buat tempahan semula segera.
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Stock overview cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">

  <div style="background:<?= $is_low ? '#FEF2F2' : 'linear-gradient(135deg,#FFFBEB,#FEF3C7)' ?>;border:2px solid <?= $is_low ? '#EF4444' : '#F59E0B' ?>;border-radius:12px;padding:18px 20px;">
    <div style="font-size:0.72rem;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Stok Semasa</div>
    <div style="font-size:2.2rem;font-weight:800;color:<?= $is_low ? '#991B1B' : '#92400E' ?>;">
      <?= number_format((float)$stock['current_grams'], 4) ?><span style="font-size:1rem;font-weight:400;color:#9CA3AF;"> g</span>
    </div>
    <?php if ($rm_value): ?>
    <div style="font-size:0.8rem;color:#6B7280;margin-top:4px;">≈ RM <?= $rm_value ?> (harga semasa)</div>
    <?php endif; ?>
    <?php if ($is_low): ?>
    <div style="font-size:0.75rem;color:#991B1B;font-weight:600;margin-top:6px;">⚠️ Di bawah had minimum!</div>
    <?php endif; ?>
  </div>

  <div style="background:#F0FDF4;border:1px solid #86EFAC;border-radius:12px;padding:18px 20px;">
    <div style="font-size:0.72rem;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Had Minimum Amaran</div>
    <div style="font-size:2.2rem;font-weight:800;color:#065F46;">
      <?= number_format((float)$stock['min_alert_grams'], 4) ?><span style="font-size:1rem;font-weight:400;color:#9CA3AF;"> g</span>
    </div>
    <div style="font-size:0.75rem;margin-top:6px;">
      <?= $stock['alert_enabled'] ? '<span style="color:#065F46;font-weight:600;">🔔 Amaran Aktif</span>' : '<span style="color:#9CA3AF;">🔕 Amaran Dimatikan</span>' ?>
    </div>
  </div>

  <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:18px 20px;">
    <div style="font-size:0.72rem;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Jumlah Direstok</div>
    <div style="font-size:2rem;font-weight:800;color:#1D4ED8;"><?= number_format((float)$stat['total_restocked'], 4) ?><span style="font-size:0.9rem;font-weight:400;color:#9CA3AF;"> g</span></div>
    <div style="font-size:0.75rem;color:#6B7280;margin-top:4px;">Jumlah kumulatif masuk</div>
  </div>

  <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:18px 20px;">
    <div style="font-size:0.72rem;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Jumlah Dikeluarkan</div>
    <div style="font-size:2rem;font-weight:800;color:#991B1B;"><?= number_format((float)$stat['total_redeemed'], 4) ?><span style="font-size:0.9rem;font-weight:400;color:#9CA3AF;"> g</span></div>
    <div style="font-size:0.75rem;color:#6B7280;margin-top:4px;">Plat emas fizikal kutip</div>
  </div>

</div>

<!-- Action forms -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">

  <!-- Restock -->
  <div class="card-kasih card-gold">
    <div class="section-title">📥 Tambah Stok (Restok)</div>
    <p style="font-size:0.8rem;color:#6B7280;margin-bottom:14px;">Gunakan ini apabila menerima bekalan emas baharu daripada pembekal.</p>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="restock">
      <div class="form-group">
        <label class="label-kasih">Jumlah (gram) <span class="required">*</span></label>
        <div class="input-group">
          <span class="input-group-prefix">g</span>
          <input type="number" name="restock_grams" class="input-kasih" step="0.0001" min="0.0001"
                 placeholder="cth: 500.0000" required>
        </div>
      </div>
      <div class="form-group">
        <label class="label-kasih">Nota (Pilihan)</label>
        <input type="text" name="restock_notes" class="input-kasih"
               placeholder="cth: Terima daripada pembekal X, invois #INV-001" maxlength="300">
      </div>
      <button type="submit" class="btn-gold btn-block"
              onclick="return confirm('Sahkan tambah stok?')">
        📥 Tambah Stok
      </button>
    </form>
  </div>

  <!-- Min threshold -->
  <div class="card-kasih" style="border-top:4px solid #10B981;">
    <div class="section-title" style="color:#065F46;">🔔 Had Minimum &amp; Amaran</div>
    <p style="font-size:0.8rem;color:#6B7280;margin-bottom:14px;">Tetapkan tahap stok minimum. Amaran dipaparkan di dashboard apabila stok mencapai had ini.</p>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="set_min">
      <div class="form-group">
        <label class="label-kasih">Had Minimum (gram)</label>
        <div class="input-group">
          <span class="input-group-prefix">g</span>
          <input type="number" name="min_grams" class="input-kasih" step="0.0001" min="0"
                 value="<?= number_format((float)$stock['min_alert_grams'], 4, '.', '') ?>" required>
        </div>
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:10px;">
        <input type="checkbox" name="alert_enabled" id="alert_enabled" value="1"
               <?= $stock['alert_enabled'] ? 'checked' : '' ?>
               style="width:18px;height:18px;cursor:pointer;">
        <label for="alert_enabled" style="font-size:0.875rem;cursor:pointer;color:#374151;">Aktifkan Amaran Stok Rendah</label>
      </div>
      <button type="submit" class="btn-block" style="background:#065F46;color:#fff;padding:10px;border-radius:8px;border:none;cursor:pointer;font-weight:600;">
        🔔 Kemaskini Had
      </button>
    </form>
  </div>

  <!-- Manual adjustment -->
  <div class="card-kasih" style="border-top:4px solid #8B5CF6;">
    <div class="section-title" style="color:#5B21B6;">⚖️ Pelarasan Manual</div>
    <p style="font-size:0.8rem;color:#6B7280;margin-bottom:14px;">Laraskan stok secara manual — gunakan nilai <strong>negatif</strong> untuk tolak, <strong>positif</strong> untuk tambah (e.g. audit/kiraan semula).</p>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="adjust">
      <div class="form-group">
        <label class="label-kasih">Pelarasan (gram)</label>
        <div class="input-group">
          <span class="input-group-prefix">±g</span>
          <input type="number" name="adjust_grams" class="input-kasih" step="0.0001"
                 placeholder="cth: -5.0000 atau +10.0000" required>
        </div>
      </div>
      <div class="form-group">
        <label class="label-kasih">Sebab Pelarasan <span class="required">*</span></label>
        <input type="text" name="adjust_notes" class="input-kasih"
               placeholder="cth: Susutan audit Jun 2025" maxlength="300" required>
      </div>
      <button type="submit" class="btn-block" style="background:#7C3AED;color:#fff;padding:10px;border-radius:8px;border:none;cursor:pointer;font-weight:600;"
              onclick="return confirm('Sahkan pelarasan stok?')">
        ⚖️ Laraskan Stok
      </button>
    </form>
  </div>

</div>

<!-- Movement history -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
    <div class="section-title" style="margin:0;">📋 Sejarah Pergerakan Stok</div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
      <?php foreach (['all'=>'Semua','restock'=>'📥 Restok','buyback_in'=>'🔄 Beli Balik','redemption_out'=>'📤 Keluar','adjustment'=>'⚖️ Pelarasan'] as $v=>$l): ?>
      <a href="?type=<?= $v ?>" class="btn-sm <?= $filter===$v ? 'btn-gold' : 'btn-gold-outline' ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead>
        <tr>
          <th>Jenis</th>
          <th>Perubahan (g)</th>
          <th>Baki Selepas (g)</th>
          <th>Nota</th>
          <th>Oleh</th>
          <th>Tarikh</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($movements)): ?>
        <tr><td colspan="6" style="text-align:center;color:#9CA3AF;padding:24px;">Tiada rekod pergerakan.</td></tr>
        <?php endif; ?>
        <?php foreach ($movements as $m):
          $type_labels = [
              'restock'       => ['📥 Restok',       '#065F46', '#D1FAE5'],
              'buyback_in'    => ['🔄 Beli Balik',   '#1D4ED8', '#DBEAFE'],
              'redemption_out'=> ['📤 Keluar Fizikal','#991B1B', '#FEE2E2'],
              'adjustment'    => ['⚖️ Pelarasan',    '#5B21B6', '#EDE9FE'],
          ];
          [$tlabel, $tcolor, $tbg] = $type_labels[$m['movement_type']] ?? ['—','#6B7280','#F3F4F6'];
          $is_positive = (float)$m['grams_change'] > 0;
        ?>
        <tr>
          <td>
            <span style="background:<?= $tbg ?>;color:<?= $tcolor ?>;border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:600;white-space:nowrap;">
              <?= $tlabel ?>
            </span>
          </td>
          <td style="font-weight:700;font-size:1rem;color:<?= $is_positive ? '#065F46' : '#991B1B' ?>;">
            <?= $is_positive ? '+' : '' ?><?= number_format((float)$m['grams_change'], 4) ?>g
          </td>
          <td style="font-weight:600;color:#374151;"><?= number_format((float)$m['grams_after'], 4) ?>g</td>
          <td style="font-size:0.8rem;color:#6B7280;max-width:220px;">
            <?= h($m['notes'] ?? '—') ?>
            <?php if ($m['reference_type'] && $m['reference_id']): ?>
            <div style="font-size:0.7rem;color:#9CA3AF;"><?= h($m['reference_type']) ?> #<?= $m['reference_id'] ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:0.8rem;"><?= h($m['full_name'] ?? 'Sistem') ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($m['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php layout_end_admin(); ?>
