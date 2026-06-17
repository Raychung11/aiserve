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

$case_stmt = $pdo->prepare(
    'SELECT c.*, a.full_name AS agent_name, a.email AS agent_email
     FROM mm2h_cases c
     LEFT JOIN users a ON a.id = c.assigned_agent_id
     WHERE c.applicant_id = ? ORDER BY c.created_at DESC LIMIT 1'
);
$case_stmt->execute([$uid]);
$my_case = $case_stmt->fetch();

$notes_list = [];
if ($my_case) {
    $notes_stmt = $pdo->prepare(
        'SELECT n.*, u.full_name AS author FROM case_notes n
         JOIN users u ON u.id = n.author_id
         WHERE n.case_id = ? AND n.is_internal = 0 ORDER BY n.created_at DESC'
    );
    $notes_stmt->execute([$my_case['id']]);
    $notes_list = $notes_stmt->fetchAll();
}

$status_order = [
    'new_lead'          => 'New Lead',
    'initial_consult'   => 'Initial Consultation',
    'doc_collection'    => 'Document Collection',
    'eligibility_review'=> 'Eligibility Review',
    'submitted_agent'   => 'Submitted to Agent',
    'gov_processing'    => 'Government Processing',
    'conditional'       => 'Conditional Approval',
    'fd_stage'          => 'Fixed Deposit Stage',
    'medical'           => 'Medical Check',
    'final_approval'    => 'Final Approval',
    'completed'         => 'Completed',
];

$page_title = 'Case Progress — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header">
      <h1><i class="bi bi-folder2-open me-2 text-gold"></i>Application Progress</h1>
    </div>
    <?php render_flash(); ?>

    <?php if (!$my_case): ?>
    <div class="mm2h-form-card text-center py-5">
      <i class="bi bi-folder-plus fs-1 text-muted d-block mb-3" style="opacity:.3"></i>
      <h4>No Case Opened Yet</h4>
      <p class="text-muted">Complete your profile onboarding and our team will open your MM2H case.</p>
      <a href="<?= APP_URL ?>/member/onboarding" class="btn btn-gold">Complete Profile</a>
    </div>
    <?php else: ?>

    <div class="row g-4">
      <div class="col-lg-8">
        <!-- Case header -->
        <div class="mm2h-form-card mb-4">
          <div class="row g-3 align-items-center">
            <div class="col-md-6">
              <div class="small text-muted">Case Number</div>
              <div class="fw-bold fs-5 text-gold"><?= h($my_case['case_number']) ?></div>
            </div>
            <div class="col-md-3">
              <div class="small text-muted">Current Status</div>
              <div class="mt-1"><?= status_badge($my_case['current_status']) ?></div>
            </div>
            <div class="col-md-3">
              <div class="small text-muted">Due Date</div>
              <div class="fw-semibold"><?= format_date($my_case['due_date']) ?></div>
            </div>
            <?php if ($my_case['next_action']): ?>
            <div class="col-12">
              <div class="alert alert-info mb-0 py-2">
                <i class="bi bi-arrow-right-circle me-2"></i>
                <strong>Next action:</strong> <?= h($my_case['next_action']) ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Progress -->
        <div class="mm2h-form-card">
          <h5 class="mb-4">Application Journey</h5>
          <?php
          $status_keys = array_keys($status_order);
          $current_idx = array_search($my_case['current_status'], $status_keys);
          $pct = $current_idx !== false ? round(($current_idx / (count($status_keys) - 1)) * 100) : 0;
          ?>
          <div class="d-flex justify-content-between mb-1 small">
            <span class="text-muted">Progress</span>
            <span class="fw-bold text-gold"><?= $pct ?>%</span>
          </div>
          <div class="progress mb-4" style="height:10px;border-radius:5px;">
            <div class="progress-bar" style="width:<?= $pct ?>%;background:var(--secondary);border-radius:5px;"></div>
          </div>

          <div class="progress-timeline">
            <?php foreach ($status_order as $key => $label):
              $is_done   = $current_idx !== false && array_search($key, $status_keys) < $current_idx;
              $is_active = $key === $my_case['current_status'];
            ?>
            <div class="timeline-item">
              <div class="timeline-dot <?= $is_done ? 'done' : ($is_active ? 'active' : '') ?>"></div>
              <div class="timeline-content <?= $is_active ? 'active' : '' ?>">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="fw-semibold small"><?= h($label) ?></div>
                    <?php if ($is_active):?>
                    <div class="text-muted" style="font-size:.78rem;">This is your current stage.</div>
                    <?php elseif ($is_done): ?>
                    <div class="text-success" style="font-size:.78rem;"><i class="bi bi-check-circle me-1"></i>Completed</div>
                    <?php endif; ?>
                  </div>
                  <?php if ($is_active): ?><span class="badge bg-warning text-dark flex-shrink-0">Current</span><?php endif; ?>
                  <?php if ($is_done): ?><i class="bi bi-check-circle-fill text-success flex-shrink-0"></i><?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <!-- Assigned agent -->
        <div class="mm2h-form-card mb-4">
          <h6 class="mb-3"><i class="bi bi-person-badge me-2 text-gold"></i>Assigned Agent</h6>
          <?php if ($my_case['agent_name']): ?>
          <div class="fw-semibold"><?= h($my_case['agent_name']) ?></div>
          <div class="small text-muted"><?= h($my_case['agent_email']) ?></div>
          <?php else: ?>
          <p class="text-muted small mb-0">Agent will be assigned after eligibility review.</p>
          <?php endif; ?>
        </div>

        <!-- Case updates (public notes) -->
        <div class="mm2h-form-card">
          <h6 class="mb-3"><i class="bi bi-chat-dots me-2 text-gold"></i>Case Updates</h6>
          <?php if ($notes_list): ?>
          <?php foreach ($notes_list as $note): ?>
          <div class="border rounded-mm2h p-2 mb-2">
            <div class="d-flex justify-content-between mb-1">
              <span class="small fw-semibold"><?= h($note['author']) ?></span>
              <span class="text-muted" style="font-size:.72rem;"><?= time_ago($note['created_at']) ?></span>
            </div>
            <p class="mb-0 small"><?= h($note['note']) ?></p>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <p class="text-muted small">No updates yet. Check back soon.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
