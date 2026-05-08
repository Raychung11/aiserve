<?php
// Detect current page from clean URL path or query string
$_sPath = trim(str_replace(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'), '', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'), '/');
$currentPage = $_sPath ?: ($_GET['page'] ?? 'dashboard');
$currentPage = strtolower(preg_replace('/[^a-z0-9_-]/', '', explode('/', $currentPage)[0]));
$userRole    = $currentUser['role'] ?? 'sme_owner';
?>
<div class="sidebar" id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-leaf-fill"></i></div>
    <div class="brand-text">
      <div class="brand-name">AiServe ESG OS</div>
      <div class="brand-tagline">Malaysia ESG Platform</div>
    </div>
  </div>

  <!-- Active Company Selector -->
  <?php if ($activeCompany): ?>
  <div class="sidebar-company">
    <div class="company-label">Active Company</div>
    <div class="company-name"><?= htmlspecialchars($activeCompany['name']) ?></div>
    <div class="company-meta">
      <?= htmlspecialchars($activeCompany['industry']) ?>
      &bull;
      <?= $activeCompany['framework'] === 'BURSA_SEDG' ? 'Bursa SEDG' : ($activeCompany['framework'] === 'GRI' ? 'GRI' : 'SEDG + GRI') ?>
    </div>
    <?php if ($userRole === 'consultant' || $userRole === 'admin'): ?>
    <a href="<?= url('companies') ?>" class="btn btn-sm btn-outline-light mt-1 w-100">
      <i class="bi bi-arrow-repeat"></i> Switch Company
    </a>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="sidebar-company no-company">
    <i class="bi bi-building-add"></i>
    <a href="<?= url('onboarding') ?>" class="text-white">Set up your company</a>
  </div>
  <?php endif; ?>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="<?= url('dashboard') ?>" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i>
      <span>Dashboard</span>
    </a>

    <div class="nav-section-label">ESG Data</div>
    <a href="<?= url('data-entry') ?>?cat=environment" class="nav-item <?= $currentPage === 'data-entry' && ($_GET['cat'] ?? '') === 'environment' ? 'active' : '' ?>">
      <i class="bi bi-tree"></i>
      <span>Environment</span>
      <?php if ($activeCompany):
        $eStats = ESGDataManager::getCompletionStats($activeCompanyId, $activeCompany['framework']);
        $eScore = $eStats['ENVIRONMENT']['score'] ?? 0;
        $eBadge = $eScore >= 80 ? 'bg-success' : ($eScore >= 50 ? 'bg-warning' : 'bg-danger');
      ?>
      <span class="nav-badge <?= $eBadge ?>"><?= $eScore ?>%</span>
      <?php endif; ?>
    </a>
    <a href="<?= url('data-entry') ?>?cat=social" class="nav-item <?= $currentPage === 'data-entry' && ($_GET['cat'] ?? '') === 'social' ? 'active' : '' ?>">
      <i class="bi bi-people"></i>
      <span>Social</span>
      <?php if ($activeCompany):
        $sScore = $eStats['SOCIAL']['score'] ?? 0;
        $sBadge = $sScore >= 80 ? 'bg-success' : ($sScore >= 50 ? 'bg-warning' : 'bg-danger');
      ?>
      <span class="nav-badge <?= $sBadge ?>"><?= $sScore ?>%</span>
      <?php endif; ?>
    </a>
    <a href="<?= url('data-entry') ?>?cat=governance" class="nav-item <?= $currentPage === 'data-entry' && ($_GET['cat'] ?? '') === 'governance' ? 'active' : '' ?>">
      <i class="bi bi-shield-check"></i>
      <span>Governance</span>
      <?php if ($activeCompany):
        $gScore = $eStats['GOVERNANCE']['score'] ?? 0;
        $gBadge = $gScore >= 80 ? 'bg-success' : ($gScore >= 50 ? 'bg-warning' : 'bg-danger');
      ?>
      <span class="nav-badge <?= $gBadge ?>"><?= $gScore ?>%</span>
      <?php endif; ?>
    </a>

    <div class="nav-section-label">Analysis</div>
    <a href="<?= url('gap-analysis') ?>" class="nav-item <?= $currentPage === 'gap-analysis' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-steps"></i>
      <span>Gap Analysis</span>
    </a>
    <a href="<?= url('reports') ?>" class="nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-text"></i>
      <span>Reports</span>
    </a>

    <div class="nav-section-label">Tools</div>
    <a href="<?= url('carbon') ?>" class="nav-item <?= $currentPage === 'carbon' ? 'active' : '' ?>">
      <i class="bi bi-calculator"></i>
      <span>Carbon Calculator</span>
    </a>
    <a href="<?= url('benchmarking') ?>" class="nav-item <?= $currentPage === 'benchmarking' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-line"></i>
      <span>Benchmarking</span>
    </a>

    <?php if ($userRole === 'consultant' || $userRole === 'admin'): ?>
    <div class="nav-section-label">Consultant</div>
    <a href="<?= url('companies') ?>" class="nav-item <?= $currentPage === 'companies' ? 'active' : '' ?>">
      <i class="bi bi-buildings"></i>
      <span>My Companies</span>
    </a>
    <?php endif; ?>

    <?php if ($userRole === 'admin'): ?>
    <div class="nav-section-label">System</div>
    <a href="<?= url('admin') ?>" class="nav-item <?= $currentPage === 'admin' ? 'active' : '' ?>">
      <i class="bi bi-shield-lock"></i>
      <span>Admin Panel</span>
    </a>
    <?php endif; ?>
  </nav>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer">
    <!-- Plan badge -->
    <?php if ($activeCompanyId): ?>
    <?php
    $sp = $activePlan ?? Subscription::getAllPlans()['starter'];
    $planSrc = $sp['_source'] ?? 'free';
    ?>
    <a href="<?= url('billing') ?>" class="sidebar-plan-badge"
       style="--plan-color:<?= $sp['color'] ?>">
      <span class="spb-name"><?= $sp['name'] ?></span>
      <span class="spb-label">
        <?php if ($planSrc === 'trial'): ?>
        <i class="bi bi-hourglass-split"></i> <?= ($sp['_days_left'] ?? 0) ?>d trial
        <?php elseif ($planSrc === 'subscription'): ?>
        <i class="bi bi-check-circle-fill"></i> Active
        <?php else: ?>
        <i class="bi bi-arrow-up-circle"></i> Upgrade
        <?php endif; ?>
      </span>
    </a>
    <?php endif; ?>

    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
      <div class="user-detail">
        <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
        <div class="user-role"><?= ucfirst(str_replace('_', ' ', $currentUser['role'])) ?></div>
      </div>
    </div>
    <a href="<?= url('logout') ?>" class="nav-item nav-logout">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </a>
  </div>
</div>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
