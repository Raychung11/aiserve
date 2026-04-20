<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/validation.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_login('/login');
if (auth_is_admin()) redirect(APP_URL . '/admin');

$user    = auth_user();
$user_id = auth_id();
$price   = get_active_gold_price();
$errors  = [];

// ── Step 1: Initiate purchase ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['step'])) {
    csrf_verify();
    $rm_amount = sanitize_decimal($_POST['rm_amount'] ?? '0');

    if (!is_numeric($rm_amount) || (float)$rm_amount <= 0) {
        $errors['rm_amount'] = 'Sila masukkan jumlah RM yang sah.';
    } elseif (!is_valid_rm_amount($rm_amount)) {
        $min = get_setting('min_topup_rm', '5');
        $max = get_setting('max_topup_rm', '50000');
        $errors['rm_amount'] = "Jumlah mestilah antara RM {$min} hingga RM {$max}.";
    } elseif (!$price) {
        $errors['rm_amount'] = 'Harga emas tidak tersedia. Sila hubungi admin.';
    }

    if (empty($errors)) {
        $db         = getDB();
        $price_snap = (string)$price['price_per_g'];
        $points_est = gold_points_from_rm($rm_amount, $price_snap);
        $grams_est  = gold_grams_from_points($points_est);

        $db->prepare("INSERT INTO gold_purchases (user_id,rm_amount,price_per_g_snapshot,points_credited,grams_credited,payment_status,purchase_status,payment_gateway,created_at) VALUES (?,?,?,?,?,'pending','pending','manual',NOW())")
           ->execute([$user_id, $rm_amount, $price_snap, $points_est, $grams_est]);
        $purchase_id = (int)$db->lastInsertId();
        audit_log($user_id, 'user', 'buy_gold_initiated', 'gold_purchases', $purchase_id, null, ['rm_amount' => $rm_amount]);
        redirect(APP_URL . '/buy-gold?step=pay&id=' . $purchase_id);
    }
}

// ── Step 2: Show payment instructions + proof upload ─────────────────────────
$step     = sanitize_string($_GET['step'] ?? '');
$purchase = null;
if ($step === 'pay' && isset($_GET['id'])) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM gold_purchases WHERE id=? AND user_id=? AND payment_status='pending'");
    $stmt->execute([(int)$_GET['id'], $user_id]);
    $purchase = $stmt->fetch();
    if (!$purchase) {
        flash_set('main', 'Pembelian tidak dijumpai atau telah diproses.', 'error');
        redirect(APP_URL . '/buy-gold');
    }
}

// ── Step 3: User submits proof of payment ─────────────────────────────────────
if ($step === 'confirm' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM gold_purchases WHERE id=? AND user_id=? AND payment_status='pending'");
    $stmt->execute([(int)$_GET['id'], $user_id]);
    $p = $stmt->fetch();

    if ($p) {
        $proof_filename = null;
        if (!empty($_FILES['payment_proof']['name'])) {
            $uploaded = upload_file('payment_proof', ['image/jpeg','image/png','image/webp','application/pdf'], __DIR__.'/../uploads/payments/', 5242880);
            if ($uploaded) {
                $proof_filename = $uploaded;
            } else {
                flash_set('main', 'Fail bukti tidak sah (JPG/PNG/WebP/PDF, maks 5MB). Sila cuba lagi.', 'error');
                redirect(APP_URL . '/buy-gold?step=pay&id=' . $p['id']);
            }
        }

        // Mark as paid/processing — wallet credit happens ONLY after admin confirms
        $ref_note = sanitize_string($_POST['payment_ref'] ?? '');
        $combined_ref = trim(($ref_note ? $ref_note : '') . ($proof_filename ? '|proof:' . $proof_filename : ''));
        $db->prepare("UPDATE gold_purchases SET payment_status='paid', purchase_status='processing', paid_at=NOW(), payment_reference=? WHERE id=?")
           ->execute([$combined_ref ?: null, $p['id']]);

        audit_log($user_id, 'user', 'buy_gold_proof_submitted', 'gold_purchases', (int)$p['id'], null, ['ref' => $ref_note]);
        flash_set('main', '✅ Bukti pembayaran diterima! Admin akan mengesahkan dalam <strong>1–2 jam bekerja</strong>. Gold Points akan dikreditkan setelah disahkan.', 'success');
        redirect(APP_URL . '/wallet');
    }
}

layout_begin_user($purchase ? 'Arahan Pembayaran' : 'Beli Emas');

if ($purchase):
?>
<!-- Payment Instruction Step -->
<div style="max-width:560px;margin:0 auto;">
  <div class="page-title">💳 Arahan Pembayaran</div>

  <div class="card-kasih card-gold" style="margin-bottom:16px;">
    <div style="text-align:center;margin-bottom:20px;">
      <div style="font-size:0.8rem;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;">Jumlah Perlu Dibayar</div>
      <div style="font-size:2.5rem;font-weight:900;color:var(--gold-dark);">RM <?= number_format((float)$purchase['rm_amount'], 2) ?></div>
      <div style="font-size:0.85rem;color:#9CA3AF;">ID Pembelian: #<?= $purchase['id'] ?></div>
    </div>
    <div class="calc-panel" style="margin-bottom:16px;">
      <div class="calc-result-row"><span class="calc-result-label">Gold Points Dijangka</span><span class="calc-result-value"><?= gold_format_points($purchase['points_credited']) ?> pts</span></div>
      <div class="calc-result-row"><span class="calc-result-label">Emas Dijangka</span><span class="calc-result-value"><?= gold_format_grams($purchase['grams_credited']) ?></span></div>
      <div class="calc-result-row"><span class="calc-result-label">Harga Emas (Terkunci)</span><span class="calc-result-value">RM <?= number_format((float)$purchase['price_per_g_snapshot'], 2) ?>/g</span></div>
    </div>
  </div>

  <!-- Bank Details -->
  <div class="card-kasih" style="margin-bottom:16px;">
    <div class="section-title">🏦 Maklumat Akaun Penerima</div>
    <div style="display:grid;gap:8px;">
      <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#F9FAFB;border-radius:8px;">
        <span style="color:#6B7280;font-size:0.85rem;">Bank</span>
        <strong><?= h(get_setting('bank_name', 'Maybank')) ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#F9FAFB;border-radius:8px;">
        <span style="color:#6B7280;font-size:0.85rem;">No. Akaun</span>
        <div style="display:flex;align-items:center;gap:8px;">
          <strong id="bank-acc"><?= h(get_setting('bank_account', '1234-5678-9012')) ?></strong>
          <button type="button" class="btn-gold-outline btn-sm" data-copy="<?= h(get_setting('bank_account', '1234-5678-9012')) ?>">Salin</button>
        </div>
      </div>
      <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#F9FAFB;border-radius:8px;">
        <span style="color:#6B7280;font-size:0.85rem;">Nama</span>
        <strong><?= h(get_setting('bank_account_name', 'Kasih Gold Easy Sdn Bhd')) ?></strong>
      </div>
      <div style="background:#FEF3C7;border-radius:8px;padding:10px 12px;font-size:0.82rem;color:#92400E;">
        📝 Sertakan <strong>ID #<?= $purchase['id'] ?></strong> dalam rujukan pembayaran anda.
      </div>
    </div>
  </div>

  <!-- Submit Proof Form -->
  <div class="card-kasih" style="margin-bottom:16px;">
    <div class="section-title">📤 Muat Naik Bukti Pembayaran</div>
    <form method="POST" action="<?= APP_URL ?>/buy-gold?step=confirm&id=<?= $purchase['id'] ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div style="margin-bottom:12px;">
        <label class="form-label">No. Rujukan / ID Transaksi Bank (pilihan)</label>
        <input type="text" name="payment_ref" class="form-input" placeholder="cth: IBK2024012345678">
      </div>
      <div style="margin-bottom:16px;">
        <label class="form-label">Tangkapan Skrin Resit (JPG/PNG/PDF, maks 5MB)</label>
        <input type="file" name="payment_proof" class="form-input" accept="image/jpeg,image/png,image/webp,application/pdf">
        <small style="color:#9CA3AF;">Muat naik resit mempercepatkan pengesahan admin.</small>
      </div>
      <button type="submit" class="btn-gold btn-block btn-lg" onclick="return confirm('Sahkan anda telah membuat pembayaran RM <?= number_format((float)$purchase['rm_amount'],2) ?>?')">
        ✅ Saya Telah Membuat Pembayaran
      </button>
    </form>
    <div style="text-align:center;margin-top:10px;">
      <a href="<?= APP_URL ?>/buy-gold" style="font-size:0.82rem;color:#9CA3AF;">Batal &amp; kembali</a>
    </div>
  </div>

  <div class="alert alert-info" style="font-size:0.82rem;">
    ⏳ <strong>Proses pengesahan:</strong> Setelah menghantar, admin akan menyemak dan mengesahkan pembayaran anda dalam <strong>1–2 jam bekerja</strong>. Gold Points akan dikreditkan automatik selepas disahkan.
  </div>
</div>
<?php else: ?>
<!-- Buy Gold Form -->
<div style="max-width:560px;margin:0 auto;">
  <div class="page-title">💛 Beli Emas</div>

  <?php if ($price): ?>
  <div class="price-card" style="margin-bottom:20px;">
    <div class="price-label">Harga Emas Semasa (Ditetapkan Admin)</div>
    <div class="price-big">RM <?= number_format((float)$price['price_per_g'], 2) ?><span style="font-size:1.2rem;font-weight:400;color:#9CA3AF;">/g</span></div>
    <div class="price-date">Dikemaskini: <?= format_date($price['effective_at'], 'd M Y, h:i A') ?></div>
  </div>
  <?php endif; ?>

  <div class="card-kasih card-gold" style="margin-bottom:16px;">
    <?= flash_html('main') ?>
    <?= validation_errors($errors) ?>

    <form method="POST" id="buy_gold_form">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="label-kasih" for="rm_amount_input">Jumlah Pembelian (RM) <span class="required">*</span></label>
        <div class="input-group">
          <span class="input-group-prefix">RM</span>
          <input type="number" id="rm_amount_input" name="rm_amount" class="form-input"
                 min="<?= get_setting('min_topup_rm','5') ?>" max="<?= get_setting('max_topup_rm','50000') ?>"
                 step="0.01" placeholder="0.00" required
                 data-price-per-g="<?= h($price ? (string)$price['price_per_g'] : '0') ?>"
                 value="<?= h($_POST['rm_amount'] ?? '') ?>">
        </div>
        <span class="help-text">Min: RM <?= get_setting('min_topup_rm','5') ?> &nbsp;|&nbsp; Max: RM <?= get_setting('max_topup_rm','50000') ?></span>
        <?php if (isset($errors['rm_amount'])): ?><span class="error-text"><?= h($errors['rm_amount']) ?></span><?php endif; ?>
      </div>

      <?php if ($price): ?>
      <div class="calc-panel" style="margin-bottom:20px;">
        <div style="font-size:0.75rem;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">Anggaran Pengiraan</div>
        <div class="calc-result-row"><span class="calc-result-label">Gold Points</span><span class="calc-result-value" id="estimated_points">—</span></div>
        <div class="calc-result-row"><span class="calc-result-label">Emas (gram)</span><span class="calc-result-value" id="estimated_grams">—</span></div>
        <div class="calc-result-row"><span class="calc-result-label">Nilai RM</span><span class="calc-result-value" id="estimated_rm_value">—</span></div>
      </div>
      <?php endif; ?>

      <div style="background:#FEFCE8;border-radius:8px;padding:12px;margin-bottom:16px;font-size:0.8rem;color:#92400E;">
        ⚠️ Gold Points dikreditkan selepas admin mengesahkan pembayaran anda (1–2 jam bekerja).
      </div>
      <button type="submit" class="btn-gold btn-block btn-lg" <?= !$price ? 'disabled' : '' ?>>
        Teruskan ke Pembayaran →
      </button>
    </form>
  </div>

  <div class="card-kasih">
    <div class="section-title">💡 Formula Gold Points</div>
    <div style="font-family:monospace;background:#F3F4F6;border-radius:8px;padding:12px;font-size:0.82rem;color:var(--kasih-dark);line-height:2;">
      Mata = (RM ÷ Harga/g) × 100<br>
      1 Mata = 0.01 gram emas<br>
      Nilai RM = (Mata ÷ 100) × Harga/g
    </div>
  </div>
</div>
<?php endif;
layout_end_user(); ?>
