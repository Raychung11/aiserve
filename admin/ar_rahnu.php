<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_role(['super_admin'], '/login');

$db       = getDB();
$admin_id = auth_id();

// ── Handle actions ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $app_id = (int)($_POST['app_id'] ?? 0);

    $as = $db->prepare("SELECT * FROM ar_rahnu_applications WHERE id=?"); $as->execute([$app_id]); $app = $as->fetch();
    if (!$app) { flash_set('main','Permohonan tidak dijumpai.','error'); redirect(APP_URL.'/admin/ar-rahnu'); }

    if ($action === 'approve' && $app['status'] === 'pending') {
        $fin_approved  = (float)($_POST['financing_approved'] ?? 0);
        $ujrah_rate    = (float)($_POST['monthly_ujrah_rate'] ?? 0.75);
        $tenure        = (int)($_POST['tenure_months'] ?? 6);
        $admin_notes   = trim($_POST['admin_notes'] ?? '');
        $maturity      = date('Y-m-d', strtotime("+{$tenure} months"));
        $total_ujrah   = round($fin_approved * ($ujrah_rate / 100) * $tenure, 2);

        if ($fin_approved <= 0) { flash_set('main','Masukkan jumlah pembiayaan diluluskan.','error'); redirect(APP_URL.'/admin/ar-rahnu?view='.$app_id); }

        $db->prepare("UPDATE ar_rahnu_applications SET
            status='approved', financing_approved=?, monthly_ujrah_rate=?, tenure_months=?,
            total_ujrah=?, maturity_date=?, admin_notes=?, reviewed_by=?, reviewed_at=NOW(), updated_at=NOW()
            WHERE id=?")
           ->execute([$fin_approved, $ujrah_rate, $tenure, $total_ujrah, $maturity, $admin_notes, $admin_id, $app_id]);
        flash_set('main', 'Permohonan Ar Rahnu #'.$app_id.' diluluskan.', 'success');
    }

    elseif ($action === 'disburse' && $app['status'] === 'approved') {
        $db->prepare("UPDATE ar_rahnu_applications SET status='active', disbursed_at=NOW(), updated_at=NOW() WHERE id=?")
           ->execute([$app_id]);
        flash_set('main', 'Permohonan #'.$app_id.' ditandakan aktif (wang telah dibayar).', 'success');
    }

    elseif ($action === 'redeem' && $app['status'] === 'active') {
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE ar_rahnu_applications SET status='redeemed', redeemed_at=NOW(), updated_at=NOW() WHERE id=?")->execute([$app_id]);
            if ($app['deposit_id']) {
                $db->prepare("UPDATE gold_deposits SET status='active', updated_at=NOW() WHERE id=?")->execute([$app['deposit_id']]);
            }
            $db->commit();
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
        flash_set('main', 'Permohonan #'.$app_id.' ditandakan ditebus. Emas pengguna dilepaskan.', 'success');
    }

    elseif ($action === 'default' && $app['status'] === 'active') {
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE ar_rahnu_applications SET status='defaulted', updated_at=NOW() WHERE id=?")->execute([$app_id]);
            if ($app['deposit_id']) {
                // Deposit forfeited — mark as redeemed (company takes possession)
                $db->prepare("UPDATE gold_deposits SET status='redeemed', redeemed_at=NOW(), admin_notes='Dilucuthak atas kemungkiran Ar Rahnu', updated_at=NOW() WHERE id=?")->execute([$app['deposit_id']]);
            }
            $db->commit();
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
        flash_set('main', 'Permohonan #'.$app_id.' ditandakan tamat tempoh/mungkir.', 'success');
    }

    elseif ($action === 'reject' && $app['status'] === 'pending') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        if (!$reason) { flash_set('main','Sila masukkan sebab penolakan.','error'); redirect(APP_URL.'/admin/ar-rahnu?view='.$app_id); }
        $db->prepare("UPDATE ar_rahnu_applications SET status='rejected', rejection_reason=?, reviewed_by=?, reviewed_at=NOW(), updated_at=NOW() WHERE id=?")
           ->execute([$reason, $admin_id, $app_id]);
        flash_set('main', 'Permohonan #'.$app_id.' ditolak.', 'success');
    }

    redirect(APP_URL . '/admin/ar-rahnu');
}

// ── Single view ───────────────────────────────────────────────────────────────
$view_id  = (int)($_GET['view'] ?? 0);
$view_app = null;
if ($view_id) {
    $vs = $db->prepare("SELECT ara.*, u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone, gd.deposit_ref FROM ar_rahnu_applications ara JOIN users u ON u.id=ara.user_id LEFT JOIN gold_deposits gd ON gd.id=ara.deposit_id WHERE ara.id=?");
    $vs->execute([$view_id]);
    $view_app = $vs->fetch();
}

// ── List ──────────────────────────────────────────────────────────────────────
$status_filter = $_GET['status'] ?? '';
$q             = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per           = 20;

$where  = '1=1';
$params = [];
if ($status_filter) { $where .= ' AND ara.status=?'; $params[] = $status_filter; }
if ($q)             { $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

$ct = $db->prepare("SELECT COUNT(*) FROM ar_rahnu_applications ara JOIN users u ON u.id=ara.user_id WHERE $where");
$ct->execute($params);
$total = (int)$ct->fetchColumn();
$pag   = paginate($total, $per, $page, APP_URL.'/admin/ar-rahnu?status='.urlencode($status_filter).'&q='.urlencode($q).'&page={page}');

$list_stmt = $db->prepare("SELECT ara.*, u.full_name AS user_name, u.email AS user_email FROM ar_rahnu_applications ara JOIN users u ON u.id=ara.user_id WHERE $where ORDER BY ara.created_at DESC LIMIT ?,?");
$list_stmt->execute(array_merge($params, [$pag['offset'], $per]));
$applications = $list_stmt->fetchAll();

// Stats
$st = $db->query("SELECT
    COUNT(*) AS total,
    SUM(status='pending')   AS pending,
    SUM(status='approved')  AS approved,
    SUM(status='active')    AS active,
    SUM(status='redeemed')  AS redeemed,
    SUM(status='defaulted') AS defaulted,
    COALESCE(SUM(CASE WHEN status IN ('active','redeemed') THEN financing_approved ELSE 0 END),0) AS total_disbursed
FROM ar_rahnu_applications")->fetch();

layout_begin_admin('Ar Rahnu — Admin');
?>

<div class="page-title">🕌 Pengurusan Ar Rahnu</div>
<?= flash_html('main') ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px;">
  <div class="stat-card"><div class="stat-value"><?= (int)$st['total'] ?></div><div class="stat-label">Jumlah Permohonan</div></div>
  <div class="stat-card stat-card-blue" style="<?= (int)$st['pending']>0?'border-left:3px solid #F59E0B;':''?>">
    <div class="stat-value" style="color:<?= (int)$st['pending']>0?'#D97706':'inherit'?>;"><?= (int)$st['pending'] ?></div>
    <div class="stat-label">Menunggu</div>
  </div>
  <div class="stat-card stat-card-purple"><div class="stat-value"><?= (int)$st['approved'] ?></div><div class="stat-label">Diluluskan</div></div>
  <div class="stat-card" style="background:#EFF6FF;"><div class="stat-value" style="color:#1E40AF;"><?= (int)$st['active'] ?></div><div class="stat-label">Aktif</div></div>
  <div class="stat-card stat-card-green"><div class="stat-value"><?= (int)$st['redeemed'] ?></div><div class="stat-label">Ditebus</div></div>
  <div class="stat-card" style="background:#FEF2F2;"><div class="stat-value" style="color:#991B1B;"><?= (int)$st['defaulted'] ?></div><div class="stat-label">Mungkir</div></div>
  <div class="stat-card" style="background:#F0FDF4;">
    <div class="stat-value" style="color:#065F46;font-size:1rem;">RM <?= number_format((float)$st['total_disbursed'],2) ?></div>
    <div class="stat-label">Jumlah Disbursed</div>
  </div>
</div>

<?php if ($view_app): ?>
<!-- ── Detail View ── -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div class="section-title" style="margin:0;">🕌 Permohonan #<?= $view_app['id'] ?> — <?= status_badge($view_app['status']) ?></div>
    <a href="<?= APP_URL ?>/admin/ar-rahnu" class="btn-gold-outline btn-sm">← Kembali</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Nama Pengguna</div><div style="font-weight:600;"><?= h($view_app['user_name']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">E-mel / Telefon</div><div style="font-weight:600;"><?= h($view_app['user_email']) ?> / <?= h($view_app['user_phone'] ?: '—') ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Jenis Cagaran</div>
      <div><?php if (($view_app['collateral_type']??'digital')==='physical' && $view_app['deposit_ref']): ?>
        <span style="background:#DBEAFE;color:#1E40AF;padding:3px 10px;border-radius:999px;font-size:0.8rem;font-weight:700;">🏦 Deposit Fizikal</span>
        <a href="<?= APP_URL ?>/admin/gold-deposit?view=<?= $view_app['deposit_id'] ?>" style="font-size:0.78rem;color:var(--gold-dark);margin-left:6px;"><?= h($view_app['deposit_ref']) ?> →</a>
      <?php else: ?>
        <span style="background:#FEF3C7;color:#92400E;padding:3px 10px;border-radius:999px;font-size:0.8rem;font-weight:700;">💰 Emas Digital</span>
      <?php endif; ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Gram Emas Digadai</div><div style="font-weight:700;"><?= gold_format_grams($view_app['gold_grams']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Gold Points</div><div style="font-weight:700;color:var(--gold-dark);"><?= ($view_app['gold_points']>0)?gold_format_points($view_app['gold_points']).' pts':'—' ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Ketulenan</div><div style="font-weight:600;"><?= h($view_app['gold_purity']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Nilai Pasaran (snap)</div><div style="font-weight:700;">RM <?= number_format((float)$view_app['market_value_snapshot'],2) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Pembiayaan Dipohon</div><div style="font-weight:700;color:#1E40AF;">RM <?= number_format((float)$view_app['financing_requested'],2) ?></div></div>
    <?php if ($view_app['financing_approved']): ?>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Pembiayaan Diluluskan</div><div style="font-weight:700;color:#065F46;">RM <?= number_format((float)$view_app['financing_approved'],2) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Kadar Ujrah / Bulan</div><div style="font-weight:600;"><?= $view_app['monthly_ujrah_rate'] ?>%</div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Tempoh</div><div style="font-weight:600;"><?= $view_app['tenure_months'] ?> bulan</div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Jumlah Ujrah</div><div style="font-weight:700;color:#92400E;">RM <?= number_format((float)$view_app['total_ujrah'],2) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Tarikh Matang</div><div style="font-weight:600;"><?= $view_app['maturity_date'] ?></div></div>
    <?php endif; ?>
    <div style="grid-column:1/-1;"><div style="font-size:0.75rem;color:#9CA3AF;">Akaun Bank</div>
      <div style="font-weight:600;"><?= h($view_app['bank_name']) ?> | <?= h($view_app['bank_account']) ?> | <?= h($view_app['bank_account_name']) ?></div></div>
    <?php if ($view_app['user_notes']): ?>
    <div style="grid-column:1/-1;"><div style="font-size:0.75rem;color:#9CA3AF;">Catatan Pemohon</div><div><?= h($view_app['user_notes']) ?></div></div>
    <?php endif; ?>
    <?php if ($view_app['admin_notes']): ?>
    <div style="grid-column:1/-1;"><div style="font-size:0.75rem;color:#9CA3AF;">Nota Admin</div><div style="color:#6B7280;"><?= h($view_app['admin_notes']) ?></div></div>
    <?php endif; ?>
    <?php if ($view_app['rejection_reason']): ?>
    <div style="grid-column:1/-1;"><div style="font-size:0.75rem;color:#9CA3AF;">Sebab Penolakan</div><div style="color:#991B1B;font-weight:600;"><?= h($view_app['rejection_reason']) ?></div></div>
    <?php endif; ?>
  </div>

  <!-- Actions -->
  <div style="border-top:1px solid #F3F4F6;padding-top:16px;display:flex;gap:12px;flex-wrap:wrap;">

    <?php if ($view_app['status'] === 'pending'): ?>
    <!-- Approve with terms -->
    <div x-data="{open:false}">
      <button @click="open=!open" class="btn-gold">✅ Luluskan</button>
      <div x-show="open" style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:16px;margin-top:12px;width:100%;">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action"  value="approve">
          <input type="hidden" name="app_id"  value="<?= $view_app['id'] ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
              <label class="form-label" style="font-size:0.78rem;">Pembiayaan Diluluskan (RM)</label>
              <input type="number" name="financing_approved" class="form-input" step="0.01" min="1"
                     value="<?= number_format((float)$view_app['financing_requested']*0.70,2,'.','') ?>" required>
            </div>
            <div>
              <label class="form-label" style="font-size:0.78rem;">Kadar Ujrah (%/bulan)</label>
              <input type="number" name="monthly_ujrah_rate" class="form-input" step="0.01" min="0" max="5" value="0.75">
            </div>
            <div>
              <label class="form-label" style="font-size:0.78rem;">Tempoh (bulan)</label>
              <select name="tenure_months" class="form-input">
                <?php foreach ([3,6,9,12] as $m): ?>
                  <option value="<?= $m ?>" <?= $m===6?'selected':''?>><?= $m ?> bulan</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div style="margin-bottom:12px;">
            <label class="form-label" style="font-size:0.78rem;">Nota Admin (pilihan)</label>
            <input type="text" name="admin_notes" class="form-input" placeholder="Nota untuk rekod...">
          </div>
          <button type="submit" class="btn-gold btn-sm">Sahkan Kelulusan</button>
        </form>
      </div>
    </div>

    <!-- Reject -->
    <div x-data="{open:false}">
      <button @click="open=!open" class="btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;border-radius:8px;padding:10px 20px;cursor:pointer;">❌ Tolak</button>
      <div x-show="open" style="background:#FEF2F2;border:1px solid #FCA5A5;border-radius:10px;padding:16px;margin-top:12px;max-width:400px;">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action"  value="reject">
          <input type="hidden" name="app_id"  value="<?= $view_app['id'] ?>">
          <label class="form-label" style="font-size:0.78rem;">Sebab Penolakan</label>
          <input type="text" name="rejection_reason" class="form-input" placeholder="Masukkan sebab..." required style="margin-bottom:8px;">
          <button type="submit" class="btn-sm" style="background:#EF4444;color:#fff;border:none;border-radius:6px;padding:8px 16px;cursor:pointer;">Tolak Permohonan</button>
        </form>
      </div>
    </div>

    <?php elseif ($view_app['status'] === 'approved'): ?>
    <form method="post" onsubmit="return confirm('Tandakan sebagai aktif? Ini bermakna wang telah dibayar kepada pemohon.')">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="disburse">
      <input type="hidden" name="app_id" value="<?= $view_app['id'] ?>">
      <button class="btn-gold" style="padding:10px 24px;">💳 Tandakan Aktif (Disbursed)</button>
    </form>

    <?php elseif ($view_app['status'] === 'active'): ?>
    <form method="post" onsubmit="return confirm('Tandakan sebagai ditebus? Emas pengguna akan dilepaskan.')">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="redeem">
      <input type="hidden" name="app_id" value="<?= $view_app['id'] ?>">
      <button class="btn-gold" style="padding:10px 24px;">🔓 Tebus (Emas Dilepas)</button>
    </form>
    <form method="post" onsubmit="return confirm('Tandakan sebagai mungkir/tamat tempoh? Emas tidak akan dikembalikan.')">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="default">
      <input type="hidden" name="app_id" value="<?= $view_app['id'] ?>">
      <button class="btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;border-radius:8px;padding:10px 20px;cursor:pointer;">⚠️ Mungkir / Tamat Tempoh</button>
    </form>
    <?php endif; ?>

  </div>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="card-kasih" style="margin-bottom:16px;">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label class="form-label" style="font-size:0.78rem;">Status</label>
      <select name="status" class="form-input" style="min-width:150px;">
        <option value="">Semua Status</option>
        <option value="pending"   <?= $status_filter==='pending'  ?'selected':''?>>⏳ Menunggu</option>
        <option value="approved"  <?= $status_filter==='approved' ?'selected':''?>>✅ Diluluskan</option>
        <option value="active"    <?= $status_filter==='active'   ?'selected':''?>>🔵 Aktif</option>
        <option value="redeemed"  <?= $status_filter==='redeemed' ?'selected':''?>>🔓 Ditebus</option>
        <option value="defaulted" <?= $status_filter==='defaulted'?'selected':''?>>⚠️ Mungkir</option>
        <option value="rejected"  <?= $status_filter==='rejected' ?'selected':''?>>❌ Ditolak</option>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Cari Pengguna</label>
      <input type="text" name="q" class="form-input" value="<?= h($q) ?>" placeholder="Nama / e-mel..." style="min-width:180px;">
    </div>
    <button type="submit" class="btn-gold btn-sm">Tapis</button>
    <a href="<?= APP_URL ?>/admin/ar-rahnu" class="btn-gold-outline btn-sm">Reset</a>
  </form>
</div>

<!-- List Table -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="section-title" style="margin:0;">Jumlah: <?= $total ?> permohonan</div>
  </div>
  <?php if (empty($applications)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:24px 0;">Tiada permohonan dijumpai.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>#ID</th><th>Pengguna</th><th>Gram Emas</th><th>Nilai RM</th><th>Dipohon</th><th>Diluluskan</th><th>Tempoh</th><th>Status</th><th>Tarikh</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($applications as $a): ?>
        <tr style="<?= $a['status']==='pending'?'background:#FFFBEB;':($a['status']==='active'?'background:#EFF6FF;':'') ?>">
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $a['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.85rem;"><?= h($a['user_name']) ?></div>
            <div style="font-size:0.72rem;color:#9CA3AF;"><?= h($a['user_email']) ?></div>
          </td>
          <td style="font-weight:600;"><?= gold_format_grams($a['gold_grams']) ?></td>
          <td>RM <?= number_format((float)$a['market_value_snapshot'],2) ?></td>
          <td style="font-weight:700;color:#1E40AF;">RM <?= number_format((float)$a['financing_requested'],2) ?></td>
          <td style="font-weight:700;color:#065F46;"><?= $a['financing_approved'] ? 'RM '.number_format((float)$a['financing_approved'],2) : '—' ?></td>
          <td style="font-size:0.82rem;">
            <?= $a['tenure_months'] ? $a['tenure_months'].' bln' : '—' ?>
            <?php if ($a['maturity_date']): ?>
              <br><span style="font-size:0.7rem;color:#9CA3AF;"><?= $a['maturity_date'] ?></span>
            <?php endif; ?>
          </td>
          <td><?= status_badge($a['status']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($a['created_at']) ?></td>
          <td><a href="<?= APP_URL ?>/admin/ar-rahnu?view=<?= $a['id'] ?>" class="btn-gold-outline btn-sm">Semak</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>

<?php layout_end_admin(); ?>
