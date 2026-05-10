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

  <!-- Active Company / Platform Label -->
  <?php if ($userRole === 'admin'): ?>
  <div class="sidebar-company" style="border-color:rgba(167,139,250,0.3)">
    <div class="company-label">Platform</div>
    <div class="company-name" style="color:#c4b5fd">Super Admin</div>
    <div class="company-meta" style="color:rgba(167,139,250,0.7)">Full platform access</div>
  </div>
  <?php elseif ($activeCompany): ?>
  <div class="sidebar-company">
    <div class="company-label">Viewing Company</div>
    <div class="company-name"><?= htmlspecialchars($activeCompany['name']) ?></div>
    <div class="company-meta">
      <?= htmlspecialchars($activeCompany['industry']) ?>
      &bull;
      <?= $activeCompany['framework'] === 'BURSA_SEDG' ? 'Bursa SEDG' : ($activeCompany['framework'] === 'GRI' ? 'GRI' : 'SEDG + GRI') ?>
    </div>
    <?php if (in_array($userRole, ['consultant','principal','associate','manager'])): ?>
    <a href="<?= url('companies') ?>" class="btn btn-sm btn-outline-light mt-1 w-100">
      <i class="bi bi-arrow-repeat"></i> Switch Company
    </a>
    <?php endif; ?>
  </div>
  <?php elseif ($userRole === 'sme_owner'): ?>
  <div class="sidebar-company no-company">
    <i class="bi bi-building-add"></i>
    <a href="<?= url('onboarding') ?>" class="text-white">Set up your company</a>
  </div>
  <?php elseif (in_array($userRole, ['principal','associate','manager','consultant'])): ?>
  <div class="sidebar-company no-company" style="opacity:0.7">
    <i class="bi bi-buildings" style="font-size:16px;margin-bottom:4px;display:block"></i>
    <span style="font-size:11px;color:rgba(255,255,255,0.6)">No company selected<br>Open one from your dashboard</span>
  </div>
  <?php endif; ?>

  <!-- Navigation -->
  <nav class="sidebar-nav">

  <?php if ($userRole === 'admin'): ?>
    <!-- ── Admin-only navigation ── -->
    <?php $adminTab = $_GET['tab'] ?? 'overview'; ?>
    <div class="nav-section-label">Admin</div>
    <a href="<?= url('admin') ?>" class="nav-item <?= $currentPage === 'admin' && $adminTab === 'overview' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i><span>Overview</span>
    </a>

    <div class="nav-section-label">Platform Data</div>
    <a href="<?= url('admin') ?>?tab=companies" class="nav-item <?= $currentPage === 'admin' && in_array($adminTab, ['companies','company']) ? 'active' : '' ?>">
      <i class="bi bi-buildings"></i><span>Companies</span>
    </a>
    <a href="<?= url('admin') ?>?tab=users" class="nav-item <?= $currentPage === 'admin' && $adminTab === 'users' ? 'active' : '' ?>">
      <i class="bi bi-people"></i><span>Users</span>
    </a>
    <a href="<?= url('admin') ?>?tab=indicators" class="nav-item <?= $currentPage === 'admin' && $adminTab === 'indicators' ? 'active' : '' ?>">
      <i class="bi bi-list-check"></i><span>Indicators</span>
    </a>
    <a href="<?= url('admin') ?>?tab=reports" class="nav-item <?= $currentPage === 'admin' && in_array($adminTab, ['reports','report']) ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-text"></i><span>Reports</span>
    </a>

    <div class="nav-section-label">System</div>
    <a href="<?= url('admin') ?>?tab=settings" class="nav-item <?= $currentPage === 'admin' && $adminTab === 'settings' ? 'active' : '' ?>">
      <i class="bi bi-gear"></i><span>Settings</span>
    </a>
    <a href="<?= url('admin') ?>?tab=logs" class="nav-item <?= $currentPage === 'admin' && $adminTab === 'logs' ? 'active' : '' ?>">
      <i class="bi bi-journal-text"></i><span>Audit Log</span>
    </a>

  <?php elseif (in_array($userRole, ['principal', 'associate', 'manager'])): ?>
    <!-- ── Hierarchy roles: portfolio-first navigation ── -->
    <div class="nav-section-label">Main</div>
    <a href="<?= url('dashboard') ?>" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i><span>Dashboard</span>
    </a>

    <div class="nav-section-label">Portfolio</div>
    <a href="<?= url('companies') ?>" class="nav-item <?= $currentPage === 'companies' ? 'active' : '' ?>">
      <i class="bi bi-buildings"></i>
      <span><?= $userRole === 'manager' ? 'My Clients' : 'All Clients' ?></span>
    </a>

    <?php if (in_array($userRole, ['principal', 'associate'])): ?>
    <div class="nav-section-label">Team</div>
    <a href="<?= url('team') ?>" class="nav-item <?= $currentPage === 'team' ? 'active' : '' ?>">
      <i class="bi bi-people-fill"></i>
      <span><?= $userRole === 'principal' ? 'My Associates' : 'My Managers' ?></span>
    </a>
    <?php endif; ?>

    <?php if ($activeCompany): ?>
    <?php
      $eStats = ESGDataManager::getCompletionStats($activeCompanyId, $activeCompany['framework']);
      $eScore = $eStats['ENVIRONMENT']['score'] ?? 0;
      $sScore = $eStats['SOCIAL']['score'] ?? 0;
      $gScore = $eStats['GOVERNANCE']['score'] ?? 0;
    ?>
    <div class="nav-section-label">ESG — <?= htmlspecialchars(mb_substr($activeCompany['name'], 0, 16)) ?></div>
    <a href="<?= url('data-entry') ?>?cat=environment" class="nav-item <?= $currentPage === 'data-entry' && ($_GET['cat'] ?? '') === 'environment' ? 'active' : '' ?>">
      <i class="bi bi-tree"></i><span>Environment</span>
      <span class="nav-badge <?= $eScore >= 80 ? 'bg-success' : ($eScore >= 50 ? 'bg-warning' : 'bg-danger') ?>"><?= $eScore ?>%</span>
    </a>
    <a href="<?= url('data-entry') ?>?cat=social" class="nav-item <?= $currentPage === 'data-entry' && ($_GET['cat'] ?? '') === 'social' ? 'active' : '' ?>">
      <i class="bi bi-people"></i><span>Social</span>
      <span class="nav-badge <?= $sScore >= 80 ? 'bg-success' : ($sScore >= 50 ? 'bg-warning' : 'bg-danger') ?>"><?= $sScore ?>%</span>
    </a>
    <a href="<?= url('data-entry') ?>?cat=governance" class="nav-item <?= $currentPage === 'data-entry' && ($_GET['cat'] ?? '') === 'governance' ? 'active' : '' ?>">
      <i class="bi bi-shield-check"></i><span>Governance</span>
      <span class="nav-badge <?= $gScore >= 80 ? 'bg-success' : ($gScore >= 50 ? 'bg-warning' : 'bg-danger') ?>"><?= $gScore ?>%</span>
    </a>
    <div class="nav-section-label">Analysis</div>
    <a href="<?= url('gap-analysis') ?>" class="nav-item <?= $currentPage === 'gap-analysis' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-steps"></i><span>Gap Analysis</span>
    </a>
    <a href="<?= url('reports') ?>" class="nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-text"></i><span>Reports</span>
    </a>
    <div class="nav-section-label">Tools</div>
    <a href="<?= url('carbon') ?>" class="nav-item <?= $currentPage === 'carbon' ? 'active' : '' ?>">
      <i class="bi bi-calculator"></i><span>Carbon Calculator</span>
    </a>
    <a href="<?= url('benchmarking') ?>" class="nav-item <?= $currentPage === 'benchmarking' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-line"></i><span>Benchmarking</span>
    </a>
    <?php endif; // activeCompany for hierarchy ?>

  <?php else: ?>
    <!-- ── sme_owner / consultant navigation ── -->
    <div class="nav-section-label">Main</div>
    <a href="<?= url('dashboard') ?>" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i><span>Dashboard</span>
    </a>

    <?php if ($activeCompany): ?>
    <?php
      $eStats = ESGDataManager::getCompletionStats($activeCompanyId, $activeCompany['framework']);
      $eScore = $eStats['ENVIRONMENT']['score'] ?? 0;
      $sScore = $eStats['SOCIAL']['score'] ?? 0;
      $gScore = $eStats['GOVERNANCE']['score'] ?? 0;
    ?>
    <div class="nav-section-label">ESG Data</div>
    <a href="<?= url('data-entry') ?>?cat=environment" class="nav-item <?= $currentPage === 'data-entry' && ($_GET['cat'] ?? '') === 'environment' ? 'active' : '' ?>">
      <i class="bi bi-tree"></i><span>Environment</span>
      <span class="nav-badge <?= $eScore >= 80 ? 'bg-success' : ($eScore >= 50 ? 'bg-warning' : 'bg-danger') ?>"><?= $eScore ?>%</span>
    </a>
    <a href="<?= url('data-entry') ?>?cat=social" class="nav-item <?= $currentPage === 'data-entry' && ($_GET['cat'] ?? '') === 'social' ? 'active' : '' ?>">
      <i class="bi bi-people"></i><span>Social</span>
      <span class="nav-badge <?= $sScore >= 80 ? 'bg-success' : ($sScore >= 50 ? 'bg-warning' : 'bg-danger') ?>"><?= $sScore ?>%</span>
    </a>
    <a href="<?= url('data-entry') ?>?cat=governance" class="nav-item <?= $currentPage === 'data-entry' && ($_GET['cat'] ?? '') === 'governance' ? 'active' : '' ?>">
      <i class="bi bi-shield-check"></i><span>Governance</span>
      <span class="nav-badge <?= $gScore >= 80 ? 'bg-success' : ($gScore >= 50 ? 'bg-warning' : 'bg-danger') ?>"><?= $gScore ?>%</span>
    </a>

    <div class="nav-section-label">Analysis</div>
    <a href="<?= url('gap-analysis') ?>" class="nav-item <?= $currentPage === 'gap-analysis' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-steps"></i><span>Gap Analysis</span>
    </a>
    <a href="<?= url('reports') ?>" class="nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-text"></i><span>Reports</span>
    </a>

    <div class="nav-section-label">Tools</div>
    <a href="<?= url('carbon') ?>" class="nav-item <?= $currentPage === 'carbon' ? 'active' : '' ?>">
      <i class="bi bi-calculator"></i><span>Carbon Calculator</span>
    </a>
    <a href="<?= url('benchmarking') ?>" class="nav-item <?= $currentPage === 'benchmarking' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-line"></i><span>Benchmarking</span>
    </a>
    <?php endif; // activeCompany ?>

    <?php if (in_array($userRole, ['consultant', 'sme_owner'])): ?>
    <div class="nav-section-label">Portfolio</div>
    <a href="<?= url('companies') ?>" class="nav-item <?= $currentPage === 'companies' ? 'active' : '' ?>">
      <i class="bi bi-buildings"></i><span>My Companies</span>
    </a>
    <?php endif; ?>

  <?php endif; // role branches ?>

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
