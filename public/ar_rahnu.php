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

$balance    = get_wallet_balance($user_id);
$sell_price = get_active_sell_price();
$errors     = [];

// ── Handle submission ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $pts_input       = trim($_POST['gold_points'] ?? '');
    $purity          = $_POST['gold_purity'] ?? '999';
    $bank_name       = trim($_POST['bank_name'] ?? '');
    $bank_account    = trim($_POST['bank_account'] ?? '');
    $bank_acct_name  = trim($_POST['bank_account_name'] ?? '');
    $user_notes      = trim($_POST['user_notes'] ?? '');
    $financing_req   = trim($_POST['financing_requested'] ?? '');

    $valid_purities  = ['916', '999', '9999'];
    if (!in_array($purity, $valid_purities)) $purity = '999';

    if (!is_numeric($pts_input) || (float)$pts_input <= 0) $errors[] = 'Masukkan jumlah mata emas yang sah.';
    if (!$bank_name)            $errors[] = 'Nama bank diperlukan.';
    if (!$bank_account)         $errors[] = 'Nombor akaun bank diperlukan.';
    if (!$bank_acct_name)       $errors[] = 'Nama pemegang akaun diperlukan.';
    if (!is_numeric($financing_req) || (float)$financing_req <= 0) $errors[] = 'Masukkan jumlah pembiayaan yang dipohon.';

    $pts   = (float)$pts_input;
    $avail = (float)$balance['points'];

    if (empty($errors) && $pts > $avail) {
        $errors[] = 'Mata tidak mencukupi. Baki anda: ' . gold_format_points((string)$avail) . ' pts.';
    }

    if (empty($errors)) {
        $price_snap  = $sell_price ? (string)$sell_price['price_per_g'] : (string)(get_active_gold_price()['price_per_g'] ?? '390');
        $grams       = gold_grams_from_points((string)$pts);
        $mkt_value   = number_format((float)$grams * (float)$price_snap, 2, '.', '');
        $fin_req     = number_format((float)$financing_req, 2, '.', '');

        $db->prepare("INSERT INTO ar_rahnu_applications
            (user_id, gold_grams, gold_points, gold_purity, market_value_snapshot,
             financing_requested, bank_name, bank_account, bank_account_name,
             user_notes, status, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,'pending',NOW(),NOW())")
          ->execute([$user_id, $grams, $pts, $purity, $mkt_value, $fin_req,
                     $bank_name, $bank_account, $bank_acct_name, $user_notes]);

        flash_set('arrahu', 'Permohonan Ar Rahnu berjaya dihantar! Pasukan kami akan menghubungi anda dalam 2–3 hari bekerja.', 'success');
        redirect(APP_URL . '/ar-rahnu');
    }
}

// ── Active applications ───────────────────────────────────────────────────────
$apps_stmt = $db->prepare("SELECT * FROM ar_rahnu_applications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$apps_stmt->execute([$user_id]);
$applications = $apps_stmt->fetchAll();

// Financing rate hint: 70% of gold value (at sell price or market price)
$hint_price = $sell_price ? (float)$sell_price['price_per_g'] : (float)(get_active_gold_price()['price_per_g'] ?? 390);

layout_begin_user('Ar Rahnu — Gadai Emas Islam');
?>

<div class="page-title">🕌 Ar Rahnu — Gadai Emas Islam</div>
<?= flash_html('arrahu') ?>

<!-- Info Banner -->
<div class="card-kasih card-gold" style="margin-bottom:20px;">
  <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
    <div style="font-size:3rem;">🕌</div>
    <div style="flex:1;">
      <div style="font-weight:800;font-size:1.1rem;margin-bottom:6px;color:var(--kasih-dark);">
        Kasih Ar Rahnu — Pembiayaan Patuh Syariah
      </div>
      <p style="font-size:0.85rem;color:#6B7280;margin-bottom:8px;">
        Dalam kerjasama dengan <strong>Kasih Ar Rahnu</strong>, anda boleh menggadaikan emas digital anda
        untuk mendapatkan pembiayaan tunai segera. Produk ini mematuhi prinsip-prinsip Syariah Islam.
      </p>
      <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <div style="background:#F0FDF4;border-radius:8px;padding:10px 14px;font-size:0.82rem;">
          <div style="font-weight:700;color:#065F46;">✅ Patuh Syariah</div>
          <div style="color:#6B7280;">Diiktiraf oleh Majlis Penasihat Syariah</div>
        </div>
        <div style="background:#EFF6FF;border-radius:8px;padding:10px 14px;font-size:0.82rem;">
          <div style="font-weight:700;color:#1E40AF;">💰 Pembiayaan Sehingga 70%</div>
          <div style="color:#6B7280;">Daripada nilai emas yang digadai</div>
        </div>
        <div style="background:#FFF7ED;border-radius:8px;padding:10px 14px;font-size:0.82rem;">
          <div style="font-weight:700;color:#92400E;">📅 Tempoh 3–12 Bulan</div>
          <div style="color:#6B7280;">Boleh dilanjutkan mengikut keperluan</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Balance card -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px;">
  <div class="stat-card">
    <div class="stat-value"><?= gold_format_points($balance['points']) ?></div>
    <div class="stat-label">Baki Gold Points</div>
  </div>
  <div class="stat-card stat-card-green">
    <div class="stat-value"><?= gold_format_grams($balance['grams']) ?></div>
    <div class="stat-label">Bersamaan Gram Emas</div>
  </div>
  <div class="stat-card stat-card-blue">
    <div class="stat-value">RM <?= number_format($hint_price * (float)$balance['grams'] * 0.70, 2) ?></div>
    <div class="stat-label">Anggaran Maks. Pembiayaan (70%)</div>
  </div>
</div>

<?php if ((float)$balance['points'] > 0): ?>
<!-- Application Form -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title">📋 Permohonan Ar Rahnu Baharu</div>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error" style="margin-bottom:16px;">
    <?php foreach ($errors as $e): ?><div>• <?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" x-data="{
    pts: '',
    purity: '999',
    pricePerG: <?= $hint_price ?>,
    pointsPerGram: 1000,
    get grams() { return this.pts > 0 ? (parseFloat(this.pts) / this.pointsPerGram).toFixed(4) : '0.0000'; },
    get marketVal() { return this.pts > 0 ? (parseFloat(this.grams) * this.pricePerG).toFixed(2) : '0.00'; },
    get maxFinancing() { return this.pts > 0 ? (parseFloat(this.marketVal) * 0.70).toFixed(2) : '0.00'; }
  }">
    <?= csrf_field() ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div>
        <label class="form-label">Gold Points untuk Digadai <span style="color:#EF4444;">*</span></label>
        <input type="number" name="gold_points" class="form-input" min="100" step="1"
               max="<?= floor((float)$balance['points']) ?>"
               placeholder="Min: 100 pts"
               x-model="pts" required>
        <div style="font-size:0.78rem;color:#9CA3AF;margin-top:4px;">
          Maks: <?= gold_format_points($balance['points']) ?> pts
        </div>
      </div>
      <div>
        <label class="form-label">Ketulenan Emas <span style="color:#EF4444;">*</span></label>
        <select name="gold_purity" class="form-input" x-model="purity">
          <option value="999">999 (Emas Tulen)</option>
          <option value="9999">9999 (Emas Super Tulen)</option>
          <option value="916">916 (Emas 22 Karat)</option>
        </select>
      </div>
    </div>

    <!-- Live Calculator -->
    <div x-show="pts > 0" style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:16px;margin-bottom:16px;">
      <div style="font-size:0.78rem;color:#6B7280;margin-bottom:8px;font-weight:600;text-transform:uppercase;">Kalkulator Pembiayaan</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
        <div>
          <div style="font-size:0.72rem;color:#9CA3AF;">Gram Emas</div>
          <div style="font-weight:700;" x-text="grams + ' g'"></div>
        </div>
        <div>
          <div style="font-size:0.72rem;color:#9CA3AF;">Nilai Pasaran</div>
          <div style="font-weight:700;" x-text="'RM ' + marketVal"></div>
        </div>
        <div>
          <div style="font-size:0.72rem;color:#9CA3AF;">Pembiayaan Maks (70%)</div>
          <div style="font-weight:800;font-size:1.05rem;color:#065F46;" x-text="'RM ' + maxFinancing"></div>
        </div>
      </div>
    </div>

    <div style="margin-bottom:16px;">
      <label class="form-label">Jumlah Pembiayaan Dipohon (RM) <span style="color:#EF4444;">*</span></label>
      <input type="number" name="financing_requested" class="form-input" step="0.01" min="50"
             placeholder="Contoh: 500.00"
             :max="maxFinancing > 0 ? maxFinancing : ''" required>
      <div style="font-size:0.78rem;color:#9CA3AF;margin-top:4px;">
        Jumlah pembiayaan bergantung kepada kelulusan admin — maksimum 70% nilai emas.
      </div>
    </div>

    <div class="section-title" style="margin-top:4px;">🏦 Akaun Bank untuk Disbursemen</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div>
        <label class="form-label">Nama Bank <span style="color:#EF4444;">*</span></label>
        <input type="text" name="bank_name" class="form-input" placeholder="Contoh: CIMB Bank" required>
      </div>
      <div>
        <label class="form-label">Nombor Akaun <span style="color:#EF4444;">*</span></label>
        <input type="text" name="bank_account" class="form-input" placeholder="Nombor akaun bank" required>
      </div>
      <div style="grid-column:1/-1;">
        <label class="form-label">Nama Pemegang Akaun <span style="color:#EF4444;">*</span></label>
        <input type="text" name="bank_account_name" class="form-input"
               value="<?= h($user['full_name'] ?? '') ?>"
               placeholder="Seperti dalam buku bank" required>
      </div>
    </div>

    <div>
      <label class="form-label">Catatan / Tujuan Pembiayaan</label>
      <textarea name="user_notes" class="form-input" rows="2"
                placeholder="Nyatakan tujuan pembiayaan jika perlu..."></textarea>
    </div>

    <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px;margin:16px 0;font-size:0.82rem;color:#1E40AF;">
      ℹ️ <strong>Proses Ar Rahnu:</strong> Setelah diluluskan, pasukan Kasih Ar Rahnu akan menghubungi anda untuk pengesahan lanjut. Emas digital anda akan dikunci sebagai cagaran semasa tempoh gadaian aktif.
    </div>

    <button type="submit" class="btn-gold" style="width:100%;padding:14px;font-size:1rem;">
      🕌 Hantar Permohonan Ar Rahnu
    </button>
  </form>
</div>
<?php else: ?>
<div class="card-kasih" style="text-align:center;padding:40px;margin-bottom:20px;">
  <div style="font-size:3rem;margin-bottom:12px;">📭</div>
  <p style="color:#9CA3AF;font-size:0.875rem;margin-bottom:20px;">Anda perlu mempunyai Gold Points untuk mengemukakan permohonan Ar Rahnu.</p>
  <a href="<?= APP_URL ?>/buy-gold" class="btn-gold">💛 Beli Emas Dahulu</a>
</div>
<?php endif; ?>

<!-- Application History -->
<div class="card-kasih">
  <div class="section-title">📋 Sejarah Permohonan Ar Rahnu</div>
  <?php if (empty($applications)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:20px 0;font-size:0.875rem;">Tiada permohonan lagi.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#ID</th><th>Gram Emas</th><th>Nilai Pasaran</th><th>Dipohon</th><th>Diluluskan</th><th>Tempoh</th><th>Status</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($applications as $a): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $a['id'] ?></td>
          <td><?= gold_format_grams($a['gold_grams']) ?></td>
          <td>RM <?= number_format((float)$a['market_value_snapshot'],2) ?></td>
          <td style="font-weight:600;">RM <?= number_format((float)$a['financing_requested'],2) ?></td>
          <td style="font-weight:700;color:#065F46;">
            <?= $a['financing_approved'] ? 'RM '.number_format((float)$a['financing_approved'],2) : '—' ?>
          </td>
          <td style="font-size:0.82rem;">
            <?= $a['tenure_months'] ? $a['tenure_months'].' bulan' : '—' ?>
            <?php if ($a['maturity_date']): ?>
              <br><span style="font-size:0.72rem;color:#9CA3AF;">Tamat: <?= $a['maturity_date'] ?></span>
            <?php endif; ?>
          </td>
          <td><?= status_badge($a['status']) ?>
            <?php if ($a['status']==='rejected' && $a['rejection_reason']): ?>
              <div style="font-size:0.7rem;color:#991B1B;margin-top:2px;"><?= h($a['rejection_reason']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($a['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php layout_end_user(); ?>
