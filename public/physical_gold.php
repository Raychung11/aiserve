<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_login('/login');
if (auth_is_admin()) redirect(APP_URL . '/admin');

$user_id = auth_id();
$user    = auth_user();
$db      = getDB();

$balance  = get_wallet_balance($user_id);
$price    = get_active_gold_price();
$errors   = [];

define('GRAMS_PER_PLATE', 0.2);
define('POINTS_PER_PLATE', 20); // 0.2g × 100 pts/g

$avail_grams  = (float)$balance['grams'];
$max_plates   = (int)floor($avail_grams / GRAMS_PER_PLATE);

// ── KYC guard ────────────────────────────────────────────────────────────────
$kyc = $db->prepare("SELECT status FROM kyc_submissions WHERE user_id=?");
$kyc->execute([$user_id]);
$kyc_info = $kyc->fetch();
$kyc_ok   = $kyc_info && $kyc_info['status'] === 'approved';

// ── Handle submission ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $plates_input = (int)($_POST['plates_count'] ?? 0);
    $user_notes   = sanitize_string($_POST['user_notes'] ?? '', 300);

    if (!$kyc_ok)
        $errors[] = 'Anda perlu lulus eKYC terlebih dahulu.';
    if ($plates_input < 1)
        $errors[] = 'Sila masukkan bilangan plat yang sah (minimum 1).';
    if ($plates_input > $max_plates)
        $errors[] = 'Mata tidak mencukupi. Anda layak untuk maksimum ' . $max_plates . ' plat sahaja.';
    if ($max_plates < 1)
        $errors[] = 'Anda memerlukan sekurang-kurangnya 0.2g emas (20 pts) untuk menebus satu plat.';

    if (empty($errors)) {
        $pts_to_deduct = (string)($plates_input * POINTS_PER_PLATE);
        $grams_total   = number_format($plates_input * GRAMS_PER_PLATE, 6, '.', '');
        $price_snap    = $price ? (string)$price['price_per_g'] : '0';
        $rm_val        = $price ? number_format($plates_input * GRAMS_PER_PLATE * (float)$price['price_per_g'], 2, '.', '') : '0.00';

        $db->beginTransaction();
        try {
            $wid    = ensure_wallet_exists($user_id);
            $led_id = ledger_debit($wid, $user_id, $pts_to_deduct, $grams_total, $rm_val, $price_snap,
                'physical_gold', null,
                'Pengeluaran emas fizikal — ' . $plates_input . ' plat (' . $grams_total . 'g)');

            $db->prepare("INSERT INTO gold_physical_redemptions
                (user_id, plates_count, grams_per_plate, total_grams, points_redeemed,
                 price_per_g_snapshot, ledger_entry_id, user_notes, status, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,'pending',NOW(),NOW())")
              ->execute([
                  $user_id, $plates_input, GRAMS_PER_PLATE, $grams_total,
                  $pts_to_deduct, $price_snap, $led_id, $user_notes
              ]);

            $db->commit();
            flash_set('main', 'Permohonan anda untuk ' . $plates_input . ' plat emas (' . $grams_total . 'g) telah dihantar. Admin akan mengesahkan dan memaklumkan bila siap untuk diambil.', 'success');
            redirect(APP_URL . '/physical-gold');
        } catch (Throwable $e) {
            $db->rollBack();
            $errors[] = 'Ralat sistem. Sila cuba lagi.';
        }
    }
}

// ── History ───────────────────────────────────────────────────────────────────
$history = $db->prepare("SELECT gpr.*, u.full_name AS approved_by_name
    FROM gold_physical_redemptions gpr
    LEFT JOIN users u ON u.id = gpr.approved_by
    WHERE gpr.user_id = ?
    ORDER BY gpr.created_at DESC LIMIT 20");
$history->execute([$user_id]);
$history = $history->fetchAll();

layout_begin_user('Pengeluaran Emas Fizikal');
?>
<?= flash_html('main') ?>

<!-- Header info -->
<div class="page-title">🥇 Tukar Emas Digital kepada Fizikal</div>
<p style="color:#6B7280;font-size:0.875rem;margin:-8px 0 20px;">Setiap <strong>0.2g</strong> emas (20 pts) boleh ditukar kepada 1 keping plat emas fizikal 999. Koleksi di pejabat kami.</p>

<?php if (!$kyc_ok): ?>
<div class="alert alert-warning" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;">
  <span style="font-size:1.5rem;">🪪</span>
  <div style="flex:1;">
    <strong>eKYC Diperlukan</strong><br>
    <span style="font-size:0.85rem;">Sila selesaikan pengesahan identiti sebelum mengeluarkan emas fizikal.</span>
  </div>
  <a href="<?= APP_URL ?>/kyc" class="btn-gold btn-sm">Mula eKYC →</a>
</div>
<?php endif; ?>

<!-- Balance + eligibility -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">

  <div class="price-card">
    <div class="price-label">Baki Emas Anda</div>
    <div class="price-big" style="color:#065F46;"><?= gold_format_grams($balance['grams']) ?></div>
    <div class="price-date"><?= gold_format_points($balance['points']) ?> pts</div>
  </div>

  <div class="price-card" style="border-color:#C9A84C;background:linear-gradient(135deg,#fff,#FFFBEB);">
    <div class="price-label" style="color:#92400E;">Layak Ditebus</div>
    <div class="price-big" style="color:#92400E;font-size:2rem;"><?= $max_plates ?> <span style="font-size:1rem;font-weight:400;color:#9CA3AF;">plat</span></div>
    <div class="price-date"><?= number_format($max_plates * GRAMS_PER_PLATE, 4) ?>g boleh ditukar</div>
  </div>

  <div class="price-card" style="border-color:#9CA3AF;">
    <div class="price-label">Saiz Plat</div>
    <div class="price-big" style="color:#1F2937;font-size:2rem;">0.2<span style="font-size:1rem;font-weight:400;color:#9CA3AF;">g</span></div>
    <div class="price-date">Emas Tulen 999 · Koleksi di pejabat</div>
  </div>

</div>

<!-- Redemption Form -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

  <div class="card-kasih card-gold">
    <div class="section-title">🏅 Permohonan Pengeluaran Emas Fizikal</div>
    <p style="font-size:0.8rem;color:#6B7280;margin-bottom:16px;">
      Mata akan ditolak serta-merta. Plat akan disediakan dalam 3–7 hari bekerja dan anda akan dihubungi untuk penghantaran/pengambilan.
    </p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:14px;">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="physical-gold-form">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="label-kasih">Bilangan Plat (0.2g setiap plat) <span class="required">*</span></label>
        <div style="display:flex;align-items:center;gap:10px;">
          <button type="button" onclick="adjustPlates(-1)" style="width:36px;height:36px;border:1px solid #D1D5DB;border-radius:6px;background:#F9FAFB;font-size:1.2rem;cursor:pointer;flex-shrink:0;">−</button>
          <input type="number" id="plates_count" name="plates_count"
                 class="input-kasih" style="text-align:center;font-size:1.3rem;font-weight:700;"
                 min="1" max="<?= $max_plates ?>" value="<?= h((string)(int)($_POST['plates_count'] ?? 1)) ?>"
                 <?= !$kyc_ok || $max_plates < 1 ? 'disabled' : '' ?> required>
          <button type="button" onclick="adjustPlates(1)" style="width:36px;height:36px;border:1px solid #D1D5DB;border-radius:6px;background:#F9FAFB;font-size:1.2rem;cursor:pointer;flex-shrink:0;">+</button>
        </div>
        <span class="help-text">Maksimum: <?= $max_plates ?> plat</span>
      </div>

      <!-- Live preview -->
      <div id="plate-preview" style="background:#FFFBEB;border:1px solid #F59E0B;border-radius:8px;padding:12px;margin-bottom:14px;">
        <div style="font-size:0.8rem;color:#92400E;font-weight:600;margin-bottom:6px;">Ringkasan Pengeluaran</div>
        <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:#374151;margin-bottom:3px;">
          <span>Plat:</span><span id="prev-plates" style="font-weight:700;">1 plat</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:#374151;margin-bottom:3px;">
          <span>Jumlah Berat:</span><span id="prev-grams" style="font-weight:700;">0.2000 g</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:#374151;margin-bottom:3px;">
          <span>Mata Ditolak:</span><span id="prev-pts" style="font-weight:700;color:#DC2626;">20.00 pts</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:#374151;">
          <span>Nilai Anggaran:</span><span id="prev-rm" style="font-weight:700;">RM —</span>
        </div>
      </div>

      <div class="form-group">
        <label class="label-kasih">Nota (Pilihan)</label>
        <input type="text" name="user_notes" class="input-kasih"
               placeholder="Cth: Untuk hadiah perkahwinan" maxlength="300"
               value="<?= h($_POST['user_notes'] ?? '') ?>">
      </div>

      <button type="submit" class="btn-gold btn-block"
              <?= !$kyc_ok || $max_plates < 1 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
        🏅 Hantar Permohonan
      </button>
    </form>
  </div>

  <!-- Info panel -->
  <div class="card-kasih" style="border-top:4px solid #C9A84C;">
    <div class="section-title" style="color:#92400E;">ℹ️ Cara Pengeluaran</div>
    <div style="display:flex;flex-direction:column;gap:16px;margin-top:8px;">
      <?php
      $steps = [
        ['🖊️', 'Hantar Permohonan', 'Pilih bilangan plat dan hantar. Mata akan ditolak serta-merta dari baki anda.'],
        ['✅', 'Semakan Admin', 'Admin akan mengesahkan permohonan dalam 1–3 hari bekerja.'],
        ['📦', 'Plat Disediakan', 'Plat emas fizikal 999 akan disediakan dalam 3–7 hari bekerja.'],
        ['📞', 'Dihubungi', 'Kami akan menghubungi anda melalui telefon/e-mel untuk mengesahkan tarikh pengambilan.'],
        ['🏢', 'Koleksi di Pejabat', 'Bawa kad pengenalan asal untuk pengesahan identiti semasa pengambilan.'],
      ];
      foreach ($steps as [$icon, $title, $desc]):
      ?>
      <div style="display:flex;gap:12px;align-items:flex-start;">
        <div style="font-size:1.3rem;flex-shrink:0;"><?= $icon ?></div>
        <div>
          <div style="font-weight:600;font-size:0.875rem;color:var(--kasih-dark);"><?= $title ?></div>
          <div style="font-size:0.78rem;color:#6B7280;margin-top:2px;"><?= $desc ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px;margin-top:16px;">
      <div style="font-size:0.8rem;color:#991B1B;font-weight:600;margin-bottom:4px;">⚠️ Penting</div>
      <div style="font-size:0.78rem;color:#7F1D1D;line-height:1.5;">
        Permohonan yang telah dihantar tidak boleh dibatalkan. Mata hanya akan dikembalikan jika permohonan ditolak oleh admin.
      </div>
    </div>
  </div>

</div>

<!-- History -->
<div class="card-kasih">
  <div class="section-title">📋 Sejarah Permohonan</div>
  <?php if (empty($history)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:24px 0;font-size:0.875rem;">Tiada permohonan lagi.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead>
        <tr>
          <th>#</th>
          <th>Plat</th>
          <th>Berat</th>
          <th>Mata Ditolak</th>
          <th>Status</th>
          <th>Ref Pengambilan</th>
          <th>Tarikh</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($history as $r): ?>
        <tr>
          <td style="font-size:0.8rem;color:#9CA3AF;"><?= $r['id'] ?></td>
          <td style="font-weight:700;"><?= $r['plates_count'] ?> plat</td>
          <td><?= number_format((float)$r['total_grams'], 4) ?>g</td>
          <td style="color:#DC2626;font-weight:600;"><?= gold_format_points($r['points_redeemed']) ?> pts</td>
          <td><?= status_badge($r['status']) ?></td>
          <td style="font-size:0.8rem;font-weight:600;color:var(--gold-dark);">
            <?= $r['pickup_reference'] ? h($r['pickup_reference']) : '—' ?>
          </td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($r['created_at']) ?></td>
        </tr>
        <?php if ($r['admin_notes']): ?>
        <tr style="background:#FFFBEB;">
          <td colspan="7" style="font-size:0.78rem;color:#92400E;padding:6px 12px;">
            💬 Nota Admin: <?= h($r['admin_notes']) ?>
          </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
var pricePerG = <?= $price ? (float)$price['price_per_g'] : 0 ?>;
var maxPlates = <?= $max_plates ?>;

function adjustPlates(delta) {
  var input = document.getElementById('plates_count');
  var val = parseInt(input.value) || 1;
  val = Math.max(1, Math.min(maxPlates, val + delta));
  input.value = val;
  updatePreview();
}

function updatePreview() {
  var input = document.getElementById('plates_count');
  var n = Math.max(1, parseInt(input.value) || 1);
  if (n > maxPlates) n = maxPlates;

  var grams = (n * 0.2).toFixed(4);
  var pts   = (n * 20).toFixed(2);
  var rmStr = pricePerG > 0
    ? 'RM ' + (n * 0.2 * pricePerG).toLocaleString('en-MY', {minimumFractionDigits:2, maximumFractionDigits:2})
    : '—';

  document.getElementById('prev-plates').textContent = n + ' plat';
  document.getElementById('prev-grams').textContent  = grams + ' g';
  document.getElementById('prev-pts').textContent    = pts + ' pts';
  document.getElementById('prev-rm').textContent     = rmStr;
}

document.getElementById('plates_count') &&
  document.getElementById('plates_count').addEventListener('input', updatePreview);

document.getElementById('physical-gold-form') &&
  document.getElementById('physical-gold-form').addEventListener('submit', function(e) {
    var n = parseInt(document.getElementById('plates_count').value) || 0;
    if (n < 1 || n > maxPlates) { e.preventDefault(); return; }
    if (!confirm('Sahkan pengeluaran ' + n + ' plat emas (' + (n * 0.2).toFixed(1) + 'g)?\n' + (n * 20).toFixed(0) + ' mata akan ditolak serta-merta.')) {
      e.preventDefault();
    }
  });

updatePreview();
</script>

<?php layout_end_user(); ?>
