<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db       = getDB();
$admin    = auth_user();
$admin_id = (int)$admin['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $dep_id = (int)($_POST['dep_id'] ?? 0);

    // ── Save global rate settings ────────────────────────────────────────────
    if ($action === 'save_settings') {
        $rate     = (float)($_POST['deposit_monthly_rate'] ?? 0);
        $min_g    = (float)($_POST['deposit_min_grams'] ?? 10);
        if ($rate < 0 || $rate > 10) { flash_set('main','Kadar tidak sah (0-10%).','error'); redirect(APP_URL.'/admin/gold-deposit'); }
        set_setting('deposit_monthly_rate', number_format($rate, 4, '.', ''), $admin_id);
        set_setting('deposit_min_grams',    number_format($min_g, 2, '.', ''), $admin_id);
        flash_set('main','Tetapan kadar faedah deposit dikemaskini.','success');
        redirect(APP_URL.'/admin/gold-deposit');
    }

    // load deposit record
    if ($dep_id) {
        $ds = $db->prepare("SELECT gd.*, u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone FROM gold_deposits gd JOIN users u ON u.id=gd.user_id WHERE gd.id=?");
        $ds->execute([$dep_id]);
        $dep = $ds->fetch();
        if (!$dep) { flash_set('main','Deposit tidak dijumpai.','error'); redirect(APP_URL.'/admin/gold-deposit'); }
    }

    // ── Verify / activate deposit ────────────────────────────────────────────
    if ($action === 'verify' && isset($dep) && $dep['status'] === 'pending') {
        $admin_notes  = sanitize_string($_POST['admin_notes'] ?? '');
        $price_snap   = get_active_gold_price();
        $price_per_g  = $price_snap ? (string)$price_snap['price_per_g'] : '0';
        $mkt_val      = number_format((float)$dep['gold_grams'] * (float)$price_per_g, 2, '.', '');
        $rate_snap    = get_setting('deposit_monthly_rate', '0.50');

        $db->prepare("UPDATE gold_deposits SET status='active', verified_by=?, verified_at=NOW(),
            price_per_g_snapshot=?, market_value_snapshot=?, interest_rate_snapshot=?,
            admin_notes=?, updated_at=NOW() WHERE id=?")
           ->execute([$admin_id, $price_per_g, $mkt_val, $rate_snap, $admin_notes, $dep_id]);
        audit_log($admin_id,'super_admin','deposit_verified','gold_deposits',$dep_id,null,null);
        flash_set('main','Deposit #'.$dep['deposit_ref'].' disahkan & diaktifkan.','success');
    }

    // ── Reject deposit ───────────────────────────────────────────────────────
    elseif ($action === 'reject_deposit' && isset($dep) && $dep['status'] === 'pending') {
        $reason = sanitize_string($_POST['rejection_reason'] ?? '');
        if (!$reason) { flash_set('main','Sila masukkan sebab.','error'); redirect(APP_URL.'/admin/gold-deposit?view='.$dep_id); }
        $db->prepare("UPDATE gold_deposits SET status='cancelled', admin_notes=?, updated_at=NOW() WHERE id=?")
           ->execute([$reason, $dep_id]);
        flash_set('main','Deposit #'.$dep_id.' dibatalkan.','success');
    }

    // ── Approve redemption ───────────────────────────────────────────────────
    elseif ($action === 'redeem_deposit' && isset($dep) && $dep['status'] === 'active') {
        $db->prepare("UPDATE gold_deposits SET status='redeemed', redeemed_at=NOW(), updated_at=NOW() WHERE id=?")
           ->execute([$dep_id]);
        audit_log($admin_id,'super_admin','deposit_redeemed','gold_deposits',$dep_id,null,null);
        flash_set('main','Deposit #'.$dep['deposit_ref'].' ditandakan ditebus. Emas boleh diserahkan kepada pelanggan.','success');
    }

    // ── Record interest payment ──────────────────────────────────────────────
    elseif ($action === 'pay_interest' && isset($dep) && in_array($dep['status'], ['active','pawned'])) {
        $period_label = sanitize_string($_POST['period_label'] ?? '');
        $period_type  = $_POST['period_type'] === 'annually' ? 'annually' : 'monthly';
        $rate         = (float)$dep['interest_rate_snapshot'];
        $grams        = (float)$dep['gold_grams'];
        $method       = $dep['payout_method'];
        $notes        = sanitize_string($_POST['admin_notes'] ?? '');

        if (!$period_label) { flash_set('main','Masukkan label tempoh.','error'); redirect(APP_URL.'/admin/gold-deposit?view='.$dep_id); }

        // Check for duplicate period
        $dup = $db->prepare("SELECT id FROM gold_deposit_interest WHERE deposit_id=? AND period_label=?");
        $dup->execute([$dep_id, $period_label]);
        if ($dup->fetch()) { flash_set('main','Tempoh "'.$period_label.'" sudah pernah dibayar untuk deposit ini.','error'); redirect(APP_URL.'/admin/gold-deposit?view='.$dep_id); }

        $rate_used      = $period_type === 'annually' ? $rate * 12 : $rate;
        $interest_grams = round($grams * ($rate_used / 100), 6);
        $price_now      = get_active_gold_price();
        $price_g        = $price_now ? (float)$price_now['price_per_g'] : 0;
        $interest_rm    = $price_g > 0 ? round($interest_grams * $price_g, 2) : 0;

        $ledger_id = null;
        if ($method === 'wallet') {
            $wid = ensure_wallet_exists((int)$dep['user_id']);
            $pts_to_credit = (string)round($interest_grams * 100, 6); // 100 pts = 1g
            $ledger_id = ledger_credit(
                $wid, (int)$dep['user_id'],
                $pts_to_credit, (string)$interest_grams,
                (string)$interest_rm, (string)$price_g,
                'deposit_interest', $dep_id,
                'Faedah deposit '.$dep['deposit_ref'].' – '.$period_label
            );
        }

        $db->prepare("INSERT INTO gold_deposit_interest
            (deposit_id,period_label,period_type,grams_deposited,interest_rate,interest_grams,interest_rm,
             price_per_g,payout_method,payout_status,ledger_entry_id,paid_at,admin_notes,created_by,created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),?,?,NOW())")
           ->execute([$dep_id,$period_label,$period_type,$grams,$rate_used,$interest_grams,$interest_rm,
                      $price_g,$method,'paid',$ledger_id,$notes,$admin_id]);
        audit_log($admin_id,'super_admin','deposit_interest_paid','gold_deposits',$dep_id,null,['period'=>$period_label,'method'=>$method]);
        flash_set('main','Faedah tempoh "'.$period_label.'" berjaya direkodkan'.($method==='wallet'?' dan dikreditkan ke wallet pengguna.':' (bayaran bank direkodkan).'),'success');
    }

    redirect(APP_URL.'/admin/gold-deposit');
}

// ── Settings ─────────────────────────────────────────────────────────────────
$monthly_rate = get_setting('deposit_monthly_rate', '0.50');
$min_grams    = get_setting('deposit_min_grams', '10.00');

// ── Single view ───────────────────────────────────────────────────────────────
$view_id  = (int)($_GET['view'] ?? 0);
$view_dep = null;
$interest_history = [];
if ($view_id) {
    $vs = $db->prepare("SELECT gd.*, u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone FROM gold_deposits gd JOIN users u ON u.id=gd.user_id WHERE gd.id=?");
    $vs->execute([$view_id]);
    $view_dep = $vs->fetch();
    if ($view_dep) {
        $ih = $db->prepare("SELECT * FROM gold_deposit_interest WHERE deposit_id=? ORDER BY created_at DESC");
        $ih->execute([$view_id]);
        $interest_history = $ih->fetchAll();
    }
}

// ── List ──────────────────────────────────────────────────────────────────────
$status_filter = $_GET['status'] ?? '';
$q             = trim($_GET['q'] ?? '');
$page          = max(1,(int)($_GET['page'] ?? 1));
$per           = 20;

$where  = '1=1';
$params = [];
if ($status_filter) { $where .= ' AND gd.status=?';  $params[] = $status_filter; }
if ($q)             { $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR gd.deposit_ref LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }

$ct = $db->prepare("SELECT COUNT(*) FROM gold_deposits gd JOIN users u ON u.id=gd.user_id WHERE $where");
$ct->execute($params); $total = (int)$ct->fetchColumn();
$pag = paginate($total, $per, $page, APP_URL.'/admin/gold-deposit?status='.urlencode($status_filter).'&q='.urlencode($q).'&page={page}');

$list_stmt = $db->prepare("SELECT gd.*, u.full_name AS user_name, u.email AS user_email FROM gold_deposits gd JOIN users u ON u.id=gd.user_id WHERE $where ORDER BY FIELD(gd.status,'pending','active','pawned','redeemed','cancelled'), gd.created_at DESC LIMIT ?,?");
$list_stmt->execute(array_merge($params, [$pag['offset'], $per]));
$deposits = $list_stmt->fetchAll();

// Stats
$st = $db->query("SELECT
    SUM(status='pending')  AS pending,
    SUM(status='active')   AS active,
    SUM(status='pawned')   AS pawned,
    SUM(status='redeemed') AS redeemed,
    COALESCE(SUM(CASE WHEN status IN ('active','pawned') THEN gold_grams ELSE 0 END),0) AS grams_held
FROM gold_deposits")->fetch();

$gold_type_labels = ['jongkong'=>'Emas Jongkong','syiling'=>'Emas Syiling','barang_kemas'=>'Barang Kemas'];

layout_begin_admin('Deposit Emas — Admin');
?>
<style>
.dep-badge-pending  { background:#FEF3C7;color:#92400E;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:600; }
.dep-badge-active   { background:#D1FAE5;color:#065F46;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:600; }
.dep-badge-pawned   { background:#EFF6FF;color:#1E40AF;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:600; }
.dep-badge-redeemed { background:#F3F4F6;color:#6B7280;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:600; }
.dep-badge-cancelled{ background:#FEE2E2;color:#991B1B;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:600; }
</style>

<div class="page-title">🏦 Pengurusan Deposit Emas</div>
<?= flash_html('main') ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px;">
  <div class="stat-card" style="<?= (int)$st['pending']>0?'border-left:3px solid #F59E0B;':''?>">
    <div class="stat-value" style="color:<?= (int)$st['pending']>0?'#D97706':'inherit'?>"><?= (int)$st['pending'] ?></div>
    <div class="stat-label">Menunggu Sahkan</div>
  </div>
  <div class="stat-card" style="background:#D1FAE5;"><div class="stat-value" style="color:#065F46;"><?= (int)$st['active'] ?></div><div class="stat-label">Aktif</div></div>
  <div class="stat-card" style="background:#EFF6FF;"><div class="stat-value" style="color:#1E40AF;"><?= (int)$st['pawned'] ?></div><div class="stat-label">Digadai</div></div>
  <div class="stat-card"><div class="stat-value"><?= (int)$st['redeemed'] ?></div><div class="stat-label">Ditebus</div></div>
  <div class="stat-card" style="background:#FFF7ED;">
    <div class="stat-value" style="color:#B45309;font-size:1rem;"><?= number_format((float)$st['grams_held'],3) ?>g</div>
    <div class="stat-label">Emas Dalam Jagaan</div>
  </div>
</div>

<!-- Rate Settings -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title" style="margin-bottom:12px;">⚙️ Tetapan Kadar Faedah Deposit</div>
  <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_settings">
    <div>
      <label class="form-label" style="font-size:0.78rem;">Kadar Bulanan (%/bulan)</label>
      <input type="number" name="deposit_monthly_rate" class="form-input" step="0.01" min="0" max="10"
             value="<?= h($monthly_rate) ?>" style="width:140px;">
      <div style="font-size:0.7rem;color:#9CA3AF;margin-top:2px;">Kadar tahunan: <?= number_format((float)$monthly_rate*12,2) ?>%/tahun</div>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Minimum Deposit (gram)</label>
      <input type="number" name="deposit_min_grams" class="form-input" step="0.01" min="1"
             value="<?= h($min_grams) ?>" style="width:140px;">
    </div>
    <button type="submit" class="btn-gold btn-sm">Simpan Tetapan</button>
  </form>
</div>

<?php if ($view_dep): ?>
<!-- ── Detail View ── -->
<?php $dep = $view_dep; ?>
<div class="card-kasih" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
    <div style="display:flex;align-items:center;gap:10px;">
      <span class="section-title" style="margin:0;">🏦 <?= h($dep['deposit_ref']) ?></span>
      <span class="dep-badge-<?= $dep['status'] ?>"><?= ['pending'=>'⏳ Menunggu','active'=>'✅ Aktif','pawned'=>'🔒 Digadai','redeemed'=>'↩️ Ditebus','cancelled'=>'❌ Batal'][$dep['status']] ?></span>
    </div>
    <a href="<?= APP_URL ?>/admin/gold-deposit" class="btn-gold-outline btn-sm">← Kembali</a>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
    <div><div style="font-size:0.72rem;color:#9CA3AF;">Nama Pengguna</div><div style="font-weight:700;"><?= h($dep['user_name']) ?></div></div>
    <div><div style="font-size:0.72rem;color:#9CA3AF;">E-mel / Telefon</div><div style="font-size:0.85rem;"><?= h($dep['user_email']) ?> / <?= h($dep['user_phone'] ?: '—') ?></div></div>
    <div><div style="font-size:0.72rem;color:#9CA3AF;">Berat Emas</div><div style="font-weight:700;font-size:1.1rem;"><?= gold_format_grams($dep['gold_grams']) ?></div></div>
    <div><div style="font-size:0.72rem;color:#9CA3AF;">Ketulenan / Jenis</div><div style="font-weight:600;"><?= h($dep['gold_purity']) ?> — <?= $gold_type_labels[$dep['gold_type']] ?? h($dep['gold_type']) ?></div></div>
    <div><div style="font-size:0.72rem;color:#9CA3AF;">Nilai Pasaran (snap)</div><div style="font-weight:700;">RM <?= number_format((float)$dep['market_value_snapshot'],2) ?></div></div>
    <div><div style="font-size:0.72rem;color:#9CA3AF;">Kadar Faedah (snap)</div><div style="font-weight:600;"><?= $dep['interest_rate_snapshot'] ?>%/bln (<?= number_format((float)$dep['interest_rate_snapshot']*12,2) ?>%/thn)</div></div>
    <div><div style="font-size:0.72rem;color:#9CA3AF;">Kaedah Payout</div>
      <div style="font-weight:600;"><?= $dep['payout_method']==='wallet'?'💰 Wallet Digital':'🏦 Akaun Bank' ?></div></div>
    <?php if ($dep['payout_method']==='bank'): ?>
    <div style="grid-column:1/-1;"><div style="font-size:0.72rem;color:#9CA3AF;">Akaun Bank</div>
      <div style="font-weight:600;"><?= h($dep['bank_name']) ?> | <?= h($dep['bank_account']) ?> | <?= h($dep['bank_account_name']) ?></div></div>
    <?php endif; ?>
    <?php if ($dep['user_notes']): ?>
    <div style="grid-column:1/-1;"><div style="font-size:0.72rem;color:#9CA3AF;">Catatan Pelanggan</div><div><?= h($dep['user_notes']) ?></div></div>
    <?php endif; ?>
    <?php if ($dep['admin_notes']): ?>
    <div style="grid-column:1/-1;"><div style="font-size:0.72rem;color:#9CA3AF;">Nota Admin</div><div style="color:#6B7280;"><?= h($dep['admin_notes']) ?></div></div>
    <?php endif; ?>
  </div>

  <!-- Actions -->
  <div style="border-top:1px solid #F3F4F6;padding-top:16px;display:flex;gap:10px;flex-wrap:wrap;" x-data="{}">

    <?php if ($dep['status'] === 'pending'): ?>
    <!-- Verify -->
    <div x-data="{open:false}">
      <button @click="open=!open" class="btn-gold">✅ Sahkan & Aktifkan</button>
      <div x-show="open" style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:16px;margin-top:10px;max-width:420px;">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action"  value="verify">
          <input type="hidden" name="dep_id"  value="<?= $dep['id'] ?>">
          <label class="form-label" style="font-size:0.78rem;">Nota Admin (pilihan)</label>
          <input type="text" name="admin_notes" class="form-input" placeholder="Contoh: Disahkan di kaunter pada..." style="margin-bottom:10px;">
          <button type="submit" class="btn-gold btn-sm">Sahkan Deposit</button>
        </form>
      </div>
    </div>
    <div x-data="{open:false}">
      <button @click="open=!open" class="btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;border-radius:8px;padding:10px 18px;cursor:pointer;">❌ Batal</button>
      <div x-show="open" style="background:#FEF2F2;border:1px solid #FCA5A5;border-radius:10px;padding:14px;margin-top:10px;max-width:360px;">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action"  value="reject_deposit">
          <input type="hidden" name="dep_id"  value="<?= $dep['id'] ?>">
          <label class="form-label" style="font-size:0.78rem;">Sebab Pembatalan</label>
          <input type="text" name="rejection_reason" class="form-input" placeholder="Masukkan sebab..." required style="margin-bottom:8px;">
          <button type="submit" class="btn-sm" style="background:#EF4444;color:#fff;border:none;border-radius:6px;padding:8px 16px;cursor:pointer;">Batalkan</button>
        </form>
      </div>
    </div>

    <?php elseif (in_array($dep['status'], ['active','pawned'])): ?>
    <!-- Pay Interest -->
    <div x-data="{open:false}">
      <button @click="open=!open" class="btn-gold">💰 Rekod Bayaran Faedah</button>
      <div x-show="open" style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:16px;margin-top:10px;max-width:460px;">
        <?php
          $rate_disp = (float)$dep['interest_rate_snapshot'];
          $g         = (float)$dep['gold_grams'];
          $monthly_g = round($g * ($rate_disp / 100), 6);
          $annual_g  = round($g * ($rate_disp * 12 / 100), 6);
          $p_now     = get_active_gold_price();
          $pg        = $p_now ? (float)$p_now['price_per_g'] : 0;
        ?>
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;padding:10px;margin-bottom:12px;font-size:0.82rem;">
          <strong>Faedah <?= gold_format_grams($dep['gold_grams']) ?> @ <?= $dep['interest_rate_snapshot'] ?>%/bln:</strong><br>
          Bulanan: <strong><?= number_format($monthly_g,6) ?>g</strong><?= $pg>0?' (≈RM '.number_format($monthly_g*$pg,2).')':'' ?><br>
          Tahunan: <strong><?= number_format($annual_g,6) ?>g</strong><?= $pg>0?' (≈RM '.number_format($annual_g*$pg,2).')':'' ?>
        </div>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="pay_interest">
          <input type="hidden" name="dep_id" value="<?= $dep['id'] ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
              <label class="form-label" style="font-size:0.78rem;">Label Tempoh</label>
              <input type="text" name="period_label" class="form-input" placeholder="cth: Jan 2025, Q1-2025"
                     value="<?= date('M Y') ?>" required>
            </div>
            <div>
              <label class="form-label" style="font-size:0.78rem;">Jenis Tempoh</label>
              <select name="period_type" class="form-input">
                <option value="monthly">Bulanan</option>
                <option value="annually">Tahunan</option>
              </select>
            </div>
          </div>
          <div style="margin-bottom:10px;">
            <label class="form-label" style="font-size:0.78rem;">Nota (pilihan)</label>
            <input type="text" name="admin_notes" class="form-input" placeholder="Nota bayaran...">
          </div>
          <div style="font-size:0.78rem;color:#92400E;margin-bottom:10px;">
            <?php if ($dep['payout_method']==='wallet'): ?>
            ✅ Faedah akan dikreditkan terus ke wallet digital pengguna.
            <?php else: ?>
            🏦 Faedah dicatat sebagai dibayar melalui pindahan bank ke akaun <?= h($dep['bank_name']) ?> <?= h($dep['bank_account']) ?>.
            <?php endif; ?>
          </div>
          <button type="submit" class="btn-gold btn-sm">Rekod Bayaran</button>
        </form>
      </div>
    </div>

    <?php if ($dep['status']==='active'): ?>
    <!-- Approve redemption -->
    <form method="POST" onsubmit="return confirm('Tandakan deposit ini sebagai ditebus? Emas boleh diserahkan kepada pelanggan.')">
      <?= csrf_field() ?>
      <input type="hidden" name="action"  value="redeem_deposit">
      <input type="hidden" name="dep_id"  value="<?= $dep['id'] ?>">
      <button type="submit" class="btn-sm" style="background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE;border-radius:8px;padding:10px 18px;cursor:pointer;">↩️ Tebus Deposit</button>
    </form>
    <?php endif; ?>
    <?php endif; ?>

  </div>
</div>

<!-- Interest history -->
<?php if (!empty($interest_history)): ?>
<div class="card-kasih" style="margin-bottom:20px;">
  <div class="section-title" style="margin-bottom:12px;">📜 Rekod Pembayaran Faedah</div>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Tempoh</th><th>Jenis</th><th>Gram Deposit</th><th>Kadar</th><th>Faedah Gram</th><th>Faedah RM</th><th>Kaedah</th><th>Status</th><th>Tarikh Bayar</th></tr></thead>
      <tbody>
        <?php foreach ($interest_history as $ih): ?>
        <tr>
          <td style="font-weight:600;"><?= h($ih['period_label']) ?></td>
          <td style="font-size:0.8rem;"><?= $ih['period_type']==='annually'?'Tahunan':'Bulanan' ?></td>
          <td><?= number_format((float)$ih['grams_deposited'],3) ?>g</td>
          <td><?= $ih['interest_rate'] ?>%</td>
          <td style="font-weight:600;color:#B45309;"><?= $ih['interest_grams'] ? number_format((float)$ih['interest_grams'],6).'g' : '—' ?></td>
          <td style="font-weight:600;">RM <?= number_format((float)$ih['interest_rm'],2) ?></td>
          <td style="font-size:0.8rem;"><?= $ih['payout_method']==='wallet'?'💰 Wallet':'🏦 Bank' ?></td>
          <td><span style="background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:999px;font-size:0.72rem;font-weight:600;">Dibayar</span></td>
          <td style="font-size:0.75rem;color:#9CA3AF;"><?= $ih['paid_at'] ? format_date($ih['paid_at'],'d M Y') : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php endif; // end single view ?>

<!-- Filter -->
<div class="card-kasih" style="margin-bottom:16px;">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label class="form-label" style="font-size:0.78rem;">Status</label>
      <select name="status" class="form-input" style="min-width:150px;">
        <option value="">Semua</option>
        <option value="pending"   <?= $status_filter==='pending'  ?'selected':''?>>⏳ Menunggu</option>
        <option value="active"    <?= $status_filter==='active'   ?'selected':''?>>✅ Aktif</option>
        <option value="pawned"    <?= $status_filter==='pawned'   ?'selected':''?>>🔒 Digadai</option>
        <option value="redeemed"  <?= $status_filter==='redeemed' ?'selected':''?>>↩️ Ditebus</option>
        <option value="cancelled" <?= $status_filter==='cancelled'?'selected':''?>>❌ Batal</option>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Cari</label>
      <input type="text" name="q" class="form-input" value="<?= h($q) ?>" placeholder="Nama / e-mel / Ref..." style="min-width:180px;">
    </div>
    <button type="submit" class="btn-gold btn-sm">Tapis</button>
    <a href="<?= APP_URL ?>/admin/gold-deposit" class="btn-gold-outline btn-sm">Reset</a>
  </form>
</div>

<!-- Table -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="section-title" style="margin:0;">Jumlah: <?= $total ?> deposit</div>
  </div>
  <?php if (empty($deposits)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:24px 0;">Tiada deposit dijumpai.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Ref</th><th>Pengguna</th><th>Gram</th><th>Purity/Jenis</th><th>Kadar</th><th>Payout</th><th>Status</th><th>Tarikh</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($deposits as $d): ?>
        <tr style="<?= $d['status']==='pending'?'background:#FFFBEB;':($d['status']==='active'?'background:#F0FDF4;':'') ?>">
          <td style="font-size:0.8rem;font-weight:600;font-family:monospace;"><?= h($d['deposit_ref']) ?></td>
          <td>
            <div style="font-weight:600;font-size:0.85rem;"><?= h($d['user_name']) ?></div>
            <div style="font-size:0.72rem;color:#9CA3AF;"><?= h($d['user_email']) ?></div>
          </td>
          <td style="font-weight:700;"><?= gold_format_grams($d['gold_grams']) ?></td>
          <td style="font-size:0.8rem;"><?= h($d['gold_purity']) ?> / <?= $gold_type_labels[$d['gold_type']] ?? h($d['gold_type']) ?></td>
          <td style="font-size:0.82rem;"><?= $d['interest_rate_snapshot'] > 0 ? $d['interest_rate_snapshot'].'%/bln' : '—' ?></td>
          <td style="font-size:0.8rem;"><?= $d['payout_method']==='wallet'?'💰 Wallet':'🏦 Bank' ?></td>
          <td><span class="dep-badge-<?= $d['status'] ?>"><?= ['pending'=>'⏳ Menunggu','active'=>'✅ Aktif','pawned'=>'🔒 Digadai','redeemed'=>'↩️ Ditebus','cancelled'=>'❌ Batal'][$d['status']] ?></span></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($d['created_at'],'d M Y') ?></td>
          <td><a href="<?= APP_URL ?>/admin/gold-deposit?view=<?= $d['id'] ?>" class="btn-gold-outline btn-sm">Semak</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>

<?php layout_end_admin(); ?>
