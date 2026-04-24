<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();
if (is_admin()) redirect('admin/dashboard');
if (is_partner()) redirect('partner/dashboard');

$pdo = db();
$uid = (int)$_SESSION['user_id'];

// Request bank support
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $bank_id = (int)($_POST['bank_id'] ?? 0);
    $notes   = trim($_POST['notes'] ?? '');
    if ($bank_id) {
        $exists = $pdo->prepare('SELECT id FROM bank_support_cases WHERE member_id=? AND bank_id=?');
        $exists->execute([$uid, $bank_id]);
        if (!$exists->fetch()) {
            $pdo->prepare(
                'INSERT INTO bank_support_cases (member_id, bank_id, notes) VALUES (?,?,?)'
            )->execute([$uid, $bank_id, $notes]);
            flash('success', 'Banking support requested. Our team will contact you within 2 business days.');
        } else {
            flash('warning', 'You already have an active request with this bank.');
        }
        log_activity('request_bank_support', 'bank', $bank_id);
    }
    redirect('member/bank-support');
}

// My bank cases
$my_cases_stmt = $pdo->prepare(
    'SELECT bsc.*, b.bank_name FROM bank_support_cases bsc
     JOIN banks b ON b.id = bsc.bank_id WHERE bsc.member_id=? ORDER BY bsc.created_at DESC'
);
$my_cases_stmt->execute([$uid]);
$my_bank_cases = $my_cases_stmt->fetchAll();

// All active banks
$banks = $pdo->query('SELECT * FROM banks WHERE status="active" ORDER BY bank_name')->fetchAll();
$requested_bank_ids = array_column($my_bank_cases, 'bank_id');

$statuses_labels = [
    'not_started' => ['secondary', 'Not Started'],
    'documents_requested' => ['warning', 'Documents Requested'],
    'submitted' => ['primary', 'Submitted'],
    'appointment_arranged' => ['info', 'Appointment Arranged'],
    'approved' => ['success', 'Approved'],
    'rejected' => ['danger', 'Rejected'],
];

$page_title = 'Banking Support — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <h1><i class="bi bi-bank2 me-2 text-gold"></i>Banking Support</h1>
      <p class="text-muted">We assist you in opening a Malaysian bank account and setting up the mandatory Fixed Deposit for MM2H.</p>
    </div>
    <?php render_flash(); ?>

    <!-- My bank cases -->
    <?php if ($my_bank_cases): ?>
    <h5 class="mb-3">My Banking Requests</h5>
    <div class="row g-4 mb-5">
      <?php foreach ($my_bank_cases as $bc): ?>
      <div class="col-md-6">
        <div class="mm2h-card">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="mb-0"><?= h($bc['bank_name']) ?></h5>
            <span class="text-muted small"><?= time_ago($bc['created_at']) ?></span>
          </div>
          <div class="row g-2">
            <div class="col-6">
              <div class="text-muted" style="font-size:.72rem;">Account Opening</div>
              <?php [$c,$l] = $statuses_labels[$bc['account_opening_status']] ?? ['secondary','—']; ?>
              <span class="badge bg-<?= $c ?>"><?= $l ?></span>
            </div>
            <div class="col-6">
              <div class="text-muted" style="font-size:.72rem;">Fixed Deposit</div>
              <?php [$c,$l] = $statuses_labels[$bc['fixed_deposit_status']] ?? ['secondary','—']; ?>
              <span class="badge bg-<?= $c ?>"><?= $l ?></span>
            </div>
            <?php if ($bc['fd_amount']): ?>
            <div class="col-12 mt-2">
              <div class="text-muted" style="font-size:.72rem;">FD Amount</div>
              <div class="fw-bold text-gold"><?= format_money((float)$bc['fd_amount']) ?></div>
            </div>
            <?php endif; ?>
          </div>
          <?php if ($bc['notes']): ?>
          <p class="small text-muted mt-2 mb-0"><?= h($bc['notes']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Available banks -->
    <h5 class="mb-3">Request Banking Support</h5>
    <div class="row g-4">
      <?php foreach ($banks as $bank): ?>
      <div class="col-md-6 col-lg-4">
        <div class="mm2h-card">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width:48px;height:48px;background:rgba(200,160,60,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-bank2 text-gold fs-4"></i>
            </div>
            <div>
              <div class="fw-bold"><?= h($bank['bank_name']) ?></div>
              <?php if ($bank['contact_person']): ?>
              <div class="text-muted small"><?= h($bank['contact_person']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <div>
              <div class="text-muted" style="font-size:.72rem;">Minimum Fixed Deposit</div>
              <div class="fw-bold text-gold"><?= format_money((float)$bank['fd_min_amount']) ?></div>
            </div>
          </div>
          <?php if ($bank['notes']): ?>
          <p class="small text-muted mb-3"><?= h($bank['notes']) ?></p>
          <?php endif; ?>
          <?php if (in_array($bank['id'], $requested_bank_ids, false)): ?>
          <button class="btn btn-outline-success btn-sm w-100" disabled>
            <i class="bi bi-check-lg me-1"></i>Request Submitted
          </button>
          <?php else: ?>
          <button class="btn btn-gold btn-sm w-100" data-bs-toggle="modal"
                  data-bs-target="#bankModal" data-bank-id="<?= $bank['id'] ?>"
                  data-bank-name="<?= h($bank['bank_name']) ?>">
            <i class="bi bi-plus me-1"></i>Request Support
          </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Request Modal -->
<div class="modal fade" id="bankModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Request Banking Support</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="bank_id" id="modalBankId">
        <div class="modal-body">
          <p>You are requesting banking support from <strong id="modalBankName"></strong>.</p>
          <div class="mb-3">
            <label class="form-label">Additional Notes (optional)</label>
            <textarea name="notes" class="form-control" rows="3"
                      placeholder="Any specific requirements, preferred appointment times, etc."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-gold">Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('bankModal').addEventListener('show.bs.modal', function(e) {
  const btn = e.relatedTarget;
  document.getElementById('modalBankId').value = btn.dataset.bankId;
  document.getElementById('modalBankName').textContent = btn.dataset.bankName;
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
