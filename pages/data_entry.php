<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding');
    exit;
}

$cat       = strtoupper($_GET['cat'] ?? 'environment');
$framework = $activeCompany['framework'];
$period    = $activeCompany['reporting_year'];
$focusId   = $_GET['focus'] ?? null;

// Map URL param to category enum
$catMap = ['environment' => 'ENVIRONMENT', 'social' => 'SOCIAL', 'governance' => 'GOVERNANCE'];
$category = $catMap[strtolower($cat)] ?? 'ENVIRONMENT';
$catDisplay = ucfirst(strtolower($category));

$pageTitle = $catDisplay . ' Data Entry';
$success   = '';
$error     = '';

// Handle bulk save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh and try again.';
    } else {
        $result = ESGDataManager::saveBulk(
            $activeCompanyId,
            $framework,
            $category,
            $_POST,
            $currentUser['id'],
            $period
        );
        if ($result['success']) {
            $success = 'Saved ' . $result['saved'] . ' indicators successfully!';
        } else {
            $error   = 'Saved ' . $result['saved'] . ' indicators. Errors: ' . implode(', ', $result['errors']);
        }
    }
}

// Load indicators for this category
$allIndicators = Company::getFrameworkIndicators($framework);
$catIndicators = array_filter($allIndicators, fn($i) => $i['category'] === $category);
$savedData     = ESGDataManager::getByCategory($activeCompanyId, $category, $period);
$stats         = ESGDataManager::getCompletionStats($activeCompanyId, $framework, $period);

// Separate required vs recommended
$required    = array_values(array_filter($catIndicators, fn($i) => $i['required']));
$recommended = array_values(array_filter($catIndicators, fn($i) => !$i['required']));

$catIcons = ['ENVIRONMENT' => 'bi-tree text-success', 'SOCIAL' => 'bi-people text-info', 'GOVERNANCE' => 'bi-shield-check text-purple'];
$catColors = ['ENVIRONMENT' => 'success', 'SOCIAL' => 'info', 'GOVERNANCE' => 'purple'];

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi <?= $catIcons[$category] ?? '' ?> me-2"></i><?= $catDisplay ?> Data Entry</h1>
        <span class="topbar-subtitle"><?= $framework === 'BURSA_SEDG' ? 'Bursa SEDG' : ($framework === 'GRI' ? 'GRI' : 'SEDG + GRI') ?> — <?= $period ?></span>
      </div>
      <div class="topbar-actions">
        <!-- Tab navigation for E/S/G -->
        <div class="cat-tabs">
          <a href="?cat=environment" class="cat-tab <?= $category === 'ENVIRONMENT' ? 'active' : '' ?>">
            <i class="bi bi-tree"></i> Environment
            <span class="tab-badge bg-<?= $stats['ENVIRONMENT']['score'] >= 80 ? 'success' : ($stats['ENVIRONMENT']['score'] >= 50 ? 'warning' : 'danger') ?>"><?= $stats['ENVIRONMENT']['score'] ?>%</span>
          </a>
          <a href="?cat=social" class="cat-tab <?= $category === 'SOCIAL' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Social
            <span class="tab-badge bg-<?= $stats['SOCIAL']['score'] >= 80 ? 'success' : ($stats['SOCIAL']['score'] >= 50 ? 'warning' : 'danger') ?>"><?= $stats['SOCIAL']['score'] ?>%</span>
          </a>
          <a href="?cat=governance" class="cat-tab <?= $category === 'GOVERNANCE' ? 'active' : '' ?>">
            <i class="bi bi-shield-check"></i> Governance
            <span class="tab-badge bg-<?= $stats['GOVERNANCE']['score'] >= 80 ? 'success' : ($stats['GOVERNANCE']['score'] >= 50 ? 'warning' : 'danger') ?>"><?= $stats['GOVERNANCE']['score'] ?>%</span>
          </a>
        </div>
      </div>
    </div>

    <div class="content-body">

      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <!-- Progress bar for this category -->
      <div class="cat-progress-bar mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold"><?= $catDisplay ?> Completion</span>
          <span class="fw-bold text-<?= $catColors[$category] ?>"><?= $stats[$category]['score'] ?>%</span>
        </div>
        <div class="progress" style="height: 10px">
          <div class="progress-bar bg-<?= $catColors[$category] ?>" style="width:<?= $stats[$category]['score'] ?>%"
               role="progressbar"></div>
        </div>
        <div class="d-flex justify-content-between small text-muted mt-1">
          <span><?= $stats[$category]['completed'] ?> of <?= $stats[$category]['total'] ?> indicators entered</span>
          <span><?= $stats[$category]['required_done'] ?>/<?= $stats[$category]['required_total'] ?> mandatory disclosures done</span>
        </div>
      </div>

      <form method="POST" action="?cat=<?= strtolower($category) ?>" id="dataEntryForm">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

        <?php
        function renderIndicatorField(array $ind, array $savedData, ?string $focusId, array|string $unlockedIds = 'all'): void {
            $id       = $ind['indicator_id'];
            $isLocked = $unlockedIds !== 'all' && !in_array($id, (array)$unlockedIds, true);
            $saved    = $savedData[$id] ?? null;
            $val      = $saved['value'] ?? '';
            $isFocus  = $focusId === $id;
            $hasVal   = $val !== '' && $val !== null;
            $statusIcon = $isLocked ? 'bi-lock-fill text-muted'
                : ($hasVal ? 'bi-check-circle-fill text-success'
                : ($ind['required'] ? 'bi-exclamation-circle-fill text-danger' : 'bi-circle text-muted'));
        ?>
        <div class="indicator-card <?= $isFocus ? 'highlight-focus' : '' ?> <?= $isLocked ? 'indicator-locked' : '' ?>"
             id="field-<?= $id ?>" data-indicator="<?= $id ?>">
          <div class="ind-header">
            <div class="ind-code"><?= htmlspecialchars($ind['code']) ?></div>
            <div class="ind-title-wrap">
              <span class="ind-name"><?= htmlspecialchars($ind['name']) ?></span>
              <?php if ($ind['required']): ?>
              <span class="badge bg-danger ms-2">Required</span>
              <?php else: ?>
              <span class="badge bg-secondary ms-2">Recommended</span>
              <?php endif; ?>
            </div>
            <i class="bi <?= $statusIcon ?> ind-status-icon"></i>
          </div>
          <p class="ind-desc"><?= htmlspecialchars($ind['description']) ?></p>
          <?php if ($isLocked): ?>
          <div class="ind-lock-overlay">
            <i class="bi bi-lock-fill"></i>
            <strong>Indicator locked</strong>
            <p class="mb-2 small">Upgrade your plan or add an Indicator Collection to unlock this disclosure.</p>
            <a href="<?= APP_URL ?>/billing" class="btn btn-sm btn-primary">
              <i class="bi bi-arrow-up-circle me-1"></i>Upgrade Plan
            </a>
            <a href="<?= APP_URL ?>/pricing" class="btn btn-sm btn-outline-secondary ms-1" target="_blank">View Pricing</a>
          </div>
          <?php endif; ?>
          <div class="ind-guidance"><i class="bi bi-lightbulb me-1 text-warning"></i><?= htmlspecialchars($ind['guidance']) ?></div>

          <div class="row mt-3">
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-semibold">
                Value <?php if ($ind['unit']): ?><span class="text-muted">(<?= htmlspecialchars($ind['unit']) ?>)</span><?php endif; ?>
              </label>
              <?php if ($ind['data_type'] === 'boolean'): ?>
              <select class="form-select" name="<?= $id ?>">
                <option value="">— Select —</option>
                <option value="Yes" <?= $val === 'Yes' ? 'selected' : '' ?>>Yes</option>
                <option value="No"  <?= $val === 'No'  ? 'selected' : '' ?>>No</option>
              </select>
              <?php elseif ($ind['data_type'] === 'text'): ?>
              <textarea class="form-control" name="<?= $id ?>" rows="2"
                        placeholder="Describe..."><?= htmlspecialchars($val) ?></textarea>
              <?php elseif ($ind['data_type'] === 'percentage'): ?>
              <div class="input-group">
                <input type="number" class="form-control" name="<?= $id ?>"
                       step="0.01" min="0" max="100" placeholder="0–100"
                       value="<?= htmlspecialchars($val) ?>">
                <span class="input-group-text">%</span>
              </div>
              <?php else: ?>
              <div class="input-group">
                <input type="number" class="form-control" name="<?= $id ?>"
                       step="0.01" min="0" placeholder="Enter value"
                       value="<?= htmlspecialchars($val) ?>">
                <?php if ($ind['unit']): ?>
                <span class="input-group-text"><?= htmlspecialchars($ind['unit']) ?></span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-semibold">Data Source</label>
              <input type="text" class="form-control" name="__source_<?= $id ?>"
                     placeholder="e.g. TNB bills, payroll"
                     value="<?= htmlspecialchars($source) ?>">
            </div>
            <div class="col-md-3 mb-2">
              <label class="form-label small fw-semibold">Notes</label>
              <input type="text" class="form-control" name="__notes_<?= $id ?>"
                     placeholder="Optional notes"
                     value="<?= htmlspecialchars($notes) ?>">
            </div>
            <div class="col-md-1 mb-2 d-flex align-items-end justify-content-center">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="__verified_<?= $id ?>" id="v_<?= $id ?>"
                       <?= $verified ? 'checked' : '' ?>>
                <label class="form-check-label small" for="v_<?= $id ?>" title="Mark as verified">
                  <i class="bi bi-patch-check" data-bs-toggle="tooltip" title="Verified data"></i>
                </label>
              </div>
            </div>
          </div>

          <?php if ($ind['financing_link']): ?>
          <div class="ind-financing-tip">
            <i class="bi bi-currency-dollar text-success me-1"></i>
            <strong>Financing link:</strong> <?= htmlspecialchars($ind['financing_link']) ?>
          </div>
          <?php endif; ?>
        </div>
        <?php } // end renderIndicatorField ?>

        <!-- Required Indicators -->
        <div class="section-divider">
          <span><i class="bi bi-exclamation-circle-fill text-danger me-2"></i>Required Disclosures (<?= count($required) ?>)</span>
        </div>
        <?php foreach ($required as $ind): ?>
          <?php renderIndicatorField($ind, $savedData, $focusId, $unlockedIds); ?>
        <?php endforeach; ?>

        <!-- Recommended Indicators -->
        <?php if (!empty($recommended)): ?>
        <div class="section-divider mt-4">
          <span><i class="bi bi-star text-warning me-2"></i>Recommended Disclosures (<?= count($recommended) ?>)</span>
          <button type="button" class="btn btn-xs btn-outline-secondary" onclick="toggleRecommended()">
            <i class="bi bi-chevron-down" id="recToggleIcon"></i> Show/Hide
          </button>
        </div>
        <div id="recommendedSection">
          <?php foreach ($recommended as $ind): ?>
            <?php renderIndicatorField($ind, $savedData, $focusId, $unlockedIds); ?>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Save button -->
        <div class="save-bar">
          <div class="save-bar-info">
            <i class="bi bi-floppy me-2"></i>
            All changes saved to your secure ESG database.
          </div>
          <div class="save-bar-actions">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="bi bi-check-lg me-2"></i>Save All Changes
            </button>
            <?php
            $nextCats = ['ENVIRONMENT' => 'social', 'SOCIAL' => 'governance', 'GOVERNANCE' => 'gap-analysis'];
            $nextCat  = $nextCats[$category] ?? 'gap-analysis';
            $nextLabel = $nextCat === 'gap-analysis' ? 'View Gap Analysis' : 'Next: ' . ucfirst($nextCat);
            ?>
            <a href="<?= url($nextCat === 'gap-analysis' ? 'gap-analysis' : 'data-entry?cat=' . $nextCat) ?>"
               class="btn btn-outline-secondary btn-lg ms-2">
              <?= $nextLabel ?> <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
function toggleRecommended() {
  const sec = document.getElementById('recommendedSection');
  const icon = document.getElementById('recToggleIcon');
  if (sec.style.display === 'none') {
    sec.style.display = '';
    icon.className = 'bi bi-chevron-down';
  } else {
    sec.style.display = 'none';
    icon.className = 'bi bi-chevron-right';
  }
}

// Scroll to focused indicator
<?php if ($focusId): ?>
document.getElementById('field-<?= htmlspecialchars($focusId) ?>')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
<?php endif; ?>

// Initialize tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

// Auto-save indicator on change (optional UX enhancement)
let saveTimer;
document.querySelectorAll('.indicator-card input, .indicator-card select, .indicator-card textarea').forEach(function(el) {
  el.addEventListener('change', function() {
    const card = this.closest('.indicator-card');
    if (card) {
      card.classList.add('unsaved');
      clearTimeout(saveTimer);
      saveTimer = setTimeout(function() {
        // Visual cue only — actual save on submit
      }, 500);
    }
  });
});
</script>
