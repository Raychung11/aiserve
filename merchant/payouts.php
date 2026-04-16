<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/merchant_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db      = getDB();
$user_id = auth_id();

$m_stmt = $db->prepare("SELECT * FROM merchants WHERE user_id=?");
$m_stmt->execute([$user_id]);
$merchant = $m_stmt->fetch();
if (!$merchant) { flash_set('main','Data pedagang tidak dijumpai.','error'); redirect(APP_URL.'/login'); }
$merchant_id = (int)$merchant['id'];

$balance = get_wallet_balance($user_id);
$errors  = [];

// ── Handle payout request ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $points_req = sanitize_decimal($_POST['points_amount'] ?? '0');
    $bank_name  = sanitize_string($_POST['bank_name'] ?? '');
    $bank_acc   = sanitize_string($_POST['bank_account'] ?? '');
    $bank_holder= sanitize_string($_POST['account_holder'] ?? '');
    $notes      = sanitize_string($_POST['notes'] ?? '');

    if ((float)$points_req <= 0)                        $errors[] = 'Jumlah mata mesti lebih dari 0.';
    if ((float)$points_req > (float)$balance['points'])  $errors[] = 'Baki mata tidak mencukupi.';
    if (!$bank_name)  $errors[] = 'Nama bank diperlukan.';
    if (!$bank_acc)   $errors[] = 'Nombor akaun diperlukan.';
    if (!$bank_holder) $errors[] = 'Nama pemegang akaun diperlukan.';

    // Check no pending request
    $pend = $db->prepare("SELECT id FROM merchant_payout_requests WHERE merchant_id=? AND status='pending'");
    $pend->execute([$merchant_id]);
    if ($pend->fetch()) $errors[] = 'Anda sudah ada permintaan bayaran yang belum diproses. Sila tunggu.';

    if (empty($errors)) {
        $price      = get_active_gold_price();
        $price_snap = $price ? (string)$price['price_per_g'] : '0';
        $rm_val     = $price ? gold_rm_from_points($points_req, $price_snap) : '0.00';
        $grams      = gold_grams_from_points($points_req);

        $db->prepare("INSERT INTO merchant_payout_requests (merchant_id,points_amount,grams_amount,rm_value,price_per_g_snapshot,bank_name,bank_account_number,account_holder_name,notes,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,'pending',NOW())")
           ->execute([$merchant_id,$points_req,$grams,$rm_val,$price_snap,$bank_name,$bank_acc,$bank_holder,$notes]);

        audit_log($user_id,'merchant','payout_requested','merchant_payout_requests',(int)$db->lastInsertId(),null,['points'=>$points_req,'rm'=>$rm_val]);
        flash_set('main','Permintaan bayaran berjaya dihantar. Admin akan memproses dalam 3-5 hari bekerja.','success');
        redirect(APP_URL.'/merchant/payouts');
    }
}

// ── Fetch payout history ──────────────────────────────────────────────────────
$page = max(1,(int)($_GET['page'] ?? 1));
$per  = 15;
$ct   = $db->prepare("SELECT COUNT(*) FROM merchant_payout_requests WHERE merchant_id=?"); $ct->execute([$merchant_id]); $total = (int)$ct->fetchColumn();
$pag  = paginate($total, $per, $page, APP_URL.'/merchant/payouts?page={page}');
$p_stmt = $db->prepare("SELECT * FROM merchant_payout_requests WHERE merchant_id=? ORDER BY created_at DESC LIMIT ?,?");
$p_stmt->execute([$merchant_id, $pag['offset'], $per]);
$payouts = $p_stmt->fetchAll();

$price = get_active_gold_price();

layout_begin_merchant('Bayaran (Payout)');
?>
<div class="page-title">💰 Bayaran (Payout)</div>
<?= flash_html('main') ?>

<!-- Balance + Request Form -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
  <div class="card-wallet">
    <div class="wallet-balance-label">Baki Mata Tersedia</div>
    <div class="wallet-balance-points"><?= gold_format_points($balance['points']) ?> <span style="font-size:1rem;">pts</span></div>
    <div class="wallet-balance-rm"><?= gold_format_rm($balance['rm_value']) ?></div>
    <?php if ($price): ?>
    <div style="margin-top:8px;font-size:0.78rem;color:rgba(255,255,255,0.5);">
      Harga Emas: <?= gold_format_rm((string)$price['price_per_g']) ?>/g
    </div>
    <?php endif; ?>
  </div>
  <div class="card-kasih">
    <div class="section-title">ℹ️ Maklumat Bayaran</div>
    <ul style="color:#6B7280;font-size:0.85rem;line-height:1.8;list-style:disc;padding-left:18px;">
      <li>Bayaran diproses dalam <strong>3-5 hari bekerja</strong>.</li>
      <li>Hanya <strong>1 permintaan aktif</strong> pada satu masa.</li>
      <li>Mata akan dikurangkan setelah admin meluluskan.</li>
      <li>Minimum bayaran: <strong>100 mata</strong>.</li>
    </ul>
  </div>
</div>

<?php if (!empty($errors)): ?><div class="alert alert-error"><?= implode('<br>',$errors) ?></div><?php endif; ?>

<!-- Request Form -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title">📝 Buat Permintaan Bayaran</div>
  <?php if ($merchant['status'] !== 'active'): ?>
    <p style="color:#9CA3AF;font-size:0.875rem;">Akaun pedagang belum aktif.</p>
  <?php else: ?>
  <form method="post">
    <?= csrf_field() ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <label class="form-label">Jumlah Mata *</label>
        <input type="number" name="points_amount" class="form-input" step="0.01" min="100" max="<?= $balance['points'] ?>" placeholder="min 100" required>
        <small style="color:#9CA3AF;">Baki: <?= gold_format_points($balance['points']) ?> pts</small>
      </div>
      <div>
        <label class="form-label">Nama Bank *</label>
        <input type="text" name="bank_name" class="form-input" placeholder="cth: Maybank, CIMB..." required>
      </div>
      <div>
        <label class="form-label">Nombor Akaun *</label>
        <input type="text" name="bank_account" class="form-input" placeholder="1234567890" required>
      </div>
      <div>
        <label class="form-label">Nama Pemegang Akaun *</label>
        <input type="text" name="account_holder" class="form-input" required>
      </div>
      <div style="grid-column:1/-1;">
        <label class="form-label">Nota (pilihan)</label>
        <textarea name="notes" class="form-input" rows="2" placeholder="Tambah nota jika ada..."></textarea>
      </div>
    </div>
    <button type="submit" class="btn-gold" style="margin-top:16px;">Hantar Permintaan</button>
  </form>
  <?php endif; ?>
</div>

<!-- Payout History -->
<div class="card-kasih">
  <div class="section-title" style="margin-bottom:12px;">📜 Sejarah Bayaran</div>
  <?php if (empty($payouts)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:16px 0;font-size:0.875rem;">Tiada rekod bayaran.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#</th><th>Mata</th><th>Nilai RM</th><th>Bank</th><th>Status</th><th>Tarikh</th></tr></thead>
      <tbody>
        <?php foreach ($payouts as $p): ?>
        <tr>
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $p['id'] ?></td>
          <td style="font-weight:600;color:var(--gold-dark);"><?= gold_format_points($p['points_amount']) ?> pts</td>
          <td style="font-size:0.85rem;"><?= gold_format_rm($p['rm_value']) ?></td>
          <td style="font-size:0.8rem;"><?= h($p['bank_name']) ?><br><span style="color:#9CA3AF;"><?= h($p['bank_account_number']) ?></span></td>
          <td><?= status_badge($p['status']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($p['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>
<?php layout_end_merchant(); ?>
