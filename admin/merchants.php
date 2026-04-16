<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

$db    = getDB();
$admin = auth_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action    = sanitize_string($_POST['action'] ?? '');
    $merch_id  = (int)($_POST['merchant_id'] ?? 0);
    $notes_txt = sanitize_string($_POST['notes'] ?? '', 500);

    if ($merch_id && in_array($action, ['approve','suspend','reject'])) {
        $new_status = $action === 'approve' ? 'active' : ($action === 'suspend' ? 'suspended' : 'rejected');
        $db->prepare("UPDATE merchants SET status=?, verification_notes=?, approved_by=?, approved_at=? WHERE id=?")
           ->execute([$new_status, $notes_txt, $admin['id'], $new_status==='active'?date('Y-m-d H:i:s'):null, $merch_id]);
        // Also update user status
        $m = $db->prepare("SELECT user_id FROM merchants WHERE id=?"); $m->execute([$merch_id]); $mrow = $m->fetch();
        if ($mrow) $db->prepare("UPDATE users SET status=? WHERE id=?")->execute([$new_status==='active'?'active':'suspended', $mrow['user_id']]);
        audit_log((int)$admin['id'],'super_admin','merchant_'.$action,'merchants',$merch_id,null,['status'=>$new_status,'notes'=>$notes_txt]);
        flash_set('main','Status pedagang berjaya dikemaskini.','success');
        redirect(APP_URL . '/admin/merchants');
    }
}

$status_f = sanitize_string($_GET['status'] ?? '');
$search   = sanitize_string($_GET['q'] ?? '');
$page     = max(1,(int)($_GET['page'] ?? 1));
$per      = 20;

$where  = ['1=1'];
$params = [];
if ($status_f) { $where[] = "m.status=?"; $params[] = $status_f; }
if ($search)   { $where[] = "(m.company_name LIKE ? OR u.email LIKE ? OR m.contact_name LIKE ?)"; $params=array_merge($params,["%$search%","%$search%","%$search%"]); }
$ws = implode(' AND ', $where);

$cnt_stmt = $db->prepare("SELECT COUNT(*) FROM merchants m JOIN users u ON u.id=m.user_id WHERE $ws"); $cnt_stmt->execute($params);
$total = (int)$cnt_stmt->fetchColumn();
$pag   = paginate($total,$per,$page,APP_URL.'/admin/merchants?page={page}'.($status_f?'&status='.$status_f:'').($search?'&q='.urlencode($search):''));

$stmt = $db->prepare("SELECT m.*, u.email, u.full_name, u.created_at AS user_created FROM merchants m JOIN users u ON u.id=m.user_id WHERE $ws ORDER BY m.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$per,$pag['offset']]));
$merchants = $stmt->fetchAll();

layout_begin_admin('Pengurusan Pedagang');
?>
<div class="page-title">🏪 Pengurusan Pedagang</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <a href="<?= APP_URL ?>/admin/merchants" class="<?= !$status_f?'btn-gold':'btn-gold-outline' ?> btn-sm">Semua</a>
  <a href="<?= APP_URL ?>/admin/merchants?status=pending" class="<?= $status_f==='pending'?'btn-gold':'btn-gold-outline' ?> btn-sm">⏳ Tertangguh</a>
  <a href="<?= APP_URL ?>/admin/merchants?status=active" class="<?= $status_f==='active'?'btn-gold':'btn-gold-outline' ?> btn-sm">✅ Aktif</a>
  <a href="<?= APP_URL ?>/admin/merchants?status=suspended" class="<?= $status_f==='suspended'?'btn-gold':'btn-gold-outline' ?> btn-sm">🚫 Digantung</a>
</div>

<?= flash_html('main') ?>

<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Syarikat</th><th>Nama Hubungan</th><th>E-mel</th><th>No. Reg</th><th>Status</th><th>Daftar</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($merchants as $m): ?>
        <tr>
          <td><strong><?= h($m['company_name']) ?></strong></td>
          <td style="font-size:0.82rem;"><?= h($m['contact_name']) ?></td>
          <td style="font-size:0.82rem;"><?= h($m['email']) ?></td>
          <td style="font-size:0.78rem;color:#9CA3AF;"><?= h($m['registration_no'] ?? '—') ?></td>
          <td><?= status_badge($m['status']) ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;"><?= format_date($m['created_at'],'d M Y') ?></td>
          <td>
            <form method="POST" style="display:flex;gap:4px;flex-wrap:wrap;">
              <?= csrf_field() ?>
              <input type="hidden" name="merchant_id" value="<?= $m['id'] ?>">
              <?php if ($m['status'] === 'pending' || $m['status'] === 'suspended' || $m['status'] === 'rejected'): ?>
                <button name="action" value="approve" class="btn-gold btn-sm" style="padding:3px 8px;">✅ Lulus</button>
              <?php endif; ?>
              <?php if ($m['status'] === 'active'): ?>
                <button name="action" value="suspend" class="btn-danger btn-sm" style="padding:3px 8px;" onclick="return confirm('Gantung pedagang ini?')">Gantung</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
</div>
<?php layout_end_admin(); ?>
