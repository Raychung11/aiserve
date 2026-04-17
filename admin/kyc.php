<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
auth_require_role(['super_admin'], '/login');

$db = getDB();

// ── Handle actions ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $kyc_id = (int)($_POST['kyc_id'] ?? 0);
    $admin_id = auth_id();

    $kyc_stmt = $db->prepare("SELECT * FROM kyc_submissions WHERE id = ?");
    $kyc_stmt->execute([$kyc_id]);
    $kyc = $kyc_stmt->fetch();

    if (!$kyc) {
        flash_set('main', 'Permohonan tidak dijumpai.', 'error');
        redirect(APP_URL . '/admin/kyc');
    }

    if ($action === 'approve' && $kyc['status'] === 'pending') {
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE kyc_submissions SET status='approved', reviewed_by=?, reviewed_at=NOW(), updated_at=NOW() WHERE id=?")
               ->execute([$admin_id, $kyc_id]);
            $db->prepare("UPDATE users SET kyc_verified_at=NOW(), updated_at=NOW() WHERE id=?")
               ->execute([$kyc['user_id']]);
            $db->commit();
            flash_set('main', 'eKYC #' . $kyc_id . ' telah diluluskan.', 'success');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash_set('main', 'Ralat: ' . $e->getMessage(), 'error');
        }
    } elseif ($action === 'reject' && $kyc['status'] === 'pending') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        if (!$reason) {
            flash_set('main', 'Sila masukkan sebab penolakan.', 'error');
            redirect(APP_URL . '/admin/kyc?view=' . $kyc_id);
        }
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE kyc_submissions SET status='rejected', rejection_reason=?, reviewed_by=?, reviewed_at=NOW(), updated_at=NOW() WHERE id=?")
               ->execute([$reason, $admin_id, $kyc_id]);
            $db->prepare("UPDATE users SET kyc_verified_at=NULL, updated_at=NOW() WHERE id=?")
               ->execute([$kyc['user_id']]);
            $db->commit();
            flash_set('main', 'eKYC #' . $kyc_id . ' telah ditolak.', 'success');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash_set('main', 'Ralat: ' . $e->getMessage(), 'error');
        }
    }

    redirect(APP_URL . '/admin/kyc');
}

// ── Single view ───────────────────────────────────────────────────────────────
$view_id = (int)($_GET['view'] ?? 0);
$view_kyc = null;
if ($view_id) {
    $vs = $db->prepare("SELECT ks.*, u.email, u.full_name AS user_full_name FROM kyc_submissions ks JOIN users u ON u.id=ks.user_id WHERE ks.id=?");
    $vs->execute([$view_id]);
    $view_kyc = $vs->fetch();
}

// ── List ──────────────────────────────────────────────────────────────────────
$status_filter = $_GET['status'] ?? '';
$q             = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per           = 20;

$where  = '1=1';
$params = [];
if ($status_filter) { $where .= ' AND ks.status=?'; $params[] = $status_filter; }
if ($q)             { $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR ks.ic_number LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }

$ct = $db->prepare("SELECT COUNT(*) FROM kyc_submissions ks JOIN users u ON u.id=ks.user_id WHERE $where");
$ct->execute($params);
$total = (int)$ct->fetchColumn();

$pag = paginate($total, $per, $page, APP_URL . '/admin/kyc?status=' . urlencode($status_filter) . '&q=' . urlencode($q) . '&page={page}');

$list_stmt = $db->prepare("SELECT ks.*, u.email, u.full_name AS user_full_name FROM kyc_submissions ks JOIN users u ON u.id=ks.user_id WHERE $where ORDER BY ks.submitted_at DESC LIMIT ?,?");
$list_stmt->execute(array_merge($params, [$pag['offset'], $per]));
$submissions = $list_stmt->fetchAll();

// Stats
$stats_stmt = $db->query("SELECT
    COUNT(*) AS total,
    SUM(status='pending')  AS pending,
    SUM(status='approved') AS approved,
    SUM(status='rejected') AS rejected
FROM kyc_submissions");
$stats = $stats_stmt->fetch();

layout_begin_admin('eKYC — Pengesahan Identiti');
?>

<div class="page-title">🪪 Pengesahan eKYC</div>
<?= flash_html('main') ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px;">
  <div class="stat-card"><div class="stat-value"><?= (int)$stats['total'] ?></div><div class="stat-label">Jumlah Permohonan</div></div>
  <div class="stat-card stat-card-blue" style="<?= (int)$stats['pending']>0?'border-left:3px solid #F59E0B;':'' ?>">
    <div class="stat-value" style="color:<?= (int)$stats['pending']>0?'#D97706':'inherit' ?>;"><?= (int)$stats['pending'] ?></div>
    <div class="stat-label">Menunggu Semakan</div>
  </div>
  <div class="stat-card stat-card-green"><div class="stat-value"><?= (int)$stats['approved'] ?></div><div class="stat-label">Diluluskan</div></div>
  <div class="stat-card" style="background:#FEF2F2;"><div class="stat-value" style="color:#991B1B;"><?= (int)$stats['rejected'] ?></div><div class="stat-label">Ditolak</div></div>
</div>

<?php if ($view_kyc): ?>
<!-- ── Detail View ── -->
<div class="card-kasih" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div class="section-title" style="margin:0;">📋 Permohonan #<?= $view_kyc['id'] ?> — <?= status_badge($view_kyc['status']) ?></div>
    <a href="<?= APP_URL ?>/admin/kyc" class="btn-gold-outline btn-sm">← Kembali</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Nama Pengguna</div><div style="font-weight:600;"><?= h($view_kyc['user_full_name']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">E-mel</div><div style="font-weight:600;"><?= h($view_kyc['email']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Nama Penuh (IC)</div><div style="font-weight:600;"><?= h($view_kyc['full_name']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">No. IC</div><div style="font-weight:600;letter-spacing:0.05em;"><?= h($view_kyc['ic_number']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Tarikh Lahir</div><div style="font-weight:600;"><?= h($view_kyc['date_of_birth']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">No. Telefon</div><div style="font-weight:600;"><?= h($view_kyc['phone'] ?: '—') ?></div></div>
    <div style="grid-column:1/-1;"><div style="font-size:0.75rem;color:#9CA3AF;">Alamat</div>
      <div style="font-weight:600;"><?= h($view_kyc['address']) ?><br><?= h($view_kyc['postcode']) ?> <?= h($view_kyc['city']) ?>, <?= h($view_kyc['state']) ?></div></div>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Tarikh Hantar</div><div><?= format_date($view_kyc['submitted_at']) ?></div></div>
    <?php if ($view_kyc['reviewed_at']): ?>
    <div><div style="font-size:0.75rem;color:#9CA3AF;">Tarikh Semak</div><div><?= format_date($view_kyc['reviewed_at']) ?></div></div>
    <?php endif; ?>
    <?php if ($view_kyc['rejection_reason']): ?>
    <div style="grid-column:1/-1;">
      <div style="font-size:0.75rem;color:#9CA3AF;">Sebab Penolakan</div>
      <div style="color:#991B1B;font-weight:600;"><?= h($view_kyc['rejection_reason']) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- IC Images -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <div>
      <div style="font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:8px;">🪪 IC Hadapan</div>
      <a href="<?= APP_URL ?>/admin/kyc-image.php?f=<?= h($view_kyc['ic_front']) ?>" target="_blank">
        <img src="<?= APP_URL ?>/admin/kyc-image.php?f=<?= h($view_kyc['ic_front']) ?>" alt="IC Front"
             style="width:100%;border-radius:8px;border:1px solid #E5E7EB;cursor:zoom-in;">
      </a>
    </div>
    <div>
      <div style="font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:8px;">🪪 IC Belakang</div>
      <a href="<?= APP_URL ?>/admin/kyc-image.php?f=<?= h($view_kyc['ic_back']) ?>" target="_blank">
        <img src="<?= APP_URL ?>/admin/kyc-image.php?f=<?= h($view_kyc['ic_back']) ?>" alt="IC Back"
             style="width:100%;border-radius:8px;border:1px solid #E5E7EB;cursor:zoom-in;">
      </a>
    </div>
  </div>

  <!-- Actions -->
  <?php if ($view_kyc['status'] === 'pending'): ?>
  <div style="display:flex;gap:12px;flex-wrap:wrap;border-top:1px solid #F3F4F6;padding-top:16px;">
    <form method="POST" onsubmit="return confirm('Luluskan permohonan eKYC ini?')">
      <?= csrf_field() ?>
      <input type="hidden" name="action"  value="approve">
      <input type="hidden" name="kyc_id"  value="<?= $view_kyc['id'] ?>">
      <button class="btn-gold" style="padding:10px 28px;">✅ Luluskan</button>
    </form>

    <div x-data="{open:false}" style="display:flex;align-items:flex-start;gap:8px;flex-wrap:wrap;">
      <button @click="open=!open" class="btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;border-radius:8px;padding:10px 20px;cursor:pointer;">
        ❌ Tolak
      </button>
      <div x-show="open" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
        <form method="POST" onsubmit="return document.getElementById('rej_reason_<?= $view_kyc['id'] ?>').value.trim() !== '' || (alert('Sila masukkan sebab penolakan.'), false)">
          <?= csrf_field() ?>
          <input type="hidden" name="action"  value="reject">
          <input type="hidden" name="kyc_id"  value="<?= $view_kyc['id'] ?>">
          <div style="display:flex;gap:8px;align-items:flex-end;">
            <div>
              <label class="form-label" style="font-size:0.78rem;">Sebab Penolakan</label>
              <input type="text" id="rej_reason_<?= $view_kyc['id'] ?>" name="rejection_reason"
                     class="form-input" placeholder="Masukkan sebab..." style="min-width:220px;">
            </div>
            <button type="submit" class="btn-sm" style="background:#EF4444;color:#fff;border:none;border-radius:8px;padding:8px 16px;cursor:pointer;">Hantar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Filter ── -->
<div class="card-kasih" style="margin-bottom:16px;">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label class="form-label" style="font-size:0.78rem;">Status</label>
      <select name="status" class="form-input" style="min-width:160px;">
        <option value="">Semua Status</option>
        <option value="pending"  <?= $status_filter==='pending'  ?'selected':'' ?>>⏳ Menunggu</option>
        <option value="approved" <?= $status_filter==='approved' ?'selected':'' ?>>✅ Diluluskan</option>
        <option value="rejected" <?= $status_filter==='rejected' ?'selected':'' ?>>❌ Ditolak</option>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:0.78rem;">Cari Nama / E-mel / IC</label>
      <input type="text" name="q" class="form-input" value="<?= h($q) ?>" placeholder="Cari..." style="min-width:180px;">
    </div>
    <button type="submit" class="btn-gold btn-sm">Tapis</button>
    <a href="<?= APP_URL ?>/admin/kyc" class="btn-gold-outline btn-sm">Reset</a>
  </form>
</div>

<!-- ── List Table ── -->
<div class="card-kasih">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="section-title" style="margin:0;">Jumlah: <?= $total ?> permohonan</div>
  </div>
  <?php if (empty($submissions)): ?>
    <p style="text-align:center;color:#9CA3AF;padding:24px 0;font-size:0.875rem;">Tiada permohonan dijumpai.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table-kasih">
      <thead>
        <tr><th>#ID</th><th>Pengguna</th><th>Nama Penuh (IC)</th><th>No. IC</th><th>Status</th><th>Tarikh Hantar</th><th>Tindakan</th></tr>
      </thead>
      <tbody>
        <?php foreach ($submissions as $s): ?>
        <tr style="<?= $s['status']==='pending' ? 'background:#FFFBEB;' : '' ?>">
          <td style="font-size:0.78rem;color:#9CA3AF;">#<?= $s['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.85rem;"><?= h($s['user_full_name']) ?></div>
            <div style="font-size:0.75rem;color:#9CA3AF;"><?= h($s['email']) ?></div>
          </td>
          <td style="font-size:0.85rem;"><?= h($s['full_name']) ?></td>
          <td style="font-size:0.82rem;font-family:monospace;letter-spacing:0.04em;"><?= h($s['ic_number']) ?></td>
          <td><?= status_badge($s['status']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($s['submitted_at']) ?></td>
          <td>
            <a href="<?= APP_URL ?>/admin/kyc?view=<?= $s['id'] ?>" class="btn-gold-outline btn-sm">🔍 Semak</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
  <?php endif; ?>
</div>

<?php layout_end_admin(); ?>
