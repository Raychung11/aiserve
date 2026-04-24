<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();
if (is_admin())  redirect('admin/dashboard');
if (is_member()) redirect('member/dashboard');

$pdo = db();
$uid = (int)$_SESSION['user_id'];

// Submit new referral
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Create user account (passive lead)
        $check = $pdo->prepare('SELECT id FROM users WHERE email=?');
        $check->execute([$email]);
        if (!$check->fetch()) {
            $tmp_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT, ['cost' => 12]);
            $ref_code = generate_referral_code($name);
            $pdo->prepare('INSERT INTO users (email, password, full_name, phone, role, referral_code, referred_by, status) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$email, $tmp_pass, $name, $phone, 'member', $ref_code, $uid, 'active']);
            $new_uid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO member_profiles (user_id) VALUES (?)')->execute([$new_uid]);
            $user_stmt = $pdo->prepare('SELECT id FROM users WHERE email=?');
            $user_stmt->execute([$email]);
            $new_user = $user_stmt->fetch();
            $referred_uid = (int)($new_user['id'] ?? $new_uid);
        } else {
            $ex = $check->fetch() ?: $pdo->prepare('SELECT id FROM users WHERE email=?');
            // re-fetch
            $user_stmt2 = $pdo->prepare('SELECT id FROM users WHERE email=?');
            $user_stmt2->execute([$email]);
            $existing = $user_stmt2->fetch();
            $referred_uid = (int)$existing['id'];
        }
        // Create referral record
        $dup = $pdo->prepare('SELECT id FROM referrals WHERE referrer_id=? AND referred_user_id=?');
        $dup->execute([$uid, $referred_uid]);
        if (!$dup->fetch()) {
            $partner_stmt = $pdo->prepare('SELECT commission_rate FROM partners WHERE user_id=?');
            $partner_stmt->execute([$uid]);
            $partner = $partner_stmt->fetch();
            $rate = $partner ? (float)$partner['commission_rate'] : 20.0;
            $pdo->prepare('INSERT INTO referrals (referrer_id, referred_user_id, commission_rate, lead_status) VALUES (?,?,?,?)')
                ->execute([$uid, $referred_uid, $rate, 'new']);
            flash('success', "Referral for $name submitted successfully.");
        } else {
            flash('warning', 'This contact has already been referred.');
        }
        log_activity('submit_referral');
    } else {
        flash('danger', 'Please provide a valid name and email.');
    }
    redirect('partner/referrals');
}

$page   = max(1,(int)($_GET['p'] ?? 1));
$per    = 20;
$total  = (int)$pdo->query("SELECT COUNT(*) FROM referrals WHERE referrer_id=$uid")->fetchColumn();
$pg     = paginate($total, $per, $page);
$refs_stmt = $pdo->prepare(
    'SELECT r.*, u.full_name, u.email, u.phone, u.created_at AS joined FROM referrals r
     JOIN users u ON u.id=r.referred_user_id WHERE r.referrer_id=? ORDER BY r.created_at DESC LIMIT ? OFFSET ?'
);
$refs_stmt->execute([$uid, $per, $pg['offset']]);
$referrals = $refs_stmt->fetchAll();

$page_title = 'Referrals — Partner Portal';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/partner_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h1><i class="bi bi-diagram-3 me-2 text-gold"></i>Referrals</h1>
      <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#newRefModal">
        <i class="bi bi-plus me-1"></i>New Referral
      </button>
    </div>
    <?php render_flash(); ?>

    <div class="mm2h-table">
      <table class="table">
        <thead>
          <tr><th>#</th><th>Name</th><th>Contact</th><th>Lead Status</th><th>Deal</th><th>Commission</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php foreach ($referrals as $i => $r): ?>
          <tr>
            <td class="text-muted small"><?= $pg['offset']+$i+1 ?></td>
            <td class="fw-semibold"><?= h($r['full_name']) ?></td>
            <td>
              <div class="small"><?= h($r['email']) ?></div>
              <small class="text-muted"><?= h($r['phone'] ?? '') ?></small>
            </td>
            <td><?= status_badge($r['lead_status']) ?></td>
            <td><?= status_badge($r['deal_status']) ?></td>
            <td>
              <?php if ($r['commission_amount'] > 0): ?>
              <div class="fw-bold text-gold small"><?= format_money((float)$r['commission_amount']) ?></div>
              <?= status_badge($r['commission_status']) ?>
              <?php else: ?>
              <span class="text-muted small">Pending deal</span>
              <?php endif; ?>
            </td>
            <td class="text-muted small"><?= format_date($r['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$referrals): ?>
          <tr><td colspan="7" class="text-center py-5 text-muted">No referrals yet. Add your first referral using the button above.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pg['total_pages'] > 1): ?>
    <nav class="mt-3"><ul class="pagination">
      <?php for ($i=1;$i<=$pg['total_pages'];$i++): ?>
      <li class="page-item <?=$i===$page?'active':''?>">
        <a class="page-link" href="?p=<?=$i?>"><?=$i?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
  </div>
</div>

<!-- New Referral Modal -->
<div class="modal fade" id="newRefModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-plus me-2 text-gold"></i>Submit New Referral</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Background, purpose, investment range…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-gold">Submit Referral</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
