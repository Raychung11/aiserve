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
$db      = getDB();
$errors  = [];

// Global settings
$monthly_rate = (float)get_setting('deposit_monthly_rate', '0.50');
$annual_rate  = $monthly_rate * 12;
$min_grams    = (float)get_setting('deposit_min_grams', '10.00');
$price_row    = get_active_gold_price();

// ── Handle submission ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $gold_grams    = trim($_POST['gold_grams'] ?? '');
    $gold_purity   = $_POST['gold_purity'] ?? '999';
    $gold_type     = $_POST['gold_type']   ?? 'jongkong';
    $payout_method = $_POST['payout_method'] ?? 'wallet';
    $bank_name     = sanitize_string($_POST['bank_name'] ?? '');
    $bank_acct     = sanitize_string($_POST['bank_account'] ?? '');
    $bank_name_acc = sanitize_string($_POST['bank_account_name'] ?? '');
    $user_notes    = sanitize_string($_POST['user_notes'] ?? '');

    $valid_purities = ['916','999','9999'];
    $valid_types    = ['jongkong','syiling','barang_kemas'];
    if (!in_array($gold_purity, $valid_purities)) $gold_purity = '999';
    if (!in_array($gold_type, $valid_types))       $gold_type  = 'jongkong';
    if (!in_array($payout_method, ['wallet','bank'])) $payout_method = 'wallet';

    if (!is_numeric($gold_grams) || (float)$gold_grams < $min_grams)
        $errors[] = 'Berat emas minimum ialah '.number_format($min_grams,2).'g.';

    if ($payout_method === 'bank') {
        if (!$bank_name)     $errors[] = 'Nama bank diperlukan.';
        if (!$bank_acct)     $errors[] = 'Nombor akaun bank diperlukan.';
        if (!$bank_name_acc) $errors[] = 'Nama pemegang akaun diperlukan.';
    }

    if (empty($errors)) {
        $grams      = number_format((float)$gold_grams, 6, '.', '');
        $price_snap = $price_row ? (string)$price_row['price_per_g'] : '0';
        $mkt_val    = number_format((float)$grams * (float)$price_snap, 2, '.', '');
        $dep_ref    = 'DEP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $db->prepare("INSERT INTO gold_deposits
            (user_id, deposit_ref, gold_grams, gold_purity, gold_type,
             price_per_g_snapshot, market_value_snapshot, interest_rate_snapshot,
             payout_method, bank_name, bank_account, bank_account_name,
             user_notes, status, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'pending',NOW(),NOW())")
           ->execute([$user_id, $dep_ref, $grams, $gold_purity, $gold_type,
                      $price_snap, $mkt_val, number_format($monthly_rate,4,'.',''),
                      $payout_method, $bank_name, $bank_acct, $bank_name_acc, $user_notes]);

        flash_set('deposit', 'Permohonan deposit anda (#'.$dep_ref.') berjaya dihantar! Sila bawa emas ke pejabat kami untuk pengesahan.', 'success');
        redirect(APP_URL . '/gold-deposit');
    }
}

// ── Load user deposits ────────────────────────────────────────────────────────
$deps_stmt = $db->prepare("SELECT * FROM gold_deposits WHERE user_id=? ORDER BY created_at DESC LIMIT 30");
$deps_stmt->execute([$user_id]);
$my_deposits = $deps_stmt->fetchAll();

// Interest summary per deposit
$interest_map = [];
foreach ($my_deposits as $dep) {
    $ih = $db->prepare("SELECT COALESCE(SUM(interest_grams),0) AS total_g, COALESCE(SUM(interest_rm),0) AS total_rm, COUNT(*) AS periods FROM gold_deposit_interest WHERE deposit_id=? AND payout_status='paid'");
    $ih->execute([$dep['id']]);
    $interest_map[$dep['id']] = $ih->fetch();
}

$gold_type_labels = ['jongkong'=>'Emas Jongkong (Bar)','syiling'=>'Emas Syiling (Duit Syiling)','barang_kemas'=>'Barang Kemas (Perhiasan)'];

layout_begin_user('Deposit Emas — Kasih Gold Easy');
?>
<style>
.dep-card { border:1px solid #E5E7EB;border-radius:12px;padding:16px;margin-bottom:12px;transition:box-shadow .2s; }
.dep-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); }
.dep-card.status-active   { border-left:4px solid #10B981; }
.dep-card.status-pending  { border-left:4px solid #F59E0B; }
.dep-card.status-pawned   { border-left:4px solid #3B82F6; }
.dep-card.status-redeemed { border-left:4px solid #9CA3AF; }
.dep-badge { display:inline-block;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:700; }
.dep-badge.pending  { background:#FEF3C7;color:#92400E; }
.dep-badge.active   { background:#D1FAE5;color:#065F46; }
.dep-badge.pawned   { background:#DBEAFE;color:#1E40AF; }
.dep-badge.redeemed { background:#F3F4F6;color:#6B7280; }
.dep-badge.cancelled{ background:#FEE2E2;color:#991B1B; }
</style>

<div class="page-title">🏦 Deposit Emas</div>
<?= flash_html('deposit') ?>

<!-- Info Banner -->
<div class="card-kasih card-gold" style="margin-bottom:20px;">
  <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
      <div style="font-size:1.1rem;font-weight:800;margin-bottom:4px;">💛 Deposit Emas Anda, Kami Jaga</div>
      <div style="font-size:0.85rem;color:#92400E;line-height:1.6;">
        Bawa emas fizikal anda ke pejabat kami. Kami simpan dengan selamat dan beri anda pulangan faedah setiap bulan atau setiap tahun.
      </div>
    </div>
    <div style="display:flex;gap:20px;flex-wrap:wrap;">
      <div style="text-align:center;">
        <div style="font-size:1.6rem;font-weight:900;color:var(--gold-dark);"><?= number_format($monthly_rate,2) ?>%</div>
        <div style="font-size:0.72rem;color:#92400E;font-weight:600;">per bulan</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:1.6rem;font-weight:900;color:var(--gold-dark);"><?= number_format($annual_rate,2) ?>%</div>
        <div style="font-size:0.72rem;color:#92400E;font-weight:600;">per tahun</div>
      </div>
    </div>
  </div>
  <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(180,120,0,.2);font-size:0.78rem;color:#92400E;">
    💡 Contoh: Deposit <strong>100g @ <?= number_format($monthly_rate,2) ?>%/bln</strong> → Faedah <strong><?= number_format(100*$monthly_rate/100,3) ?>g</strong> setiap bulan
    <?php if ($price_row): ?>
    (≈RM <?= number_format(100*$monthly_rate/100*(float)$price_row['price_per_g'],2) ?>)
    <?php endif; ?>
  </div>
</div>

<!-- How it works -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title" style="margin-bottom:12px;">📋 Cara Deposit Berfungsi</div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
    <?php foreach ([
        ['1','Isi Borang','Hantar permohonan di bawah dengan butiran emas anda.'],
        ['2','Bawa ke Pejabat','Datang ke kaunter kami dengan emas anda untuk pengesahan.'],
        ['3','Terima Faedah','Faedah dikreditkan ke wallet atau dibayar ke bank setiap bulan/tahun.'],
        ['4','Tebus Bila-bila','Pohon pengeluaran pada bila-bila masa, emas diserahkan dalam 1–3 hari.'],
    ] as [$n,$title,$desc]): ?>
    <div style="background:#FAFAFA;border-radius:10px;padding:14px;text-align:center;">
      <div style="width:28px;height:28px;background:var(--gold-dark);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.85rem;margin:0 auto 8px;"><?= $n ?></div>
      <div style="font-weight:700;font-size:0.85rem;margin-bottom:4px;"><?= $title ?></div>
      <div style="font-size:0.75rem;color:#6B7280;"><?= $desc ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- My Deposits -->
<?php if (!empty($my_deposits)): ?>
<div class="section-title">📦 Deposit Anda</div>
<?php foreach ($my_deposits as $dep): ?>
<?php $idata = $interest_map[$dep['id']] ?? []; ?>
<div class="dep-card status-<?= $dep['status'] ?>">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
    <div>
      <span style="font-family:monospace;font-weight:700;font-size:0.9rem;"><?= h($dep['deposit_ref']) ?></span>
      <span class="dep-badge <?= $dep['status'] ?>" style="margin-left:8px;"><?= ['pending'=>'⏳ Menunggu','active'=>'✅ Aktif','pawned'=>'🔒 Digadai','redeemed'=>'↩️ Ditebus','cancelled'=>'❌ Batal'][$dep['status']] ?></span>
    </div>
    <div style="font-size:0.75rem;color:#9CA3AF;"><?= format_date($dep['created_at'],'d M Y') ?></div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-top:12px;">
    <div>
      <div style="font-size:0.7rem;color:#9CA3AF;">Berat Emas</div>
      <div style="font-weight:800;font-size:1.1rem;"><?= gold_format_grams($dep['gold_grams']) ?></div>
    </div>
    <div>
      <div style="font-size:0.7rem;color:#9CA3AF;">Ketulenan / Jenis</div>
      <div style="font-weight:600;"><?= h($dep['gold_purity']) ?> — <?= $gold_type_labels[$dep['gold_type']] ?? h($dep['gold_type']) ?></div>
    </div>
    <div>
      <div style="font-size:0.7rem;color:#9CA3AF;">Kadar Faedah</div>
      <div style="font-weight:700;color:var(--gold-dark);"><?= $dep['interest_rate_snapshot'] ?>%/bln (<?= number_format((float)$dep['interest_rate_snapshot']*12,2) ?>%/thn)</div>
    </div>
    <div>
      <div style="font-size:0.7rem;color:#9CA3AF;">Faedah Diterima</div>
      <div style="font-weight:700;color:#065F46;">
        <?php if ($dep['payout_method']==='wallet'): ?>
          <?= number_format((float)($idata['total_g'] ?? 0), 4) ?>g (<?= (int)($idata['periods']??0) ?> kali)
        <?php else: ?>
          RM <?= number_format((float)($idata['total_rm'] ?? 0), 2) ?> (<?= (int)($idata['periods']??0) ?> kali)
        <?php endif; ?>
      </div>
    </div>
    <div>
      <div style="font-size:0.7rem;color:#9CA3AF;">Kaedah Payout</div>
      <div><?= $dep['payout_method']==='wallet'?'💰 Wallet Digital':'🏦 Akaun Bank' ?></div>
    </div>
  </div>

  <?php if ($dep['status'] === 'active'): ?>
  <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
    <a href="<?= APP_URL ?>/ar-rahnu?deposit_id=<?= $dep['id'] ?>" class="btn-gold-outline btn-sm">🕌 Gadai (Ar-Rahnu)</a>
    <a href="<?= APP_URL ?>/ar-rahnu?deposit_id=<?= $dep['id'] ?>" style="background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE;border-radius:8px;padding:8px 16px;font-size:0.82rem;font-weight:600;text-decoration:none;">ℹ️ Butiran</a>
  </div>
  <?php endif; ?>

  <?php if ($dep['status'] === 'pawned'): ?>
  <div style="margin-top:10px;font-size:0.78rem;padding:8px 12px;background:#DBEAFE;border-radius:8px;color:#1E40AF;">
    🔒 Emas ini sedang digadaikan. Tebus Ar-Rahnu anda terlebih dahulu untuk mengakses deposit ini.
  </div>
  <?php endif; ?>

  <?php if ($dep['status'] === 'pending'): ?>
  <div style="margin-top:10px;font-size:0.78rem;padding:8px 12px;background:#FEF3C7;border-radius:8px;color:#92400E;">
    ⏳ Sedang menunggu pengesahan. Sila bawa emas ke pejabat kami. Pasukan kami akan mengesahkan dalam 1–2 hari bekerja.
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- New deposit form -->
<div class="card-kasih" style="margin-top:20px;">
  <div class="section-title" style="margin-bottom:16px;">📝 Permohonan Deposit Baru</div>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="margin-bottom:16px;">
      <?php foreach ($errors as $e): ?><div>• <?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="POST" x-data="{method: '<?= ($_POST['payout_method'] ?? 'wallet') ?>'}">
    <?= csrf_field() ?>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px;">
      <div>
        <label class="form-label">Berat Emas (gram) <span style="color:red;">*</span></label>
        <input type="number" name="gold_grams" class="form-input" step="0.001" min="<?= $min_grams ?>"
               placeholder="cth: 50.000" value="<?= h($_POST['gold_grams'] ?? '') ?>" required>
        <div style="font-size:0.72rem;color:#9CA3AF;margin-top:2px;">Minimum: <?= number_format($min_grams,2) ?>g</div>
      </div>
      <div>
        <label class="form-label">Ketulenan</label>
        <select name="gold_purity" class="form-input">
          <option value="999" <?= ($_POST['gold_purity']??'999')==='999'?'selected':''?>>999 (24K Fine)</option>
          <option value="9999" <?= ($_POST['gold_purity']??'')==='9999'?'selected':''?>>9999 (24K Investment)</option>
          <option value="916" <?= ($_POST['gold_purity']??'')==='916'?'selected':''?>>916 (22K)</option>
        </select>
      </div>
      <div>
        <label class="form-label">Jenis Emas</label>
        <select name="gold_type" class="form-input">
          <option value="jongkong"    <?= ($_POST['gold_type']??'jongkong')==='jongkong'?'selected':''?>>Emas Jongkong (Bar)</option>
          <option value="syiling"     <?= ($_POST['gold_type']??'')==='syiling'?'selected':''?>>Emas Syiling</option>
          <option value="barang_kemas"<?= ($_POST['gold_type']??'')==='barang_kemas'?'selected':''?>>Barang Kemas</option>
        </select>
      </div>
    </div>

    <!-- Payout method -->
    <div style="margin-bottom:16px;">
      <label class="form-label">Kaedah Penerimaan Faedah <span style="color:red;">*</span></label>
      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <label style="display:flex;align-items:center;gap:8px;padding:12px 16px;border:2px solid var(--gold-border);border-radius:10px;cursor:pointer;flex:1;min-width:180px;"
               :style="method==='wallet'?'border-color:var(--gold-dark);background:#FFFBEB;':''">
          <input type="radio" name="payout_method" value="wallet" x-model="method" style="accent-color:var(--gold-dark);">
          <span><strong>💰 Wallet Digital</strong><br><span style="font-size:0.75rem;color:#6B7280;">Dikreditkan sebagai emas ke dalam wallet Kasih Gold</span></span>
        </label>
        <label style="display:flex;align-items:center;gap:8px;padding:12px 16px;border:2px solid var(--gold-border);border-radius:10px;cursor:pointer;flex:1;min-width:180px;"
               :style="method==='bank'?'border-color:var(--gold-dark);background:#FFFBEB;':''">
          <input type="radio" name="payout_method" value="bank" x-model="method" style="accent-color:var(--gold-dark);">
          <span><strong>🏦 Pindah Bank (RM)</strong><br><span style="font-size:0.75rem;color:#6B7280;">Faedah dalam RM dipindah ke akaun bank anda</span></span>
        </label>
      </div>
    </div>

    <!-- Bank details (shown when bank selected) -->
    <div x-show="method==='bank'" style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:16px;margin-bottom:16px;">
      <div style="font-weight:600;margin-bottom:12px;font-size:0.9rem;">🏦 Maklumat Akaun Bank</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label class="form-label" style="font-size:0.78rem;">Nama Bank</label>
          <input type="text" name="bank_name" class="form-input" placeholder="Maybank, CIMB, RHB..."
                 value="<?= h($_POST['bank_name'] ?? '') ?>">
        </div>
        <div>
          <label class="form-label" style="font-size:0.78rem;">Nombor Akaun</label>
          <input type="text" name="bank_account" class="form-input" placeholder="1234567890"
                 value="<?= h($_POST['bank_account'] ?? '') ?>">
        </div>
        <div style="grid-column:1/-1;">
          <label class="form-label" style="font-size:0.78rem;">Nama Pemegang Akaun</label>
          <input type="text" name="bank_account_name" class="form-input" placeholder="Nama penuh seperti dalam kad bank"
                 value="<?= h($_POST['bank_account_name'] ?? '') ?>">
        </div>
      </div>
    </div>

    <div style="margin-bottom:16px;">
      <label class="form-label">Catatan (pilihan)</label>
      <textarea name="user_notes" class="form-input" rows="2" placeholder="Maklumat tambahan tentang emas anda..."><?= h($_POST['user_notes'] ?? '') ?></textarea>
    </div>

    <!-- Disclaimer -->
    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px;margin-bottom:16px;font-size:0.78rem;color:#92400E;line-height:1.6;">
      ⚠️ <strong>Nota Penting:</strong> Permohonan ini perlu disahkan di pejabat kami. Sila bawa emas fizikal anda bersama dokumen pengenalan (MyKad) untuk pengesahan. Emas anda akan disimpan dengan selamat dalam peti besi berdaftar.
    </div>

    <button type="submit" class="btn-gold" style="width:100%;">🏦 Hantar Permohonan Deposit</button>
  </form>
</div>

<?php layout_end_user(); ?>
