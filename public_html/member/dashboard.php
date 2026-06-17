<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_auth();

// Redirect non-members to their correct panel
if (is_admin())   redirect('admin/dashboard');
if (is_partner()) redirect('partner/dashboard');

$pdo    = db();
$uid    = (int)$_SESSION['user_id'];
$user   = current_user();

// Profile
$profile_stmt = $pdo->prepare('SELECT * FROM member_profiles WHERE user_id = ?');
$profile_stmt->execute([$uid]);
$profile = $profile_stmt->fetch();

// Active case
$case_stmt = $pdo->prepare(
    'SELECT * FROM mm2h_cases WHERE applicant_id = ? ORDER BY created_at DESC LIMIT 1'
);
$case_stmt->execute([$uid]);
$my_case = $case_stmt->fetch();

// Document stats
$doc_stats = $pdo->prepare(
    'SELECT status, COUNT(*) AS cnt FROM documents WHERE user_id = ? GROUP BY status'
);
$doc_stats->execute([$uid]);
$docs = [];
foreach ($doc_stats->fetchAll() as $r) {
    $docs[$r['status']] = (int)$r['cnt'];
}
$total_docs    = array_sum($docs);
$verified_docs = $docs['verified'] ?? 0;

// Checklist completion
$checklist_total = (int)$pdo->query('SELECT COUNT(*) FROM document_checklists WHERE required=1 AND status="active"')->fetchColumn();

// Property recommendations
$prop_recs = $pdo->prepare(
    'SELECT pr.*, p.property_name, p.location, p.price, p.type
     FROM property_recommendations pr
     JOIN properties p ON p.id = pr.property_id
     WHERE pr.member_id = ? ORDER BY pr.created_at DESC LIMIT 3'
);
$prop_recs->execute([$uid]);
$properties = $prop_recs->fetchAll();

// Case status steps
$status_order = [
    'new_lead','initial_consult','doc_collection','eligibility_review',
    'submitted_agent','gov_processing','conditional','fd_stage',
    'medical','final_approval','completed',
];

$page_title = t('dashboard_title') . ' — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>

  <div class="main-content">
    <!-- Welcome header -->
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
      <div>
        <h1><?= t('welcome') ?>, <?= h(explode(' ', $user['full_name'])[0]) ?>! 👋</h1>
        <p class="text-muted mb-0">
          <?php if (!($profile['onboarding_completed'] ?? 0)): ?>
            Complete your profile to begin your MM2H journey.
          <?php else: ?>
            Your MM2H application is being managed on this dashboard.
          <?php endif; ?>
        </p>
      </div>
      <div class="d-flex gap-2">
        <span class="badge bg-<?= ($user['role'] ?? '') === 'member' ? 'primary' : 'secondary' ?> px-3 py-2">
          <?= h(ucwords(str_replace('_', ' ', $user['role'] ?? ''))) ?>
        </span>
        <span class="badge bg-<?= ($profile['subscription_plan'] ?? 'free') === 'free' ? 'secondary' : 'warning text-dark' ?> px-3 py-2">
          <?= h(ucfirst($profile['subscription_plan'] ?? 'free')) ?> Plan
        </span>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Onboarding alert -->
    <?php if (!($profile['onboarding_completed'] ?? 0)): ?>
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
      <i class="bi bi-exclamation-triangle-fill fs-4"></i>
      <div>
        <strong>Your profile is incomplete.</strong>
        Complete your onboarding so our team can assess your eligibility and assign an MM2H agent.
        <a href="<?= APP_URL ?>/member/onboarding" class="btn btn-gold btn-sm ms-3">Complete Now</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(200,160,60,.1);color:var(--secondary)">
            <i class="bi bi-folder2-open"></i>
          </div>
          <div>
            <div class="stat-value"><?= $my_case ? '1' : '0' ?></div>
            <div class="stat-label">Active Case</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(25,135,84,.1);color:var(--success)">
            <i class="bi bi-file-earmark-check"></i>
          </div>
          <div>
            <div class="stat-value"><?= $verified_docs ?>/<?= $checklist_total ?></div>
            <div class="stat-label">Docs Verified</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(59,130,246,.1);color:#3b82f6">
            <i class="bi bi-buildings"></i>
          </div>
          <div>
            <div class="stat-value"><?= count($properties) ?></div>
            <div class="stat-label">Property Matches</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6">
            <i class="bi bi-person-check"></i>
          </div>
          <div>
            <div class="stat-value"><?= ($profile['onboarding_completed'] ?? 0) ? '✓' : '!' ?></div>
            <div class="stat-label">Profile Status</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Case progress -->
      <div class="col-lg-7">
        <div class="mm2h-form-card h-100">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0"><i class="bi bi-folder2-open me-2 text-gold"></i>Application Progress</h5>
            <a href="<?= APP_URL ?>/member/case-progress" class="btn btn-outline-gold btn-sm">Details</a>
          </div>
          <?php if ($my_case): ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <small class="fw-semibold">Case: <?= h($my_case['case_number']) ?></small>
              <?= status_badge($my_case['current_status']) ?>
            </div>
          </div>
          <?php
          $current_idx = array_search($my_case['current_status'], $status_order);
          $progress_pct = $current_idx !== false ? round(($current_idx / (count($status_order) - 1)) * 100) : 0;
          ?>
          <div class="progress mb-4" style="height:8px;border-radius:4px;">
            <div class="progress-bar bg-warning" style="width:<?= $progress_pct ?>%"></div>
          </div>
          <div class="progress-timeline">
            <?php foreach ($status_order as $i => $s):
              $is_done   = $i < $current_idx;
              $is_active = $s === $my_case['current_status'];
            ?>
            <div class="timeline-item">
              <div class="timeline-dot <?= $is_done ? 'done' : ($is_active ? 'active' : '') ?>"></div>
              <div class="timeline-content <?= $is_active ? 'active' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="small fw-semibold"><?= h(ucwords(str_replace('_', ' ', $s))) ?></span>
                  <?php if ($is_done): ?><i class="bi bi-check-circle-fill text-success small"></i><?php endif; ?>
                  <?php if ($is_active): ?><span class="badge bg-warning text-dark" style="font-size:.65rem;">Current</span><?php endif; ?>
                </div>
                <?php if ($is_active && $my_case['next_action']): ?>
                <div class="small text-muted mt-1">Next: <?= h($my_case['next_action']) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-folder-plus fs-1 d-block mb-3" style="opacity:.3"></i>
            <p>No active case yet. Complete your profile and our team will open your case.</p>
            <a href="<?= APP_URL ?>/member/onboarding" class="btn btn-gold btn-sm">Complete Profile</a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right column -->
      <div class="col-lg-5 d-flex flex-column gap-4">
        <!-- Document checklist quick view -->
        <div class="mm2h-form-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0"><i class="bi bi-list-check me-2 text-gold"></i>Documents</h6>
            <a href="<?= APP_URL ?>/member/documents" class="btn btn-outline-gold btn-sm">Upload</a>
          </div>
          <div class="d-flex gap-3">
            <?php
            $doc_badges = [
              ['verified', 'success', 'Verified'],
              ['pending',  'warning', 'Pending'],
              ['rejected', 'danger',  'Rejected'],
            ];
            foreach ($doc_badges as [$k, $color, $label]):
            ?>
            <div class="text-center flex-fill p-2 rounded" style="background:rgba(0,0,0,.03)">
              <div class="fw-bold fs-4" style="color:var(--<?= $color === 'warning' ? 'secondary' : $color ?>)"><?= $docs[$k] ?? 0 ?></div>
              <div class="small text-muted"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Property recommendations -->
        <div class="mm2h-form-card flex-fill">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0"><i class="bi bi-buildings me-2 text-gold"></i>Property Matches</h6>
            <a href="<?= APP_URL ?>/member/property-match" class="btn btn-outline-gold btn-sm">View All</a>
          </div>
          <?php if ($properties): ?>
            <?php foreach ($properties as $prop): ?>
            <div class="d-flex align-items-center gap-3 mb-3">
              <div style="width:42px;height:42px;background:rgba(200,160,60,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-building text-gold"></i>
              </div>
              <div class="min-w-0">
                <div class="fw-semibold small text-truncate"><?= h($prop['property_name']) ?></div>
                <div class="text-muted" style="font-size:.75rem;"><?= h($prop['location'] ?? '') ?> · <?= format_money((float)$prop['price']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted small">Property recommendations will appear here once assigned by our team.</p>
          <?php endif; ?>
        </div>

        <!-- Quick links -->
        <div class="mm2h-form-card">
          <h6 class="mb-3"><i class="bi bi-lightning me-2 text-gold"></i>Quick Actions</h6>
          <div class="d-flex flex-column gap-2">
            <a href="<?= APP_URL ?>/member/bank-support"    class="btn btn-outline-secondary text-start btn-sm"><i class="bi bi-bank2 me-2"></i>Banking Support</a>
            <a href="<?= APP_URL ?>/member/business-network" class="btn btn-outline-secondary text-start btn-sm"><i class="bi bi-people me-2"></i>Business Network</a>
            <a href="<?= APP_URL ?>/member/profile"          class="btn btn-outline-secondary text-start btn-sm"><i class="bi bi-person-circle me-2"></i>Edit Profile</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
