<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Admins go to admin panel companies tab
if ($currentUser['role'] === 'admin') {
    header('Location: ' . APP_URL . '/admin?tab=companies');
    exit;
}

$pageTitle   = 'My Companies';
$success     = $error = '';
$role        = $currentUser['role'];
$isHierarchy = in_array($role, ['principal', 'associate', 'manager']);

// Handle company switch / open
if (isset($_GET['switch']) && (int)$_GET['switch'] > 0) {
    $switchId = (int)$_GET['switch'];
    // Hierarchy roles: verify access via Hierarchy
    if ($isHierarchy) {
        $accessible = Hierarchy::getAccessibleCompanies($currentUser['id'], $role);
        $found = array_filter($accessible, fn($c) => (int)$c['id'] === $switchId);
        if ($found) {
            Auth::setActiveCompany($switchId);
            $activeCompanyId = $switchId;
            $activeCompany   = Company::getById($switchId, $currentUser['id'], $role);
            $success = 'Switched to ' . htmlspecialchars($activeCompany['name'] ?? '');
        }
    } else {
        $co = Company::getById($switchId, $currentUser['id'], $role);
        if ($co) {
            Auth::setActiveCompany($switchId);
            $activeCompanyId = $switchId;
            $activeCompany   = $co;
            $success = 'Switched to ' . htmlspecialchars($co['name']);
        }
    }
}

// Handle delete (owner only)
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0 && Auth::verifyCsrf($_GET['token'] ?? '')) {
    $delId = (int)$_GET['delete'];
    Database::query('DELETE FROM companies WHERE id = ? AND created_by = ?', [$delId, $currentUser['id']]);
    $success = 'Company removed.';
}

// Load companies based on role
$companies = $isHierarchy
    ? Hierarchy::getAccessibleCompanies($currentUser['id'], $role)
    : Company::getForUser($currentUser['id'], $role);

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-buildings me-2 text-primary"></i><?= $isHierarchy ? 'All Client Companies' : 'My Companies' ?></h1>
        <span class="topbar-subtitle"><?= ucfirst(str_replace('_',' ',$role)) ?> portfolio view &mdash; <?= count($companies) ?> companies</span>
      </div>
      <div class="topbar-actions">
        <?php if (!$isHierarchy): ?>
        <a href="<?= url('onboarding') ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-building-add me-1"></i>Add New Company
        </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="content-body">
      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <!-- Stats row -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="stat-card">
            <div class="stat-icon text-primary"><i class="bi bi-buildings"></i></div>
            <div class="stat-num"><?= count($companies) ?></div>
            <div class="stat-label">Total Companies</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card">
            <div class="stat-icon text-success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-num">
              <?= count(array_filter($companies, function($c) {
                  $s = ESGDataManager::getCompletionStats($c['id'], $c['framework']);
                  return ESGDataManager::calcOverallScore($s) >= 60;
              })) ?>
            </div>
            <div class="stat-label">Companies ≥ 60% Score</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card">
            <div class="stat-icon text-warning"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-num">
              <?= count(array_filter($companies, function($c) {
                  $s = ESGDataManager::getCompletionStats($c['id'], $c['framework']);
                  return ESGDataManager::calcOverallScore($s) < 40;
              })) ?>
            </div>
            <div class="stat-label">Companies Needing Attention</div>
          </div>
        </div>
      </div>

      <!-- Companies grid -->
      <?php if (empty($companies)): ?>
      <div class="empty-state">
        <i class="bi bi-buildings fs-1 text-muted"></i>
        <h3 class="mt-3">No companies yet</h3>
        <?php if ($isHierarchy): ?>
        <p class="text-muted">No client companies are assigned to your portfolio yet.</p>
        <?php else: ?>
        <p class="text-muted">Add your first client company to get started.</p>
        <a href="<?= url('onboarding') ?>" class="btn btn-primary mt-2">
          <i class="bi bi-building-add me-2"></i>Add First Company
        </a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($companies as $co):
          $coStats     = ESGDataManager::getCompletionStats($co['id'], $co['framework'], $co['reporting_year']);
          $coScore     = ESGDataManager::calcOverallScore($coStats);
          $coScoreInfo = ESGDataManager::scoreLabel($coScore);
          $isActive    = $activeCompanyId === (int)$co['id'];
          $revDisplay  = str_replace(['below_10M','10M_to_50M','above_50M'], ['<RM10M','RM10–50M','>RM50M'], $co['revenue_tier']);
        ?>
        <div class="col-md-6 col-xl-4">
          <div class="company-card <?= $isActive ? 'company-card-active' : '' ?>">
            <div class="company-card-header">
              <div>
                <h5 class="company-name"><?= htmlspecialchars($co['name']) ?></h5>
                <div class="company-meta-row">
                  <span class="company-badge"><?= htmlspecialchars($co['industry']) ?></span>
                  <?php if (!empty($co['bursa_sector'])): ?>
                  <span class="company-badge" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe">
                    <i class="bi bi-buildings me-1" style="font-size:10px"></i><?= htmlspecialchars($co['bursa_sector']) ?>
                  </span>
                  <?php endif; ?>
                  <span class="company-badge"><?= $revDisplay ?></span>
                  <span class="company-badge"><?= number_format($co['employee_count']) ?> employees</span>
                  <?php if ($co['is_pre_ipo']): ?>
                  <span class="company-badge badge-ipo">Pre-IPO</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="company-score" style="color: <?= $coScoreInfo['color'] ?>">
                <div class="cs-num"><?= $coScore ?>%</div>
                <div class="cs-label"><?= $coScoreInfo['label'] ?></div>
              </div>
            </div>
            <div class="company-card-body">
              <div class="framework-tag"><?= $co['framework'] === 'BURSA_SEDG' ? 'Bursa SEDG' : ($co['framework'] === 'GRI' ? 'GRI' : 'SEDG + GRI') ?> — <?= $co['reporting_year'] ?></div>
              <div class="esg-mini-bars">
                <div class="mini-bar">
                  <span>E</span>
                  <div class="progress flex-grow-1" style="height:6px">
                    <div class="progress-bar bg-success" style="width:<?= $coStats['ENVIRONMENT']['score'] ?>%"></div>
                  </div>
                  <span><?= $coStats['ENVIRONMENT']['score'] ?>%</span>
                </div>
                <div class="mini-bar">
                  <span>S</span>
                  <div class="progress flex-grow-1" style="height:6px">
                    <div class="progress-bar bg-info" style="width:<?= $coStats['SOCIAL']['score'] ?>%"></div>
                  </div>
                  <span><?= $coStats['SOCIAL']['score'] ?>%</span>
                </div>
                <div class="mini-bar">
                  <span>G</span>
                  <div class="progress flex-grow-1" style="height:6px">
                    <div class="progress-bar bg-purple" style="width:<?= $coStats['GOVERNANCE']['score'] ?>%"></div>
                  </div>
                  <span><?= $coStats['GOVERNANCE']['score'] ?>%</span>
                </div>
              </div>
            </div>
            <div class="company-card-footer">
              <?php if (!$isActive): ?>
              <a href="?switch=<?= $co['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-right-circle me-1"></i>Switch to
              </a>
              <?php else: ?>
              <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
              <?php endif; ?>
              <a href="<?= url('dashboard') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
              </a>
              <a href="<?= url('gap-analysis') ?>" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-bar-chart-steps me-1"></i>Gaps
              </a>
              <?php if ($co['created_by'] == $currentUser['id']): ?>
              <a href="?delete=<?= $co['id'] ?>&token=<?= Auth::csrfToken() ?>"
                 class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('Remove this company?')">
                <i class="bi bi-trash"></i>
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Add new card (consultant/sme_owner only) -->
        <?php if (!$isHierarchy): ?>
        <div class="col-md-6 col-xl-4">
          <a href="<?= url('onboarding') ?>" class="add-company-card">
            <i class="bi bi-building-add fs-1"></i>
            <div class="mt-2 fw-semibold">Add New Company</div>
            <small class="text-muted">Set up another client</small>
          </a>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
