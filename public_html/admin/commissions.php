<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_admin();

$pdo = db();
$statuses = ['pending','approved','paid','rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cid    = (int)($_POST['commission_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $notes  = trim($_POST['notes'] ?? '');
    if ($cid && in_array($status, $statuses, true)) {
        $paid = $status === 'paid' ? ', paid_at=NOW()' : '';
        $pdo->prepare("UPDATE commissions SET status=?, notes=?$paid, updated_at=NOW() WHERE id=?")->execute([$status,$notes,$cid]);
        log_activity('admin_update_commission','commission',$cid);
        flash('success','Commission updated.');
    }
    redirect('admin/commissions');
}

$filter = $_GET['status'] ?? '';
$page   = max(1,(int)($_GET['p'] ?? 1));
$per    = 20;
$where  = ['1=1']; $params=[];
if ($filter && in_array($filter,$statuses,true)) { $where[]='c.status=?'; $params[]=$filter; }
$wSQL = implode(' AND ', $where);
$total = $pdo->prepare("SELECT COUNT(*) FROM commissions c WHERE $wSQL");
$total->execute($params);
$pg = paginate((int)$total->fetchColumn(),$per,$page);
$stmt = $pdo->prepare(
  "SELECT c.*, u.full_name AS partner_name, u.email AS partner_email
   FROM commissions c JOIN users u ON u.id=c.partner_id
   WHERE $wSQL ORDER BY c.created_at DESC LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params,[$per,$pg['offset']]));
$commissions = $stmt->fetchAll();

$summary = $pdo->query("SELECT status, SUM(amount) AS total FROM commissions GROUP BY status")->fetchAll();

$page_title = 'Commissions — Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header"><h1><i class="bi bi-cash-stack me-2 text-gold"></i>Commissions</h1></div>
    <?php render_flash(); ?>
    <!-- Summary -->
    <div class="row g-3 mb-4">
      <?php foreach ($summary as $s): ?>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(200,160,60,.1);color:var(--secondary)">
            <i class="bi bi-cash"></i>
          </div>
          <div>
            <div class="stat-value small"><?= format_money((float)$s['total']) ?></div>
            <div class="stat-label"><?= h(ucfirst($s['status'])) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <!-- Filter -->
    <form method="GET" class="row g-2 mb-4">
      <div class="col-auto">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          <?php foreach ($statuses as $s): ?>
          <option value="<?=$s?>" <?=$filter===$s?'selected':''?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto"><button class="btn btn-gold">Filter</button></div>
    </form>
    <div class="mm2h-table">
      <table class="table">
        <thead>
          <tr><th>#</th><th>Partner</th><th>Amount</th><th>Type</th><th>Status</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($commissions as $i => $c): ?>
          <tr>
            <td class="text-muted small"><?= $pg['offset']+$i+1 ?></td>
            <td>
              <div class="fw-semibold"><?= h($c['partner_name']) ?></div>
              <small class="text-muted"><?= h($c['partner_email']) ?></small>
            </td>
            <td class="fw-bold text-gold"><?= format_money((float)$c['amount']) ?></td>
            <td><?= h(ucfirst($c['type'])) ?></td>
            <td><?= status_badge($c['status']) ?></td>
            <td class="text-muted small"><?= format_date($c['created_at']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-gold" data-bs-toggle="modal"
                      data-bs-target="#commModal" data-id="<?= $c['id'] ?>"
                      data-status="<?= h($c['status']) ?>" data-notes="<?= h($c['notes'] ?? '') ?>">
                Update
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$commissions): ?><tr><td colspan="7" class="text-center py-4 text-muted">No commissions found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Update Modal -->
<div class="modal fade" id="commModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Commission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="commission_id" id="commId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="commStatus" class="form-select">
              <?php foreach ($statuses as $s): ?>
              <option value="<?=$s?>"><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" id="commNotes" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-gold">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('commModal').addEventListener('show.bs.modal', function(e) {
  const btn = e.relatedTarget;
  document.getElementById('commId').value     = btn.dataset.id;
  document.getElementById('commStatus').value = btn.dataset.status;
  document.getElementById('commNotes').value  = btn.dataset.notes;
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
