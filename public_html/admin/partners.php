<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pid     = (int)($_POST['partner_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    if ($pid) {
        if ($action === 'verify') {
            $pdo->prepare('UPDATE partners SET verified=1 WHERE id=?')->execute([$pid]);
            flash('success', 'Partner verified.');
        } elseif ($action === 'set_commission') {
            $rate = min(50, max(0, (float)($_POST['commission_rate'] ?? 20)));
            $pdo->prepare('UPDATE partners SET commission_rate=? WHERE id=?')->execute([$rate, $pid]);
            flash('success', 'Commission rate updated.');
        }
        log_activity("admin_partner_$action", 'partner', $pid);
    }
    redirect('admin/partners');
}

$partners = $pdo->query(
  "SELECT p.*, u.full_name, u.email, u.role, u.status,
          (SELECT COUNT(*) FROM referrals r WHERE r.referrer_id=u.id) AS total_referrals,
          (SELECT COALESCE(SUM(amount),0) FROM commissions c WHERE c.partner_id=u.id) AS total_earned
   FROM partners p JOIN users u ON u.id=p.user_id
   ORDER BY p.created_at DESC"
)->fetchAll();

$page_title = 'Partners — Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header"><h1><i class="bi bi-diagram-3 me-2 text-gold"></i>Partners</h1></div>
    <?php render_flash(); ?>
    <div class="mm2h-table">
      <table class="table">
        <thead>
          <tr><th>Partner</th><th>Role</th><th>Sector</th><th>Commission</th><th>Referrals</th><th>Earned</th><th>Verified</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($partners as $p): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= h($p['full_name']) ?></div>
              <small class="text-muted"><?= h($p['email']) ?></small>
              <?php if ($p['company_name']): ?><br><small class="text-muted"><?= h($p['company_name']) ?></small><?php endif; ?>
            </td>
            <td><?= status_badge($p['role']) ?></td>
            <td class="small"><?= h($p['sector'] ?? '—') ?></td>
            <td>
              <form method="POST" class="d-flex gap-1 align-items-center">
                <?= csrf_field() ?>
                <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="set_commission">
                <input type="number" name="commission_rate" class="form-control form-control-sm" style="width:70px"
                       value="<?= h($p['commission_rate']) ?>" min="0" max="50" step="0.5">
                <span>%</span>
                <button class="btn btn-sm btn-outline-gold">Set</button>
              </form>
            </td>
            <td class="text-center"><?= (int)$p['total_referrals'] ?></td>
            <td class="fw-bold text-gold small"><?= format_money((float)$p['total_earned']) ?></td>
            <td>
              <?php if ($p['verified']): ?>
              <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Verified</span>
              <?php else: ?>
              <form method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="verify">
                <button class="btn btn-sm btn-outline-success">Verify</button>
              </form>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= APP_URL ?>/admin/users?id=<?= $p['user_id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$partners): ?><tr><td colspan="8" class="text-center py-4 text-muted">No partners yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
