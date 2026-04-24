<?php
require_once __DIR__ . '/../includes/auth_check.php';
$activePage = 'agents';
$flash = $_SESSION['flash'] ?? []; unset($_SESSION['flash']);

// ── POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        // Check plan agent limit
        $agentCount = Database::count('users','tenant_id=? AND role="agent" AND is_active=1',[$_tenantId]);
        $limit = (int)PLAN_LIMITS[$_user['plan']??'starter']['agents'];
        if ($agentCount >= $limit) {
            $_SESSION['flash'] = ['error' => "Agent limit ($limit) reached. Upgrade your plan."];
            header('Location: ' . APP_URL . '/agents'); exit;
        }
        if (Database::count('users','email=?',[strtolower($_POST['email'])])) {
            $_SESSION['flash'] = ['error' => 'Email already registered.'];
            header('Location: ' . APP_URL . '/agents?action=create'); exit;
        }
        // Generate unique agent code
        do { $code = strtoupper(substr(md5(uniqid()),0,8)); } while(Database::count('users','agent_code=?',[$code]));

        Database::insert('users',[
            'tenant_id'       => $_tenantId,
            'name'            => trim($_POST['name']),
            'email'           => strtolower(trim($_POST['email'])),
            'phone'           => trim($_POST['phone']??''),
            'password_hash'   => password_hash($_POST['password'],PASSWORD_BCRYPT),
            'role'            => 'agent',
            'agent_code'      => $code,
            'commission_tier' => (int)($_POST['commission_tier']??5),
            'is_active'       => 1,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::record('agent.created','Agent added: '.trim($_POST['name']));
        $_SESSION['flash'] = ['success' => 'Agent created. Code: '.$code];
        header('Location: ' . APP_URL . '/agents'); exit;
    }

    if ($action === 'pay') {
        $agentId = (int)$_POST['agent_id'];
        Database::query('UPDATE commission_logs SET status="paid",paid_at=?,reference=?,updated_at=? WHERE agent_id=? AND tenant_id=? AND status="pending"',
            [date('Y-m-d H:i:s'), trim($_POST['reference']??''), date('Y-m-d H:i:s'), $agentId, $_tenantId]);
        ActivityLog::record('commission.paid','Commission paid to agent #'.$agentId);
        $_SESSION['flash'] = ['success' => 'Commission marked as paid.'];
        header('Location: ' . APP_URL . '/agents?view='.$agentId); exit;
    }
}

// ── Leaderboard ────────────────────────────────────────────────────────
if (!empty($_GET['leaderboard'])) {
    $pageTitle    = 'Agent Leaderboard';
    $pageSubtitle = 'Top performers';
    $agents = Database::fetchAll(
        'SELECT u.*, (SELECT COUNT(*) FROM properties p WHERE p.agent_id=u.id AND p.deleted_at IS NULL) AS prop_count,
                (SELECT COALESCE(SUM(commission_amount),0) FROM commission_logs cl WHERE cl.agent_id=u.id AND cl.status="paid") AS total_earned
         FROM users u WHERE u.tenant_id=? AND u.role="agent" AND u.is_active=1 ORDER BY total_earned DESC',
        [$_tenantId]
    );
    require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="card-box">
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th style="width:50px;">#</th><th>Agent</th><th>Tier</th><th>Properties</th><th>Total Earned</th><th></th></tr></thead>
        <tbody>
          <?php foreach($agents as $i=>$a): ?>
          <tr <?= $i===0?'style="background:#fef9c3"':'' ?>>
            <td class="fw-bold"><?= $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':$i+1)) ?></td>
            <td><div class="fw-semibold" style="font-size:.875rem;"><?= htmlspecialchars($a['name']) ?></div><code style="font-size:.75rem;"><?= $a['agent_code'] ?></code></td>
            <td><span style="background:#ede9fe;color:#5b21b6;padding:.25rem .6rem;border-radius:6px;font-size:.75rem;"><?= $a['commission_tier'] ?>%</span></td>
            <td><?= $a['prop_count'] ?></td>
            <td class="fw-bold text-success">RM <?= number_format($a['total_earned'],0) ?></td>
            <td><a href="<?= APP_URL ?>/agents?view=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── View agent ─────────────────────────────────────────────────────────
if (!empty($_GET['view'])) {
    $agent = Database::fetchOne('SELECT * FROM users WHERE id=? AND tenant_id=? AND role="agent"',[(int)$_GET['view'],$_tenantId]);
    if (!$agent) { header('Location: ' . APP_URL . '/agents'); exit; }
    $pageTitle    = $agent['name'];
    $pageSubtitle = 'Agent Profile';
    $commissions  = Database::fetchAll('SELECT cl.*,p.name AS property_name FROM commission_logs cl LEFT JOIN properties p ON p.id=cl.property_id WHERE cl.agent_id=? AND cl.tenant_id=? ORDER BY cl.created_at DESC LIMIT 50',[$agent['id'],$_tenantId]);
    $pending  = Database::fetchOne('SELECT COALESCE(SUM(commission_amount),0) s FROM commission_logs WHERE agent_id=? AND tenant_id=? AND status="pending"',[$agent['id'],$_tenantId])['s']??0;
    $paid     = Database::fetchOne('SELECT COALESCE(SUM(commission_amount),0) s FROM commission_logs WHERE agent_id=? AND tenant_id=? AND status="paid"',[$agent['id'],$_tenantId])['s']??0;
    $propCount= Database::count('properties','agent_id=? AND deleted_at IS NULL',[$agent['id']]);
    require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="row g-3 mb-4">
      <div class="col-md-3"><div class="card-box text-center">
        <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center mx-auto mb-2" style="width:56px;height:56px;background:#6366f1;font-size:1.3rem;"><?= strtoupper(substr($agent['name'],0,1)) ?></div>
        <div class="fw-semibold"><?= htmlspecialchars($agent['name']) ?></div>
        <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($agent['email']) ?></div>
        <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($agent['phone']??'') ?></div>
        <div class="mt-2"><span style="background:#ede9fe;color:#5b21b6;padding:.3rem .7rem;border-radius:6px;font-size:.75rem;"><?= $agent['commission_tier'] ?>% commission</span></div>
        <div class="mt-2 p-2 bg-light rounded-3"><div class="text-muted" style="font-size:.7rem;">Agent Code</div><code class="fw-bold"><?= $agent['agent_code'] ?></code></div>
      </div></div>
      <div class="col-md-9">
        <div class="row g-3 mb-3">
          <?php foreach([['Properties',$propCount,''],['Pending',number_format((float)$pending,0),' text-warning'],['Paid',number_format((float)$paid,0),' text-success']] as [$l,$v,$c]): ?>
          <div class="col-md-4"><div class="card-box"><div class="stat-label"><?=$l?></div><div class="stat-value<?=$c?>"><?= $l!=='Properties'?'RM ':'' ?><?=$v?></div></div></div>
          <?php endforeach; ?>
        </div>
        <?php if((float)$pending > 0): ?>
        <div class="card-box">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div><div class="fw-semibold">Pending Payout: RM <?= number_format((float)$pending,2) ?></div><div class="text-muted" style="font-size:.8rem;">Mark all pending commissions as paid</div></div>
            <form action="<?= APP_URL ?>/agents" method="POST" class="d-flex gap-2 align-items-center">
              <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>"><input type="hidden" name="action" value="pay"><input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
              <input type="text" name="reference" class="form-control form-control-sm" style="width:140px;" placeholder="Ref. no.">
              <button type="submit" class="btn btn-success btn-sm">Pay Now</button>
            </form>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-box">
      <h6 class="fw-semibold mb-3">Commission History</h6>
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th style="font-size:.8rem;">Property</th><th style="font-size:.8rem;">Revenue</th><th style="font-size:.8rem;">Rate</th><th style="font-size:.8rem;">Commission</th><th style="font-size:.8rem;">Status</th><th style="font-size:.8rem;">Date</th></tr></thead>
        <tbody>
          <?php if(!$commissions): ?><tr><td colspan="6" class="text-center text-muted py-4">No commission records yet.</td></tr>
          <?php else: foreach($commissions as $c): ?>
          <tr>
            <td style="font-size:.85rem;"><?= htmlspecialchars($c['property_name']??'—') ?></td>
            <td style="font-size:.85rem;">RM <?= number_format($c['revenue_amount'],0) ?></td>
            <td style="font-size:.85rem;"><?= $c['commission_rate'] ?>%</td>
            <td class="fw-semibold text-success" style="font-size:.875rem;">RM <?= number_format($c['commission_amount'],2) ?></td>
            <td><?php $sc=$c['status']==='paid'?['#dcfce7','#15803d']:($c['status']==='pending'?['#fef9c3','#b45309']:['#f1f5f9','#475569']); ?><span style="font-size:.7rem;padding:.2rem .5rem;border-radius:6px;background:<?=$sc[0]?>;color:<?=$sc[1]?>"><?=ucfirst($c['status'])?></span></td>
            <td class="text-muted" style="font-size:.78rem;"><?= $c['paid_at']?date('d M Y',strtotime($c['paid_at'])):date('d M Y',strtotime($c['created_at'])) ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── Create form ────────────────────────────────────────────────────────
if (!empty($_GET['action']) && $_GET['action'] === 'create') {
    $pageTitle = 'Add Agent'; $pageSubtitle = 'Invite a new agent to your network';
    require_once __DIR__ . '/../includes/header.php'; ?>
    <div class="row justify-content-center"><div class="col-lg-6"><div class="card-box">
    <form action="<?= APP_URL ?>/agents" method="POST">
    <input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>"><input type="hidden" name="action" value="create">
    <div class="row g-3">
      <div class="col-12"><label class="form-label fw-semibold">Full Name</label><input type="text" name="name" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Phone</label><input type="text" name="phone" class="form-control" required></div>
      <div class="col-12"><label class="form-label fw-semibold">Commission Tier</label>
        <div class="row g-2">
          <?php foreach([[5,'Base — new agents'],[7,'Mid — 10+ properties'],[10,'Top — high performers']] as [$pct,$desc]): ?>
          <div class="col-md-4"><label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2" style="cursor:pointer;">
            <input type="radio" name="commission_tier" value="<?=$pct?>" <?=$pct===5?'checked':''?>>
            <div><div class="fw-semibold"><?=$pct?>%</div><div class="text-muted" style="font-size:.7rem;"><?=$desc?></div></div>
          </label></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-md-6"><label class="form-label fw-semibold">Password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
      <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="<?= APP_URL ?>/agents" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">Create Agent</button>
      </div>
    </div></form></div></div></div>
    <?php require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── Agent list ─────────────────────────────────────────────────────────
$pageTitle    = 'Agent Network';
$pageSubtitle = 'Manage your referral team and commissions';
$agents = Database::fetchAll(
    'SELECT u.*,
       (SELECT COUNT(*) FROM properties p WHERE p.agent_id=u.id AND p.deleted_at IS NULL) AS prop_count,
       (SELECT COALESCE(SUM(commission_amount),0) FROM commission_logs cl WHERE cl.agent_id=u.id AND cl.status="pending") AS pending_comm,
       (SELECT COALESCE(SUM(commission_amount),0) FROM commission_logs cl WHERE cl.agent_id=u.id AND cl.status="paid") AS total_earned
     FROM users u WHERE u.tenant_id=? AND u.role="agent" ORDER BY u.created_at DESC',
    [$_tenantId]
);
require_once __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <a href="<?= APP_URL ?>/agents?leaderboard=1" class="btn btn-sm btn-outline-secondary"><i class="bi bi-trophy me-1"></i>Leaderboard</a>
  <a href="<?= APP_URL ?>/agents?action=create" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Agent</a>
</div>
<?php if(!$agents): ?>
<div class="text-center py-5 card-box"><i class="bi bi-person-badge" style="font-size:3rem;color:#cbd5e1;"></i><h5 class="mt-3 text-muted">No agents yet</h5><a href="<?= APP_URL ?>/agents?action=create" class="btn btn-primary mt-2">Add first agent</a></div>
<?php else: ?>
<div class="row g-3">
  <?php foreach($agents as $a): ?>
  <div class="col-md-6 col-xl-4"><div class="card-box card-hover">
    <div class="d-flex align-items-start justify-content-between mb-3">
      <div class="d-flex align-items-center gap-2">
        <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#6366f1;font-size:.9rem;flex-shrink:0;"><?= strtoupper(substr($a['name'],0,1)) ?></div>
        <div><div class="fw-semibold"><?= htmlspecialchars($a['name']) ?></div><div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($a['email']) ?></div></div>
      </div>
      <span style="background:#ede9fe;color:#5b21b6;padding:.25rem .6rem;border-radius:6px;font-size:.7rem;"><?= $a['commission_tier'] ?>%</span>
    </div>
    <div class="row g-2 text-center mb-3">
      <div class="col-4"><div class="text-muted" style="font-size:.7rem;">Properties</div><div class="fw-bold"><?= $a['prop_count'] ?></div></div>
      <div class="col-4"><div class="text-muted" style="font-size:.7rem;">Pending</div><div class="fw-bold text-warning">RM <?= number_format((float)$a['pending_comm'],0) ?></div></div>
      <div class="col-4"><div class="text-muted" style="font-size:.7rem;">Earned</div><div class="fw-bold text-success">RM <?= number_format((float)$a['total_earned'],0) ?></div></div>
    </div>
    <div class="d-flex align-items-center">
      <code style="font-size:.75rem;color:#64748b;"><?= $a['agent_code'] ?></code>
      <a href="<?= APP_URL ?>/agents?view=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary ms-auto">View</a>
    </div>
  </div></div>
  <?php endforeach; ?>
</div>
<?php endif;
require_once __DIR__ . '/../includes/footer.php';
