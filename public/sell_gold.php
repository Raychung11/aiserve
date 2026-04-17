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

$sell_price = get_active_sell_price();
$balance    = get_wallet_balance($user_id);
$errors     = [];

// ── Handle submission ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $points_input    = trim($_POST['points_amount'] ?? '');
    $bank_name       = trim($_POST['bank_name'] ?? '');
    $bank_account    = trim($_POST['bank_account'] ?? '');
    $bank_acct_name  = trim($_POST['bank_account_name'] ?? '');

    if (!$sell_price)          $errors[] = 'Harga beli semasa tidak tersedia. Sila cuba sebentar lagi.';
    if (!is_numeric($points_input) || (float)$points_input <= 0) $errors[] = 'Masukkan jumlah mata yang sah.';
    if (!$bank_name)           $errors[] = 'Nama bank diperlukan.';
    if (!$bank_account)        $errors[] = 'Nombor akaun bank diperlukan.';
    if (!$bank_acct_name)      $errors[] = 'Nama pemegang akaun diperlukan.';

    $pts    = (float)$points_input;
    $avail  = (float)$balance['points'];

    if (empty($errors) && $pts > $avail) {
        $errors[] = 'Mata tidak mencukupi. Baki anda: ' . gold_format_points((string)$avail) . ' pts.';
    }

    if (empty($errors)) {
        $price_snap = (string)$sell_price['price_per_g'];
        $grams      = gold_grams_from_points((string)$pts);
        $rm_payout  = number_format((float)$grams * (float)$price_snap, 2, '.', '');

        $db->beginTransaction();
        try {
            $wid     = ensure_wallet_exists($user_id);
            $led_id  = ledger_debit($wid, $user_id, (string)$pts, $grams, $rm_payout, $price_snap,
                'gold_sell', null, 'Permintaan jual emas — ' . gold_format_points((string)$pts) . ' pts');

            $db->prepare("INSERT INTO gold_sell_requests
                (user_id, sell_price_id, points_amount, grams_amount, price_per_g_snapshot, rm_payout,
                 bank_name, bank_account, bank_account_name, status, ledger_entry_id, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,'pending',?,NOW(),NOW())")
              ->execute([$user_id, $sell_price['id'], $pts, $grams, $price_snap, $rm_payout,
                         $bank_name, $bank_account, $bank_acct_name, $led_id]);

            $db->commit();
            flash_set('sell', 'Permintaan jual emas berjaya dihantar! Pembayaran akan diproses dalam 1–3 hari bekerja.', 'success');
            redirect(APP_URL . '/sell-gold');
        } catch (\Throwable $e) {
            $db->rollBack();
            $errors[] = 'Ralat sistem: ' . $e->getMessage();
        }
    }
}

// ── Load request history ──────────────────────────────────────────────────────
$hist = $db->prepare("SELECT * FROM gold_sell_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$hist->execute([$user_id]);
$history = $hist->fetchAll();

layout_begin_user('Jual Emas Saya');
?>

<div class="page-title">💰 Jual Emas Kepada Kami</div>
<?= flash_html('sell') ?>

<!-- Price Banner -->
<div class="card-wallet" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
    <div>
      <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;">Harga Beli Emas Semasa (Kami Bayar)</div>
      <?php if ($sell_price): ?>
        <div style="font-size:2rem;font-weight:800;color:var(--gold);">RM <?= number_format((float)$sell_price['price_per_g'], 2) ?><span style="font-size:1rem;font-weight:400;color:rgba(255,255,255,0.6);">/g</span></div>
        <div style="font-size:0.78rem;color:rgba(255,255,255,0.5);margin-top:4px;">Dikemaskini: <?= format_date($sell_price['effective_at']) ?></div>
      <?php else: ?>
        <div style="color:#FCA5A5;font-weight:700;font-size:1.1rem;">⚠️ Harga belum ditetapkan — jual emas tidak tersedia buat masa ini.</div>
      <?php endif; ?>
    </div>
    <div style="text-align:right;">
      <div style="font-size:0.78rem;color:rgba(255,255,255,0.5);margin-bottom:4px;">Baki Gold Points Anda</div>
      <div style="font-size:1.4rem;font-weight:700;color:var(--gold);"><?= gold_format_points($balance['points']) ?> pts</div>
      <div style="font-size:0.82rem;color:rgba(255,255,255,0.5);">≈ <?= gold_format_grams($balance['grams']) ?></div>
    </div>
  </div>
</div>

<?php if ($sell_price && (float)$balance['points'] > 0): ?>
<!-- Sell Form -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title">📋 Hantar Permintaan Jual</div>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error" style="margin-bottom:16px;">
    <?php foreach ($errors as $e): ?><div>• <?= h($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" x-data="{
    pts: '',
    pricePerG: <?= (float)$sell_price['price_per_g'] ?>,
    pointsPerGram: 1000,
    get grams() { return this.pts > 0 ? (parseFloat(this.pts) / this.pointsPerGram).toFixed(4) : '0.0000'; },
    get rmPayout() { return this.pts > 0 ? (parseFloat(this.grams) * this.pricePerG).toFixed(2) : '0.00'; }
  }">
    <?= csrf_field() ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
      <div style="grid-column:1/-1;">
        <label class="form-label">Jumlah Gold Points untuk Dijual <span style="color:#EF4444;">*</span></label>
        <input type="number" name="points_amount" class="form-input" min="100" step="1"
               max="<?= floor((float)$balance['points']) ?>"
               placeholder="Min: 100 pts"
               x-model="pts" required>
        <div style="font-size:0.78rem;color:#9CA3AF;margin-top:4px;">
          Maksimum: <?= gold_format_points($balance['points']) ?> pts
        </div>
      </div>
    </div>

    <!-- Live Preview -->
    <div x-show="pts > 0" style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:16px;margin-bottom:20px;">
      <div style="font-size:0.78rem;color:#6B7280;margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">Anggaran Pembayaran</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
        <div>
          <div style="font-size:0.72rem;color:#9CA3AF;">Gold Points</div>
          <div style="font-weight:700;color:var(--gold-dark);" x-text="parseFloat(pts).toLocaleString() + ' pts'"></div>
        </div>
        <div>
          <div style="font-size:0.72rem;color:#9CA3AF;">Gram Emas</div>
          <div style="font-weight:700;" x-text="grams + ' g'"></div>
        </div>
        <div>
          <div style="font-size:0.72rem;color:#9CA3AF;">Anda Terima</div>
          <div style="font-weight:800;font-size:1.15rem;color:#065F46;" x-text="'RM ' + rmPayout"></div>
        </div>
      </div>
    </div>

    <div class="section-title" style="margin-top:0;">🏦 Akaun Bank untuk Pembayaran</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
      <div>
        <label class="form-label">Nama Bank <span style="color:#EF4444;">*</span></label>
        <input type="text" name="bank_name" class="form-input" placeholder="Contoh: Maybank" required>
      </div>
      <div>
        <label class="form-label">Nombor Akaun <span style="color:#EF4444;">*</span></label>
        <input type="text" name="bank_account" class="form-input" placeholder="Contoh: 1234567890" required>
      </div>
      <div style="grid-column:1/-1;">
        <label class="form-label">Nama Pemegang Akaun <span style="color:#EF4444;">*</span></label>
        <input type="text" name="bank_account_name" class="form-input"
               value="<?= h($user['full_name'] ?? '') ?>"
               placeholder="Seperti dalam buku bank" required>
      </div>
    </div>

    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px;margin-bottom:20px;font-size:0.82rem;color:#92400E;">
      ⚠️ <strong>Nota:</strong> Mata emas akan ditolak serta-merta apabila permohonan dihantar. Pembayaran RM akan dibuat ke akaun bank anda dalam 1–3 hari bekerja selepas kelulusan admin.
    </div>

    <button type="submit" class="btn-gold" style="width:100%;padding:14px;font-size:1rem;">
      💰 Hantar Permintaan Jual Emas
    </button>
  </form>
</div>

<?php elseif ((float)$balance['points'] <= 0): ?>
<div class="card-kasih" style="text-align:center;padding:40px;margin-bottom:20px;">
  <div style="font-size:3rem;margin-bottom:12px;">📭</div>
  <div style="font-weight:700;color:#374151;margin-bottom:8px;">Tiada Baki Emas</div>
  <p style="color:#9CA3AF;font-size:0.875rem;margin-bottom:20px;">Anda perlu membeli emas terlebih dahulu sebelum boleh menjual.</p>
  <a href="<?= APP_URL ?>/buy-gold" class="btn-gold">💛 Beli Emas Sekarang</a>
</div>
<?php endif; ?>

<!-- History -->
<div class="card-kasih">
  <div class="section-title">📋 Sejarah Permintaan Jual</div>
  <?php if (empty($history)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:20px 0;font-size:0.875rem;">Tiada permintaan lagi.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#ID</th><th>Mata</th><th>Gram</th><th>Harga/g</th><th>Bayaran RM</th><th>Bank</th><th>Status</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($history as $r): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $r['id'] ?></td>
          <td style="font-weight:600;color:var(--gold-dark);"><?= gold_format_points($r['points_amount']) ?></td>
          <td style="font-size:0.82rem;"><?= gold_format_grams($r['grams_amount']) ?></td>
          <td style="font-size:0.82rem;">RM <?= number_format((float)$r['price_per_g_snapshot'],2) ?></td>
          <td style="font-weight:700;color:#065F46;">RM <?= number_format((float)$r['rm_payout'],2) ?></td>
          <td style="font-size:0.78rem;color:#6B7280;"><?= h($r['bank_name']) ?> <?= h($r['bank_account']) ?></td>
          <td><?= status_badge($r['status']) ?>
            <?php if ($r['status']==='rejected' && $r['rejection_reason']): ?>
              <div style="font-size:0.7rem;color:#991B1B;margin-top:2px;"><?= h($r['rejection_reason']) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($r['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php layout_end_user(); ?>
