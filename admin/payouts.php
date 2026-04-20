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
    $rid    = (int)$_POST['request_id'];
    $action = sanitize_string($_POST['action'] ?? '');
    $note   = sanitize_string($_POST['admin_note'] ?? '', 300);
    if ($rid && in_array($action,['approve','reject','mark_paid'])) {
        $new_status = $action==='approve'?'approved':($action==='mark_paid'?'paid':'rejected');
        $db->prepare("UPDATE merchant_payout_requests SET status=?,admin_note=?,processed_by=?,processed_at=NOW() WHERE id=?")->execute([$new_status,$note,$admin['id'],$rid]);
        audit_log((int)$admin['id'],'super_admin','payout_'.$action,'merchant_payout_requests',$rid,null,['status'=>$new_status]);
        flash_set('main','Permintaan bayaran dikemaskini.','success');
        redirect(APP_URL . '/admin/payouts');
    }
}

$status_f = sanitize_string($_GET['status'] ?? '');
$page = max(1,(int)($_GET['page']??1)); $per = 20;
$where = ['1=1']; $params=[];
if ($status_f) { $where[] = "mpr.status=?"; $params[] = $status_f; }
$ws = implode(' AND ', $where);
$cnt = $db->prepare("SELECT COUNT(*) FROM merchant_payout_requests mpr WHERE $ws"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag = paginate($total,$per,$page,APP_URL.'/admin/payouts?page={page}'.($status_f?'&status='.$status_f:''));

$stmt = $db->prepare("SELECT mpr.*, m.company_name, m.payout_bank, m.payout_account_no FROM merchant_payout_requests mpr JOIN merchants m ON m.id=mpr.merchant_id WHERE $ws ORDER BY mpr.requested_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params,[$per,$pag['offset']])); $requests = $stmt->fetchAll();

layout_begin_admin('Permintaan Bayaran');
?>
<div class="page-title">💰 Permintaan Bayaran Pedagang</div>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <a href="<?= APP_URL ?>/admin/payouts" class="<?= !$status_f?'btn-gold':'btn-gold-outline' ?> btn-sm">Semua</a>
  <a href="<?= APP_URL ?>/admin/payouts?status=pending"  class="<?= $status_f==='pending' ?'btn-gold':'btn-gold-outline' ?> btn-sm">⏳ Tertangguh</a>
  <a href="<?= APP_URL ?>/admin/payouts?status=approved" class="<?= $status_f==='approved'?'btn-gold':'btn-gold-outline' ?> btn-sm">✅ Diluluskan</a>
  <a href="<?= APP_URL ?>/admin/payouts?status=paid"     class="<?= $status_f==='paid'    ?'btn-gold':'btn-gold-outline' ?> btn-sm">💸 Dibayar</a>
</div>
<?= flash_html('main') ?>
<div class="card-kasih">
  <div class="table-responsive">
    <table class="table-kasih">
      <thead><tr><th>Pedagang</th><th>Mata</th><th>RM</th><th>Bank</th><th>Status</th><th>Nota Admin</th><th>Tarikh</th><th>Tindakan</th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
          <td><?= h($r['company_name']) ?></td>
          <td style="font-weight:700;color:var(--gold-dark);"><?= gold_format_points($r['points_amount']) ?></td>
          <td>RM <?= number_format((float)$r['rm_equivalent'],2) ?></td>
          <td style="font-size:0.78rem;"><?= h($r['payout_bank']??'—') ?> <?= h($r['payout_account_no']??'') ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td style="font-size:0.78rem;color:#6B7280;"><?= h($r['admin_note']??'—') ?></td>
          <td style="font-size:0.75rem;color:#9CA3AF;white-space:nowrap;"><?= format_date($r['requested_at']) ?></td>
          <td>
            <?php if ($r['status'] === 'pending'): ?>
            <form method="POST" style="display:flex;gap:4px;flex-wrap:wrap;">
              <?= csrf_field() ?>
              <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
              <button name="action" value="approve" class="btn-gold btn-sm" style="padding:3px 8px;">✅ Lulus</button>
              <button name="action" value="reject"  class="btn-danger btn-sm" style="padding:3px 8px;" onclick="return confirm('Tolak permintaan ini?')">❌ Tolak</button>
            </form>
            <?php elseif ($r['status'] === 'approved'): ?>
            <form method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
              <button name="action" value="mark_paid" class="btn-gold btn-sm" style="padding:3px 8px;" onclick="return confirm('Tandakan sebagai dibayar?')">💸 Bayar</button>
            </form>
            <?php else: echo '—'; endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_html($pag) ?>
</div>
<?php layout_end_admin(); ?>
