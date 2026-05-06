<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding');
    exit;
}

$pageTitle = 'Reports';
$success   = $error = '';
$action    = $_GET['action'] ?? 'list';
$reportId  = (int)($_GET['id'] ?? 0);

// Generate new report
if ($action === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired.';
    } else {
        $genFramework = $_POST['framework'] ?? $activeCompany['framework'];
        $genPeriod    = $_POST['period'] ?? $activeCompany['reporting_year'];
        $result = ReportGenerator::generate($activeCompanyId, $genFramework, $genPeriod, $currentUser['id']);
        if ($result['success']) {
            header('Location: ' . APP_URL . '/reports?action=view&id=' . $result['report_id'] . '&generated=1');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// View existing report
if ($action === 'view' && $reportId) {
    $report = Database::fetchOne(
        'SELECT * FROM reports WHERE id = ? AND company_id = ?',
        [$reportId, $activeCompanyId]
    );
    if (!$report) {
        $action = 'list';
    }
}

// List reports
$reports = Database::fetchAll(
    'SELECT r.*, u.name AS generated_by_name FROM reports r
     LEFT JOIN users u ON r.generated_by = u.id
     WHERE r.company_id = ? ORDER BY r.generated_at DESC',
    [$activeCompanyId]
);

include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-file-earmark-text me-2 text-primary"></i>ESG Reports</h1>
        <span class="topbar-subtitle"><?= htmlspecialchars($activeCompany['name']) ?></span>
      </div>
      <div class="topbar-actions">
        <a href="?action=generate" class="btn btn-primary btn-sm">
          <i class="bi bi-file-earmark-plus me-1"></i>New Report
        </a>
      </div>
    </div>

    <div class="content-body">

      <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['generated'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        Report generated successfully! You can print or save it as PDF using <strong>Ctrl+P</strong>.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <?php if ($action === 'generate'): ?>
      <!-- Generate form -->
      <div class="report-generate-box">
        <h4><i class="bi bi-magic me-2"></i>Generate ESG Compliance Report</h4>
        <p class="text-muted">Your report will be aligned to your chosen framework with all entered data, gap analysis, and financing opportunities.</p>

        <form method="POST" action="?action=generate">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Framework</label>
              <select class="form-select" name="framework">
                <?php foreach (Company::getAllFrameworks() as $fw): ?>
                <option value="<?= $fw['id'] ?>" <?= $activeCompany['framework'] === $fw['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($fw['short']) ?> — <?= htmlspecialchars($fw['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Reporting Period</label>
              <select class="form-select" name="period">
                <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $activeCompany['reporting_year'] == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
              <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="bi bi-magic me-2"></i>Generate Report
              </button>
            </div>
          </div>
          <div class="report-what-inside">
            <strong>Report includes:</strong>
            <ul class="mb-0 mt-1">
              <li>Executive Summary with ESG Score</li>
              <li>Full disclosure table (all indicators) per category</li>
              <li>Gap analysis with prioritized recommendations</li>
              <li>Green financing opportunities</li>
              <li>Print-ready / PDF-ready format</li>
            </ul>
          </div>
        </form>
      </div>

      <?php elseif ($action === 'view' && isset($report)): ?>
      <!-- View report -->
      <div class="report-toolbar mb-3">
        <a href="<?= url('reports') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Back to Reports
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
          <i class="bi bi-printer me-1"></i>Print / Save PDF
        </button>
        <span class="text-muted ms-3 small">
          Score: <strong><?= $report['score'] ?>%</strong> |
          Framework: <strong><?= $report['framework'] ?></strong> |
          Generated: <?= date('d M Y', strtotime($report['generated_at'])) ?>
        </span>
      </div>
      <div class="report-preview-frame">
        <?= $report['content_html'] ?>
      </div>

      <?php else: ?>
      <!-- Reports list -->
      <?php if (empty($reports)): ?>
      <div class="empty-state">
        <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
        <h3 class="mt-3">No reports yet</h3>
        <p class="text-muted">Generate your first ESG compliance report.</p>
        <a href="?action=generate" class="btn btn-primary mt-2">
          <i class="bi bi-magic me-2"></i>Generate First Report
        </a>
      </div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($reports as $rep):
          $repScore     = (float)$rep['score'];
          $repScoreInfo = ESGDataManager::scoreLabel($repScore);
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="report-card">
            <div class="report-card-header" style="border-left: 4px solid <?= $repScoreInfo['color'] ?>">
              <div class="report-score" style="color: <?= $repScoreInfo['color'] ?>"><?= $repScore ?>%</div>
              <div class="report-info">
                <div class="report-framework-badge"><?= $rep['framework'] ?></div>
                <div class="report-period"><?= $rep['period'] ?></div>
              </div>
            </div>
            <div class="report-card-body">
              <p class="report-title"><?= htmlspecialchars($rep['title']) ?></p>
              <div class="report-meta">
                <small><i class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($rep['generated_at'])) ?></small>
                <?php if ($rep['generated_by_name']): ?>
                <small><i class="bi bi-person me-1"></i><?= htmlspecialchars($rep['generated_by_name']) ?></small>
                <?php endif; ?>
              </div>
            </div>
            <div class="report-card-footer">
              <a href="?action=view&id=<?= $rep['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-eye me-1"></i>View Report
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- New report card -->
        <div class="col-md-6 col-lg-4">
          <a href="?action=generate" class="report-new-card">
            <i class="bi bi-file-earmark-plus fs-1"></i>
            <div class="mt-2">Generate New Report</div>
          </a>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<style>
@media print {
  .app-layout { display: block !important; }
  .sidebar, .topbar, .report-toolbar, .alert { display: none !important; }
  .main-content { margin: 0 !important; padding: 0 !important; }
  .content-body { padding: 0 !important; }
  .report-preview-frame { border: none !important; padding: 0 !important; }
}
</style>
