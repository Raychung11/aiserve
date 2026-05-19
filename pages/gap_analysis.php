<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding');
    exit;
}

$pageTitle = 'Gap Analysis';
$framework = $activeCompany['framework'];
$period    = $activeCompany['reporting_year'];

// Handle "Convert to Action Plan" POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['convert_gap_to_ap'])) {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $apId = ActionPlanManager::create([
            'company_id'     => $activeCompanyId,
            'indicator_id'   => $_POST['indicator_id'] ?? null,
            'created_by'     => $currentUser['id'],
            'title'          => $_POST['ap_title'] ?? 'Fix gap',
            'recommendation' => $_POST['ap_recommendation'] ?? null,
            'priority'       => $_POST['ap_priority'] ?? 'medium',
        ]);
        header('Location: ' . url('action-plans') . '?created=' . $apId);
        exit;
    }
}

// Re-run or use cached
$forceRefresh = isset($_GET['refresh']);
$analysis     = ($forceRefresh || !GapAnalyzer::loadCached($activeCompanyId, $framework, $period))
                ? GapAnalyzer::analyze($activeCompanyId, $framework, $period)
                : GapAnalyzer::loadCached($activeCompanyId, $framework, $period);

// Filter controls
$filterPriority = $_GET['priority'] ?? 'all';
$filterCategory = strtoupper($_GET['category'] ?? 'all');

$gaps = $analysis['gaps'] ?? [];
if ($filterPriority !== 'all') {
    $gaps = array_filter($gaps, fn($g) => $g['priority'] === $filterPriority);
}
if ($filterCategory !== 'ALL') {
    $gaps = array_filter($gaps, fn($g) => $g['indicator']['category'] === $filterCategory);
}
$gaps = array_values($gaps);

$priorityCounts = [
    'critical' => count(array_filter($analysis['gaps'] ?? [], fn($g) => $g['priority'] === 'critical')),
    'high'     => count(array_filter($analysis['gaps'] ?? [], fn($g) => $g['priority'] === 'high')),
    'medium'   => count(array_filter($analysis['gaps'] ?? [], fn($g) => $g['priority'] === 'medium')),
    'low'      => count(array_filter($analysis['gaps'] ?? [], fn($g) => $g['priority'] === 'low')),
];

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-bar-chart-steps me-2 text-warning"></i>Gap Analysis</h1>
        <span class="topbar-subtitle"><?= $framework === 'BURSA_SEDG' ? 'Bursa SEDG' : ($framework === 'GRI' ? 'GRI' : 'SEDG + GRI') ?> — <?= $period ?></span>
      </div>
      <div class="topbar-actions">
        <a href="?refresh=1" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </a>
        <a href="<?= url('reports') ?>?action=generate" class="btn btn-primary btn-sm">
          <i class="bi bi-file-earmark-plus me-1"></i>Generate Report
        </a>
      </div>
    </div>

    <div class="content-body">

      <!-- Score Summary -->
      <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
          <div class="gap-score-card">
            <div class="gap-score-num"><?= $analysis['score'] ?>%</div>
            <div class="gap-score-label">Overall Completion</div>
            <div class="progress mt-2" style="height:8px">
              <div class="progress-bar bg-success" style="width:<?= $analysis['score'] ?>%"></div>
            </div>
            <div class="small text-muted mt-1"><?= $analysis['completed'] ?>/<?= $analysis['total_indicators'] ?> indicators</div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="gap-score-card border-success">
            <div class="gap-score-num text-success"><?= $analysis['env_score'] ?>%</div>
            <div class="gap-score-label"><i class="bi bi-tree me-1"></i>Environment</div>
            <div class="progress mt-2" style="height:8px">
              <div class="progress-bar bg-success" style="width:<?= $analysis['env_score'] ?>%"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="gap-score-card border-info">
            <div class="gap-score-num text-info"><?= $analysis['social_score'] ?>%</div>
            <div class="gap-score-label"><i class="bi bi-people me-1"></i>Social</div>
            <div class="progress mt-2" style="height:8px">
              <div class="progress-bar bg-info" style="width:<?= $analysis['social_score'] ?>%"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="gap-score-card border-purple">
            <div class="gap-score-num text-purple"><?= $analysis['gov_score'] ?>%</div>
            <div class="gap-score-label"><i class="bi bi-shield-check me-1"></i>Governance</div>
            <div class="progress mt-2" style="height:8px">
              <div class="progress-bar bg-purple" style="width:<?= $analysis['gov_score'] ?>%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Priority filter -->
      <div class="filter-bar mb-3">
        <div class="filter-label">Filter by Priority:</div>
        <div class="filter-pills">
          <a href="?priority=all&category=<?= strtolower($filterCategory) ?>"
             class="filter-pill <?= $filterPriority === 'all' ? 'active' : '' ?>">
            All (<?= count($analysis['gaps'] ?? []) ?>)
          </a>
          <a href="?priority=critical&category=<?= strtolower($filterCategory) ?>"
             class="filter-pill pill-critical <?= $filterPriority === 'critical' ? 'active' : '' ?>">
            Critical (<?= $priorityCounts['critical'] ?>)
          </a>
          <a href="?priority=high&category=<?= strtolower($filterCategory) ?>"
             class="filter-pill pill-high <?= $filterPriority === 'high' ? 'active' : '' ?>">
            High (<?= $priorityCounts['high'] ?>)
          </a>
          <a href="?priority=medium&category=<?= strtolower($filterCategory) ?>"
             class="filter-pill pill-medium <?= $filterPriority === 'medium' ? 'active' : '' ?>">
            Medium (<?= $priorityCounts['medium'] ?>)
          </a>
          <a href="?priority=low&category=<?= strtolower($filterCategory) ?>"
             class="filter-pill pill-low <?= $filterPriority === 'low' ? 'active' : '' ?>">
            Low (<?= $priorityCounts['low'] ?>)
          </a>
        </div>
        <div class="filter-pills ms-3">
          <a href="?priority=<?= $filterPriority ?>&category=all" class="filter-pill <?= $filterCategory === 'ALL' ? 'active' : '' ?>">All Categories</a>
          <a href="?priority=<?= $filterPriority ?>&category=environment" class="filter-pill <?= $filterCategory === 'ENVIRONMENT' ? 'active' : '' ?>">Environment</a>
          <a href="?priority=<?= $filterPriority ?>&category=social" class="filter-pill <?= $filterCategory === 'SOCIAL' ? 'active' : '' ?>">Social</a>
          <a href="?priority=<?= $filterPriority ?>&category=governance" class="filter-pill <?= $filterCategory === 'GOVERNANCE' ? 'active' : '' ?>">Governance</a>
        </div>
      </div>

      <!-- Gap list -->
      <?php if (empty($gaps)): ?>
      <div class="empty-state">
        <i class="bi bi-check-circle-fill text-success fs-1"></i>
        <h3 class="mt-3">No gaps found for this filter!</h3>
        <p class="text-muted">Try a different filter or <a href="<?= url('data-entry') ?>">continue entering data</a>.</p>
      </div>
      <?php else: ?>
      <div class="gap-list">
        <?php foreach ($gaps as $gap):
          $ind      = $gap['indicator'];
          $catColor = ['ENVIRONMENT' => 'success', 'SOCIAL' => 'info', 'GOVERNANCE' => 'purple'][$ind['category']] ?? 'secondary';
          $priClass = ['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'secondary'][$gap['priority']] ?? 'secondary';
        ?>
        <div class="gap-item gap-priority-<?= $gap['priority'] ?>">
          <div class="gap-item-header">
            <div class="gap-badges">
              <span class="badge bg-<?= $priClass ?>"><?= strtoupper($gap['priority']) ?></span>
              <span class="badge bg-<?= $catColor ?> bg-opacity-25 text-<?= $catColor ?>"><?= $ind['category'] ?></span>
              <?php if ($ind['required']): ?>
              <span class="badge bg-danger bg-opacity-10 text-danger">Required</span>
              <?php endif; ?>
              <?php if ($gap['quick_win']): ?>
              <span class="badge bg-warning text-dark"><i class="bi bi-lightning-fill me-1"></i>Quick Win</span>
              <?php endif; ?>
            </div>
            <div class="gap-code"><?= htmlspecialchars($ind['code']) ?></div>
            <div class="d-flex gap-2 align-items-center">
              <a href="<?= url('data-entry') ?>?cat=<?= strtolower($ind['category']) ?>&focus=<?= $ind['indicator_id'] ?>"
                 class="btn btn-sm btn-primary gap-fix-btn">
                <i class="bi bi-pencil me-1"></i>Enter Data
              </a>
              <button type="button" class="btn btn-sm btn-outline-warning gap-fix-btn"
                      onclick="openConvertModal(<?= htmlspecialchars(json_encode([
                          'indicator_id' => $ind['indicator_id'],
                          'title'        => 'Fix: ' . $ind['name'],
                          'recommendation' => $gap['recommendation'],
                          'priority'     => $gap['priority'],
                      ])) ?>)">
                <i class="bi bi-kanban me-1"></i>Action Plan
              </button>
            </div>
          </div>
          <h5 class="gap-name"><?= htmlspecialchars($ind['name']) ?></h5>
          <p class="gap-desc"><?= htmlspecialchars($ind['description']) ?></p>
          <div class="gap-meta">
            <div class="gap-meta-item">
              <i class="bi bi-lightbulb text-warning me-1"></i>
              <strong>Recommendation:</strong> <?= htmlspecialchars($gap['recommendation']) ?>
            </div>
            <div class="gap-meta-item">
              <i class="bi bi-graph-up text-success me-1"></i>
              <strong>Impact:</strong> <?= htmlspecialchars($gap['impact']) ?>
            </div>
            <div class="gap-meta-item">
              <i class="bi bi-clock text-muted me-1"></i>
              <strong>Effort:</strong> <?= htmlspecialchars($gap['effort']) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Financing Opportunities -->
      <?php if (!empty($analysis['financing_ops'])): ?>
      <div class="card mt-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="bi bi-currency-dollar text-success me-2"></i>Green Financing Opportunities</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead><tr><th>Programme</th><th>Benefit</th><th>Status</th><th>Requirement</th></tr></thead>
              <tbody>
                <?php foreach ($analysis['financing_ops'] as $op): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($op['name']) ?></strong></td>
                  <td class="text-success fw-semibold"><?= htmlspecialchars($op['benefit']) ?></td>
                  <td>
                    <?= $op['status'] === 'eligible'
                        ? '<span class="badge bg-success">Eligible Now</span>'
                        : '<span class="badge bg-warning text-dark">Action Required</span>' ?>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars($op['requirement']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Convert to Action Plan modal -->
<div class="modal fade" id="convertApModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="convert_gap_to_ap" value="1">
        <input type="hidden" name="indicator_id" id="apIndicatorId">
        <input type="hidden" name="ap_priority" id="apPriority">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-kanban me-2 text-warning"></i>Create Action Plan from Gap</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Title</label>
            <input type="text" class="form-control" name="ap_title" id="apTitle" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Recommendation</label>
            <textarea class="form-control" name="ap_recommendation" id="apRecommendation" rows="3"></textarea>
          </div>
          <div class="mb-0">
            <label class="form-label fw-semibold">Priority</label>
            <select class="form-select" name="ap_priority" id="apPrioritySelect">
              <option value="critical">Critical</option>
              <option value="high">High</option>
              <option value="medium" selected>Medium</option>
              <option value="low">Low</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning"><i class="bi bi-check2 me-1"></i>Create Action Plan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function openConvertModal(data) {
  document.getElementById('apIndicatorId').value = data.indicator_id;
  document.getElementById('apTitle').value = data.title;
  document.getElementById('apRecommendation').value = data.recommendation;
  document.getElementById('apPrioritySelect').value = data.priority;
  new bootstrap.Modal(document.getElementById('convertApModal')).show();
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
