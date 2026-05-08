<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../src/Benchmarker.php';

if ($currentUser['role'] !== 'admin') {
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}

$tab     = $_GET['tab'] ?? 'overview';
$viewId  = (int)($_GET['id'] ?? 0);
$fwKey   = preg_replace('/[^a-z_]/', '', strtolower($_GET['fw'] ?? 'bursa_sedg'));
$catFilter = strtoupper($_GET['cat'] ?? '');
$success = $error = '';

// ── POST handler ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired.';
    } else {
        $action   = $_POST['action'] ?? '';
        $targetId = (int)($_POST['target_id'] ?? 0);

        switch ($action) {
            case 'toggle_user':
                if ($targetId && $targetId !== $currentUser['id']) {
                    $u = Database::fetchOne('SELECT is_active FROM users WHERE id=?', [$targetId]);
                    if ($u) {
                        $ns = $u['is_active'] ? 0 : 1;
                        Database::update('users', ['is_active' => $ns], 'id=?', [$targetId]);
                        $success = 'User ' . ($ns ? 'activated' : 'deactivated') . '.';
                    }
                }
                $tab = 'users';
                break;

            case 'set_role':
                $newRole = $_POST['role'] ?? '';
                if ($targetId && $targetId !== $currentUser['id'] &&
                    in_array($newRole, ['admin','consultant','sme_owner','principal','associate','manager'])) {
                    Database::update('users', ['role' => $newRole], 'id=?', [$targetId]);
                    $success = 'Role updated to ' . $newRole . '.';
                }
                $tab = 'users';
                break;

            case 'delete_company':
                if ($targetId) {
                    $co = Database::fetchOne('SELECT name FROM companies WHERE id=?', [$targetId]);
                    if ($co) {
                        Database::query('DELETE FROM companies WHERE id=?', [$targetId]);
                        $success = 'Company "' . htmlspecialchars($co['name']) . '" deleted.';
                    }
                }
                $tab = 'companies';
                break;

            case 'save_settings':
                $allowed = ['weight_environment','weight_social','weight_governance',
                            'score_excellent','score_good','score_moderate','score_poor',
                            'default_framework','reporting_year','platform_name'];
                foreach ($allowed as $k) {
                    if (!array_key_exists($k, $_POST)) continue;
                    try {
                        $ex = Database::fetchOne('SELECT `key` FROM platform_settings WHERE `key`=?', [$k]);
                        if ($ex) {
                            Database::query('UPDATE platform_settings SET value=? WHERE `key`=?', [trim($_POST[$k]), $k]);
                        } else {
                            Database::insert('platform_settings', ['key' => $k, 'value' => trim($_POST[$k])]);
                        }
                    } catch (\Throwable $e) {}
                }
                $success = 'Settings saved.';
                $tab = 'settings';
                break;
        }

        try {
            Database::insert('activity_log', [
                'user_id'     => $currentUser['id'],
                'action'      => 'ADMIN_' . strtoupper($action),
                'description' => 'action=' . $action . ($targetId ? ' target=' . $targetId : ''),
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {}

        header('Location: ' . APP_URL . '/admin?tab=' . urlencode($tab) . ($viewId ? '&id=' . $viewId : '') . '&msg=' . urlencode($success ?: $error));
        exit;
    }
}
if (isset($_GET['msg'])) $success = htmlspecialchars($_GET['msg']);

// ── Platform stats (always loaded) ────────────────────────────────────
$stats = [
    'users'        => (int)(Database::fetchOne('SELECT COUNT(*) n FROM users')['n'] ?? 0),
    'active_users' => (int)(Database::fetchOne('SELECT COUNT(*) n FROM users WHERE is_active=1')['n'] ?? 0),
    'companies'    => (int)(Database::fetchOne('SELECT COUNT(*) n FROM companies')['n'] ?? 0),
    'esg_data'     => (int)(Database::fetchOne('SELECT COUNT(*) n FROM esg_data')['n'] ?? 0),
    'reports'      => (int)(Database::fetchOne('SELECT COUNT(*) n FROM reports')['n'] ?? 0),
    'logins_today' => (int)(Database::fetchOne("SELECT COUNT(*) n FROM activity_log WHERE action='LOGIN' AND DATE(created_at)=CURDATE()")['n'] ?? 0),
];
$roleStats      = Database::fetchAll('SELECT role, COUNT(*) cnt FROM users GROUP BY role ORDER BY cnt DESC');
$fwStats        = Database::fetchAll('SELECT framework, COUNT(*) cnt FROM companies GROUP BY framework ORDER BY cnt DESC');
$recentActivity = Database::fetchAll(
    'SELECT al.*, u.name un, u.role ur FROM activity_log al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 15'
);

// ── Tab-specific data ─────────────────────────────────────────────────
$allUsers = $allCompanies = $allReports = $indicators = [];
$drillCompany = null; $drillData = []; $drillReports = []; $drillMembers = [];
$settings = []; $singleReport = null;

if ($tab === 'users') {
    $allUsers = Database::fetchAll(
        'SELECT u.*, (SELECT COUNT(*) FROM user_companies uc WHERE uc.user_id=u.id) co_count
         FROM users u ORDER BY u.created_at DESC'
    );
}

if ($tab === 'companies') {
    $allCompanies = Database::fetchAll(
        'SELECT c.*, u.name creator_name,
         (SELECT COUNT(*) FROM user_companies uc WHERE uc.company_id=c.id) member_count,
         (SELECT COUNT(DISTINCT indicator_id) FROM esg_data WHERE company_id=c.id AND value IS NOT NULL AND value!="") filled,
         (SELECT COUNT(*) FROM esg_data WHERE company_id=c.id) data_points
         FROM companies c LEFT JOIN users u ON c.created_by=u.id ORDER BY c.created_at DESC'
    );
}

if ($tab === 'company' && $viewId) {
    $drillCompany = Database::fetchOne(
        'SELECT c.*, u.name creator_name FROM companies c LEFT JOIN users u ON c.created_by=u.id WHERE c.id=?', [$viewId]
    );
    if ($drillCompany) {
        $drillData    = Database::fetchAll('SELECT * FROM esg_data WHERE company_id=? ORDER BY category, indicator_id', [$viewId]);
        $drillReports = Database::fetchAll(
            'SELECT r.*, u.name gbn FROM reports r LEFT JOIN users u ON r.generated_by=u.id WHERE r.company_id=? ORDER BY r.generated_at DESC',
            [$viewId]
        );
        $drillMembers = Database::fetchAll(
            'SELECT uc.role uc_role, u.name, u.email, u.role FROM user_companies uc JOIN users u ON uc.user_id=u.id WHERE uc.company_id=?',
            [$viewId]
        );
    }
}

if ($tab === 'indicators') {
    $fwFile = __DIR__ . '/../config/indicators/' . $fwKey . '.php';
    if (file_exists($fwFile)) {
        $covRows = Database::fetchAll(
            'SELECT indicator_id, COUNT(DISTINCT company_id) cnt FROM esg_data WHERE value IS NOT NULL AND value!="" GROUP BY indicator_id'
        );
        $covMap = [];
        foreach ($covRows as $r) $covMap[$r['indicator_id']] = (int)$r['cnt'];
        $raw = require $fwFile;
        foreach ($raw as $cat => $items) {
            foreach ($items as $item) {
                if ($catFilter && $catFilter !== $cat) continue;
                $item['category_label'] = $cat;
                $item['coverage']       = $covMap[$item['indicator_id']] ?? 0;
                $indicators[]           = $item;
            }
        }
    }
}

if ($tab === 'reports') {
    $allReports = Database::fetchAll(
        'SELECT r.id, r.title, r.framework, r.period, r.score, r.generated_at, c.name company_name, u.name gbn
         FROM reports r LEFT JOIN companies c ON r.company_id=c.id LEFT JOIN users u ON r.generated_by=u.id
         ORDER BY r.generated_at DESC'
    );
}

if ($tab === 'report' && $viewId) {
    $singleReport = Database::fetchOne(
        'SELECT r.*, c.name company_name, u.name gbn FROM reports r LEFT JOIN companies c ON r.company_id=c.id LEFT JOIN users u ON r.generated_by=u.id WHERE r.id=?',
        [$viewId]
    );
}

if ($tab === 'settings') {
    try {
        $rows = Database::fetchAll('SELECT * FROM platform_settings');
        foreach ($rows as $row) $settings[$row['key']] = $row['value'];
    } catch (\Throwable $e) { $settings = []; }
}

// ── Helpers ───────────────────────────────────────────────────────────
$RC = ['admin'=>['#7c3aed','Admin'],'principal'=>['#1d4ed8','Principal'],'associate'=>['#0891b2','Associate'],
       'manager'=>['#475569','Manager'],'consultant'=>['#b45309','Consultant'],'sme_owner'=>['#16a34a','SME Owner']];
function rb(string $role, array $rc): string {
    [$bg,$lbl] = $rc[$role] ?? ['#6b7280',$role];
    return '<span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:11px;font-weight:700;background:'.$bg.';color:#fff">'.htmlspecialchars($lbl).'</span>';
}
$CC = ['ENVIRONMENT'=>['#15803d','E','#dcfce7'],'SOCIAL'=>['#1d4ed8','S','#dbeafe'],'GOVERNANCE'=>['#7c3aed','G','#f3e8ff']];
$PC = ['critical'=>'#dc2626','high'=>'#ea580c','medium'=>'#ca8a04','low'=>'#64748b'];
$fwLabels = ['bursa_sedg'=>'Bursa SEDG','gri'=>'GRI','tcfd'=>'TCFD','cdp'=>'CDP','esrs'=>'ESRS','issb'=>'ISSB','sasb_manufacturing'=>'SASB Mfg','sasb_food'=>'SASB Food','sasb_tech'=>'SASB Tech'];
$scoreColor = function(float $s): string {
    if ($s >= 80) return '#16a34a'; if ($s >= 60) return '#0891b2'; if ($s >= 40) return '#ca8a04'; return '#dc2626';
};

include __DIR__ . '/../includes/header.php';
?>

<style>
.adm-stat { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px;
            display:flex; align-items:center; gap:14px; }
.adm-stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center;
                 justify-content:center; font-size:20px; flex-shrink:0; }
.adm-stat-val  { font-size:26px; font-weight:800; color:#0f172a; line-height:1; }
.adm-stat-lbl  { font-size:12px; color:#64748b; margin-top:2px; }
.adm-stat-sub  { font-size:11px; color:#94a3b8; margin-top:1px; }

.adm-tabs { display:flex; gap:4px; background:#f1f5f9; border-radius:12px; padding:4px;
            flex-wrap:wrap; margin-bottom:24px; }
.adm-tab  { padding:8px 16px; border-radius:9px; font-size:13px; font-weight:600;
            color:#64748b; text-decoration:none; border:none; background:none; cursor:pointer;
            white-space:nowrap; transition:all 0.15s; display:flex; align-items:center; gap:6px; }
.adm-tab:hover  { background:#e2e8f0; color:#334155; }
.adm-tab.active { background:#fff; color:#0f172a; box-shadow:0 1px 4px rgba(0,0,0,0.1); }
.adm-tab .badge { font-size:10px; background:#e2e8f0; color:#475569; border-radius:4px; padding:1px 6px; }
.adm-tab.active .badge { background:#f1f5f9; }

.adm-section { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:20px; }
.adm-section-head { padding:14px 20px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center;
                    justify-content:space-between; }
.adm-section-title { font-size:14px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px; }

.adm-table { width:100%; border-collapse:collapse; }
.adm-table th { padding:10px 16px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase;
                letter-spacing:0.4px; color:#94a3b8; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.adm-table td { padding:12px 16px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#334155;
                vertical-align:middle; }
.adm-table tr:last-child td { border-bottom:none; }
.adm-table tr:hover td { background:#f8fafc; }

.role-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:700; }
.cov-bar-wrap { display:flex; align-items:center; gap:8px; }
.cov-bar { flex:1; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden; }
.cov-bar-fill { height:100%; border-radius:3px; transition:width 0.3s; }

.fw-pill { display:inline-block; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600;
           border:2px solid transparent; cursor:pointer; text-decoration:none; transition:all 0.15s; }
.fw-pill.active { border-color:#16a34a; background:#f0fdf4; color:#15803d; }
.fw-pill:not(.active) { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
.fw-pill:hover:not(.active) { border-color:#cbd5e1; color:#334155; }

.act-feed { display:flex; flex-direction:column; gap:0; }
.act-item { display:flex; align-items:flex-start; gap:12px; padding:10px 20px; border-bottom:1px solid #f1f5f9; }
.act-item:last-child { border-bottom:none; }
.act-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:5px; }
.act-meta { flex:1; min-width:0; }
.act-action { font-size:12px; font-weight:700; color:#334155; }
.act-desc { font-size:11px; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.act-time { font-size:11px; color:#cbd5e1; flex-shrink:0; }

.drilldown-breadcrumb { display:flex; align-items:center; gap:8px; font-size:13px; color:#64748b;
                        margin-bottom:20px; }
.drilldown-breadcrumb a { color:#16a34a; text-decoration:none; font-weight:600; }
.drilldown-breadcrumb a:hover { text-decoration:underline; }
.drilldown-breadcrumb .sep { color:#cbd5e1; }

.info-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:16px; padding:20px; }
.info-cell { }
.info-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:#94a3b8; margin-bottom:4px; }
.info-val   { font-size:14px; font-weight:600; color:#0f172a; }

.score-pill { display:inline-block; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:800;
              color:#fff; }

.set-group { margin-bottom:28px; }
.set-group-title { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;
                   color:#94a3b8; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid #f1f5f9; }
.set-row { display:flex; align-items:center; gap:16px; padding:10px 0; border-bottom:1px solid #f8fafc; }
.set-row:last-child { border-bottom:none; }
.set-label { flex:1; font-size:13px; font-weight:600; color:#334155; }
.set-sublabel { font-size:11px; color:#94a3b8; margin-top:2px; }
.set-input { width:140px; padding:6px 10px; border:1px solid #e2e8f0; border-radius:8px;
             font-size:13px; color:#0f172a; background:#f8fafc; }
.set-input:focus { outline:none; border-color:#16a34a; background:#fff; }
</style>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-shield-lock-fill me-2" style="color:#7c3aed"></i>Super Admin</h1>
        <span class="topbar-subtitle">Platform control &bull; <?= date('d M Y') ?></span>
      </div>
      <div class="topbar-actions">
        <span style="background:#7c3aed;color:#fff;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700">
          <i class="bi bi-shield-check me-1"></i>Super Admin
        </span>
      </div>
    </div>

    <div class="content-body">

      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show mb-4">
        <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- ── Stats Row ─────────────────────────────────────── -->
      <div class="row g-3 mb-4">
        <?php
        $statItems = [
            ['icon'=>'bi-people-fill',        'color'=>'#16a34a', 'bg'=>'#f0fdf4', 'val'=>$stats['users'],       'lbl'=>'Total Users',     'sub'=>$stats['active_users'].' active'],
            ['icon'=>'bi-buildings-fill',      'color'=>'#0891b2', 'bg'=>'#f0f9ff', 'val'=>$stats['companies'],   'lbl'=>'Companies',        'sub'=>'On platform'],
            ['icon'=>'bi-database-fill',       'color'=>'#7c3aed', 'bg'=>'#faf5ff', 'val'=>$stats['esg_data'],   'lbl'=>'ESG Data Points',  'sub'=>'Across all companies'],
            ['icon'=>'bi-file-earmark-text',   'color'=>'#b45309', 'bg'=>'#fffbeb', 'val'=>$stats['reports'],    'lbl'=>'Reports Generated','sub'=>'All time'],
            ['icon'=>'bi-box-arrow-in-right',  'color'=>'#0d9488', 'bg'=>'#f0fdfa', 'val'=>$stats['logins_today'],'lbl'=>'Logins Today',   'sub'=>'Active sessions'],
        ];
        foreach ($statItems as $si): ?>
        <div class="col-6 col-md-4 col-xl">
          <div class="adm-stat">
            <div class="adm-stat-icon" style="background:<?= $si['bg'] ?>;color:<?= $si['color'] ?>">
              <i class="bi <?= $si['icon'] ?>"></i>
            </div>
            <div>
              <div class="adm-stat-val"><?= number_format($si['val']) ?></div>
              <div class="adm-stat-lbl"><?= $si['lbl'] ?></div>
              <div class="adm-stat-sub"><?= $si['sub'] ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- ── Tab Navigation ────────────────────────────────── -->
      <div class="adm-tabs">
        <?php
        $tabs = [
          ['overview',   'bi-activity',          'Overview',   ''],
          ['companies',  'bi-buildings',          'Companies',  $stats['companies']],
          ['users',      'bi-people',             'Users',      $stats['users']],
          ['indicators', 'bi-list-check',         'Indicators', ''],
          ['reports',    'bi-file-earmark-text',  'Reports',    $stats['reports']],
          ['settings',   'bi-gear',               'Settings',   ''],
          ['logs',       'bi-journal-text',       'Audit Log',  ''],
        ];
        foreach ($tabs as [$slug, $icon, $label, $count]):
          $isActive = ($tab === $slug || ($tab === 'company' && $slug === 'companies'));
        ?>
        <a href="<?= APP_URL ?>/admin?tab=<?= $slug ?>" class="adm-tab <?= $isActive ? 'active' : '' ?>">
          <i class="bi <?= $icon ?>"></i><?= $label ?>
          <?php if ($count !== ''): ?><span class="badge"><?= $count ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- ═══════════════════════════════════════════════════════
           TAB: OVERVIEW
      ════════════════════════════════════════════════════════ -->
      <?php if ($tab === 'overview'): ?>
      <div class="row g-4">

        <!-- Left: Role breakdown + Framework breakdown -->
        <div class="col-lg-4">
          <div class="adm-section mb-4">
            <div class="adm-section-head">
              <span class="adm-section-title"><i class="bi bi-person-badge" style="color:#7c3aed"></i>Users by Role</span>
            </div>
            <div style="padding:16px 20px">
              <?php foreach ($roleStats as $rs):
                [$bg,$lbl] = $RC[$rs['role']] ?? ['#6b7280',$rs['role']];
                $pct = $stats['users'] ? round($rs['cnt']/$stats['users']*100) : 0;
              ?>
              <div style="margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                  <span style="display:inline-block;padding:2px 10px;border-radius:6px;font-size:11px;font-weight:700;background:<?=$bg?>;color:#fff"><?=$lbl?></span>
                  <span style="font-size:13px;font-weight:700;color:#0f172a"><?=$rs['cnt']?> <span style="font-weight:400;color:#94a3b8;font-size:11px">(<?=$pct?>%)</span></span>
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden">
                  <div style="height:100%;width:<?=$pct?>%;background:<?=$bg?>;border-radius:3px"></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="adm-section">
            <div class="adm-section-head">
              <span class="adm-section-title"><i class="bi bi-diagram-3" style="color:#0891b2"></i>Companies by Framework</span>
            </div>
            <div style="padding:16px 20px">
              <?php if (!$fwStats): ?>
              <p style="color:#94a3b8;font-size:13px;margin:0">No companies yet.</p>
              <?php else: foreach ($fwStats as $fs):
                $pct = $stats['companies'] ? round($fs['cnt']/$stats['companies']*100) : 0;
              ?>
              <div style="margin-bottom:10px">
                <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px">
                  <span><?= htmlspecialchars($fs['framework']) ?></span>
                  <span><?= $fs['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden">
                  <div style="height:100%;width:<?=$pct?>%;background:#0891b2;border-radius:3px"></div>
                </div>
              </div>
              <?php endforeach; endif; ?>
            </div>
          </div>
        </div>

        <!-- Right: Activity feed -->
        <div class="col-lg-8">
          <div class="adm-section">
            <div class="adm-section-head">
              <span class="adm-section-title"><i class="bi bi-activity" style="color:#16a34a"></i>Recent Activity</span>
              <a href="?tab=logs" style="font-size:12px;color:#16a34a;text-decoration:none">View all →</a>
            </div>
            <div class="act-feed">
              <?php
              $actColors = ['LOGIN'=>'#16a34a','LOGOUT'=>'#94a3b8','COMPANY_CREATED'=>'#0891b2',
                            'ESG_DATA_SAVED'=>'#7c3aed','REPORT_GENERATED'=>'#b45309',
                            'ADMIN_TOGGLE_USER'=>'#dc2626','ADMIN_SET_ROLE'=>'#ea580c'];
              foreach ($recentActivity as $log):
                $dot = $actColors[$log['action']] ?? '#cbd5e1';
                [$bg,$lbl] = $RC[$log['ur'] ?? ''] ?? ['#e2e8f0','—'];
              ?>
              <div class="act-item">
                <div class="act-dot" style="background:<?= $dot ?>"></div>
                <div class="act-meta">
                  <div class="act-action">
                    <?php if ($log['un']): ?>
                    <span style="font-weight:800"><?= htmlspecialchars($log['un']) ?></span>
                    <?php if ($log['ur']): ?>
                    <span style="display:inline-block;padding:1px 7px;border-radius:4px;font-size:10px;font-weight:700;background:<?=$bg?>;color:#fff;margin-left:4px"><?= $lbl ?></span>
                    <?php endif; ?>
                    &mdash;
                    <?php endif; ?>
                    <?= htmlspecialchars($log['action']) ?>
                  </div>
                  <div class="act-desc"><?= htmlspecialchars(substr($log['description'] ?? '', 0, 80)) ?></div>
                </div>
                <div class="act-time"><?= date('d M H:i', strtotime($log['created_at'])) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════
           TAB: COMPANIES (list)
      ════════════════════════════════════════════════════════ -->
      <?php elseif ($tab === 'companies'): ?>
      <div class="adm-section">
        <div class="adm-section-head">
          <span class="adm-section-title"><i class="bi bi-buildings-fill" style="color:#0891b2"></i>All Companies (<?= count($allCompanies) ?>)</span>
        </div>
        <div class="table-responsive">
          <table class="adm-table">
            <thead>
              <tr>
                <th>#</th><th>Company</th><th>Industry</th><th>Revenue</th>
                <th>Framework</th><th>Members</th><th>ESG Coverage</th><th>Created</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$allCompanies): ?>
            <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:32px">No companies yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($allCompanies as $co):
              $filled = (int)$co['filled'];
              $covPct = $filled > 0 ? min(100, round($filled / 41 * 100)) : 0; // 41 = bursa indicators
              $covColor = $covPct >= 70 ? '#16a34a' : ($covPct >= 40 ? '#ca8a04' : '#dc2626');
            ?>
            <tr>
              <td style="color:#94a3b8"><?= $co['id'] ?></td>
              <td>
                <div style="font-weight:700;color:#0f172a"><?= htmlspecialchars($co['name']) ?></div>
                <div style="font-size:11px;color:#94a3b8"><?= htmlspecialchars($co['registration_no'] ?? '') ?></div>
                <div style="font-size:11px;color:#64748b">by <?= htmlspecialchars($co['creator_name'] ?? '—') ?></div>
              </td>
              <td><?= htmlspecialchars($co['industry']) ?></td>
              <td style="font-size:12px"><?= Benchmarker::revenueTierLabel($co['revenue_tier']) ?></td>
              <td><span style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700"><?= htmlspecialchars($co['framework']) ?></span></td>
              <td style="text-align:center"><?= $co['member_count'] ?></td>
              <td style="min-width:140px">
                <div class="cov-bar-wrap">
                  <div class="cov-bar">
                    <div class="cov-bar-fill" style="width:<?= $covPct ?>%;background:<?= $covColor ?>"></div>
                  </div>
                  <span style="font-size:11px;font-weight:700;color:<?= $covColor ?>;white-space:nowrap"><?= $covPct ?>%</span>
                </div>
                <div style="font-size:10px;color:#94a3b8;margin-top:2px"><?= $filled ?> indicators filled</div>
              </td>
              <td style="font-size:12px;color:#94a3b8"><?= date('d M Y', strtotime($co['created_at'])) ?></td>
              <td style="white-space:nowrap">
                <a href="?tab=company&id=<?= $co['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-size:12px;margin-right:4px">
                  <i class="bi bi-eye me-1"></i>View Data
                </a>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($co['name'])) ?> and all data?')">
                  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                  <input type="hidden" name="action" value="delete_company">
                  <input type="hidden" name="target_id" value="<?= $co['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:12px">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════
           TAB: COMPANY DRILL-DOWN
      ════════════════════════════════════════════════════════ -->
      <?php elseif ($tab === 'company'): ?>
      <div class="drilldown-breadcrumb">
        <a href="?tab=companies"><i class="bi bi-buildings me-1"></i>Companies</a>
        <span class="sep">/</span>
        <span style="color:#0f172a;font-weight:700"><?= htmlspecialchars($drillCompany['name'] ?? 'Unknown') ?></span>
      </div>

      <?php if (!$drillCompany): ?>
      <div class="alert alert-danger">Company not found.</div>
      <?php else: ?>

      <!-- Company info card -->
      <div class="adm-section mb-4">
        <div class="adm-section-head">
          <span class="adm-section-title">
            <i class="bi bi-building" style="color:#0891b2"></i>
            <?= htmlspecialchars($drillCompany['name']) ?>
          </span>
          <span style="font-size:12px;color:#94a3b8">ID #<?= $drillCompany['id'] ?></span>
        </div>
        <div class="info-grid">
          <div class="info-cell"><div class="info-label">Registration No.</div><div class="info-val"><?= htmlspecialchars($drillCompany['registration_no'] ?? '—') ?></div></div>
          <div class="info-cell"><div class="info-label">Industry</div><div class="info-val"><?= htmlspecialchars($drillCompany['industry']) ?></div></div>
          <div class="info-cell"><div class="info-label">Revenue Tier</div><div class="info-val"><?= Benchmarker::revenueTierLabel($drillCompany['revenue_tier']) ?></div></div>
          <div class="info-cell"><div class="info-label">Employees</div><div class="info-val"><?= number_format($drillCompany['employee_count']) ?></div></div>
          <div class="info-cell"><div class="info-label">Framework</div><div class="info-val"><?= htmlspecialchars($drillCompany['framework']) ?></div></div>
          <div class="info-cell"><div class="info-label">Reporting Year</div><div class="info-val"><?= $drillCompany['reporting_year'] ?></div></div>
          <div class="info-cell"><div class="info-label">Created By</div><div class="info-val"><?= htmlspecialchars($drillCompany['creator_name'] ?? '—') ?></div></div>
          <div class="info-cell"><div class="info-label">Created At</div><div class="info-val"><?= date('d M Y', strtotime($drillCompany['created_at'])) ?></div></div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Members -->
        <div class="col-md-4">
          <div class="adm-section">
            <div class="adm-section-head">
              <span class="adm-section-title"><i class="bi bi-people" style="color:#7c3aed"></i>Members (<?= count($drillMembers) ?>)</span>
            </div>
            <?php if (!$drillMembers): ?>
            <div style="padding:20px;color:#94a3b8;font-size:13px">No members assigned.</div>
            <?php else: ?>
            <table class="adm-table">
              <thead><tr><th>Name</th><th>Role</th><th>Access</th></tr></thead>
              <tbody>
              <?php foreach ($drillMembers as $m): ?>
              <tr>
                <td>
                  <div style="font-weight:600"><?= htmlspecialchars($m['name']) ?></div>
                  <div style="font-size:11px;color:#94a3b8"><?= htmlspecialchars($m['email']) ?></div>
                </td>
                <td><?= rb($m['role'], $RC) ?></td>
                <td style="font-size:12px;color:#64748b"><?= htmlspecialchars($m['uc_role']) ?></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>

        <!-- Reports for this company -->
        <div class="col-md-8">
          <div class="adm-section">
            <div class="adm-section-head">
              <span class="adm-section-title"><i class="bi bi-file-earmark-text" style="color:#b45309"></i>Reports (<?= count($drillReports) ?>)</span>
            </div>
            <?php if (!$drillReports): ?>
            <div style="padding:20px;color:#94a3b8;font-size:13px">No reports generated yet.</div>
            <?php else: ?>
            <table class="adm-table">
              <thead><tr><th>Title</th><th>Period</th><th>Score</th><th>Generated</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($drillReports as $r):
                $sc = (float)$r['score'];
                $sc_col = $scoreColor($sc);
              ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['period']) ?></td>
                <td><span class="score-pill" style="background:<?= $sc_col ?>"><?= number_format($sc, 1) ?></span></td>
                <td style="font-size:12px;color:#94a3b8"><?= date('d M Y', strtotime($r['generated_at'])) ?></td>
                <td><a href="?tab=report&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary" style="font-size:11px">View</a></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ESG Data table -->
      <div class="adm-section mt-4">
        <div class="adm-section-head">
          <span class="adm-section-title"><i class="bi bi-database" style="color:#7c3aed"></i>ESG Data (<?= count($drillData) ?> entries)</span>
          <span style="font-size:12px;color:#94a3b8"><?= count(array_filter($drillData, fn($d) => !empty($d['value']))) ?> with values</span>
        </div>
        <?php if (!$drillData): ?>
        <div style="padding:32px;text-align:center;color:#94a3b8;font-size:13px">No ESG data entered yet for this company.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="adm-table">
            <thead>
              <tr><th>Indicator</th><th>Category</th><th>Period</th><th>Value</th><th>Source</th><th>Verified</th><th>Entered</th></tr>
            </thead>
            <tbody>
            <?php foreach ($drillData as $d):
              [$catBg,$catLtr,$catLightBg] = $CC[$d['category']] ?? ['#6b7280','?','#f8fafc'];
            ?>
            <tr>
              <td style="font-family:monospace;font-size:12px;font-weight:700"><?= htmlspecialchars($d['indicator_id']) ?></td>
              <td>
                <span style="display:inline-block;width:20px;height:20px;border-radius:5px;background:<?=$catBg?>;color:#fff;font-size:10px;font-weight:800;text-align:center;line-height:20px"><?=$catLtr?></span>
              </td>
              <td style="font-size:12px"><?= htmlspecialchars($d['period']) ?></td>
              <td style="font-weight:600;color:#0f172a;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= htmlspecialchars($d['value'] ?? '—') ?>
                <?php if ($d['unit']): ?><span style="font-size:11px;color:#94a3b8"> <?= htmlspecialchars($d['unit']) ?></span><?php endif; ?>
              </td>
              <td style="font-size:12px;color:#64748b;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($d['data_source'] ?? '—') ?></td>
              <td>
                <?php if ($d['verified']): ?>
                <span style="color:#16a34a;font-size:13px"><i class="bi bi-check-circle-fill"></i></span>
                <?php else: ?>
                <span style="color:#e2e8f0;font-size:13px"><i class="bi bi-circle"></i></span>
                <?php endif; ?>
              </td>
              <td style="font-size:11px;color:#94a3b8"><?= $d['updated_at'] ? date('d M Y', strtotime($d['updated_at'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <?php endif; // drillCompany ?>

      <!-- ═══════════════════════════════════════════════════════
           TAB: USERS
      ════════════════════════════════════════════════════════ -->
      <?php elseif ($tab === 'users'): ?>
      <div class="adm-section">
        <div class="adm-section-head">
          <span class="adm-section-title"><i class="bi bi-people-fill" style="color:#7c3aed"></i>All Users (<?= count($allUsers) ?>)</span>
          <a href="<?= APP_URL ?>/register" class="btn btn-sm btn-success" style="font-size:13px">
            <i class="bi bi-person-plus me-1"></i>Add User
          </a>
        </div>
        <div class="table-responsive">
          <table class="adm-table">
            <thead>
              <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Companies</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($allUsers as $u):
              $isSelf = $u['id'] === $currentUser['id'];
            ?>
            <tr style="<?= $isSelf ? 'background:#f0fdf4' : '' ?>">
              <td style="color:#94a3b8;font-size:12px"><?= $u['id'] ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:32px;height:32px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#334155">
                    <?= strtoupper(substr($u['name'],0,1)) ?>
                  </div>
                  <div>
                    <div style="font-weight:700"><?= htmlspecialchars($u['name']) ?></div>
                    <?php if ($isSelf): ?><div style="font-size:10px;color:#16a34a;font-weight:700">YOU</div><?php endif; ?>
                  </div>
                </div>
              </td>
              <td style="font-size:13px"><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <?php if ($isSelf): ?>
                  <?= rb($u['role'], $RC) ?>
                <?php else: ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                  <input type="hidden" name="action" value="set_role">
                  <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                  <select name="role" onchange="this.form.submit()"
                          style="border:1px solid #e2e8f0;border-radius:6px;padding:4px 8px;font-size:12px;font-weight:600;color:#334155;background:#f8fafc;cursor:pointer">
                    <?php foreach (array_keys($RC) as $r): ?>
                    <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= $RC[$r][1] ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <?php if ($u['co_count'] > 0): ?>
                <span style="font-weight:700;color:#0891b2"><?= $u['co_count'] ?></span>
                <?php else: ?>
                <span style="color:#e2e8f0">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($u['is_active']): ?>
                <span style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700">Active</span>
                <?php else: ?>
                <span style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700">Inactive</span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:#94a3b8"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <?php if (!$isSelf): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                  <input type="hidden" name="action" value="toggle_user">
                  <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                  <button type="submit"
                    class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                    style="font-size:12px">
                    <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                  </button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════
           TAB: INDICATORS
      ════════════════════════════════════════════════════════ -->
      <?php elseif ($tab === 'indicators'): ?>

      <!-- Framework selector -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
        <?php foreach ($fwLabels as $slug => $label):
          $fwFile = __DIR__ . '/../config/indicators/' . $slug . '.php';
          if (!file_exists($fwFile)) continue;
        ?>
        <a href="?tab=indicators&fw=<?= $slug ?>&cat=<?= $catFilter ?>"
           class="fw-pill <?= $fwKey===$slug?'active':'' ?>">
           <?= htmlspecialchars($label) ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Category filter -->
      <div style="display:flex;gap:8px;margin-bottom:20px;align-items:center">
        <span style="font-size:12px;font-weight:700;color:#94a3b8">Filter:</span>
        <?php foreach ([''=>'All','ENVIRONMENT'=>'Environment','SOCIAL'=>'Social','GOVERNANCE'=>'Governance'] as $k=>$v):
          [$cbg,$cltr,$clbg] = $CC[$k] ?? ['#334155','','#f8fafc'];
        ?>
        <a href="?tab=indicators&fw=<?= $fwKey ?>&cat=<?= $k ?>"
           style="padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;border:2px solid <?= $catFilter===$k?$cbg:'#e2e8f0' ?>;background:<?= $catFilter===$k?$clbg:'#f8fafc' ?>;color:<?= $catFilter===$k?$cbg:'#64748b' ?>">
           <?= $v ?>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="adm-section">
        <div class="adm-section-head">
          <span class="adm-section-title">
            <i class="bi bi-list-check" style="color:#16a34a"></i>
            <?= $fwLabels[$fwKey] ?? strtoupper($fwKey) ?> Indicators (<?= count($indicators) ?>)
          </span>
          <span style="font-size:12px;color:#94a3b8">Coverage = companies with data / total companies (<?= $stats['companies'] ?>)</span>
        </div>
        <?php if (!$indicators): ?>
        <div style="padding:32px;text-align:center;color:#94a3b8">No indicators found for this framework.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="adm-table">
            <thead>
              <tr><th>Code</th><th>Indicator Name</th><th>Category</th><th>Priority</th><th>Type</th><th style="width:170px">Coverage</th></tr>
            </thead>
            <tbody>
            <?php foreach ($indicators as $ind):
              [$catBg,$catLtr,$catLBg] = $CC[$ind['category_label']] ?? ['#6b7280','?','#f8fafc'];
              $priCol = $PC[$ind['priority']] ?? '#64748b';
              $cov = $ind['coverage'];
              $covPct = $stats['companies'] > 0 ? min(100, round($cov/$stats['companies']*100)) : 0;
              $covColor = $covPct >= 70 ? '#16a34a' : ($covPct >= 30 ? '#ca8a04' : '#dc2626');
            ?>
            <tr>
              <td style="font-family:monospace;font-size:12px;font-weight:700;color:#7c3aed"><?= htmlspecialchars($ind['code'] ?? $ind['indicator_id']) ?></td>
              <td>
                <div style="font-weight:600;color:#0f172a"><?= htmlspecialchars($ind['name']) ?></div>
                <div style="font-size:11px;color:#94a3b8;margin-top:2px"><?= htmlspecialchars(mb_substr($ind['description'] ?? '', 0, 80)) ?>...</div>
                <?php if (!empty($ind['required'])): ?>
                <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:4px;font-weight:700">Required</span>
                <?php endif; ?>
              </td>
              <td>
                <span style="display:inline-block;padding:2px 9px;border-radius:6px;background:<?=$catLBg?>;color:<?=$catBg?>;font-size:11px;font-weight:700">
                  <?= $ind['category_label'] ?>
                </span>
              </td>
              <td>
                <span style="display:inline-block;padding:2px 9px;border-radius:6px;background:<?=$priCol?>22;color:<?=$priCol?>;font-size:11px;font-weight:700;text-transform:capitalize">
                  <?= htmlspecialchars($ind['priority'] ?? 'medium') ?>
                </span>
              </td>
              <td style="font-size:12px;color:#64748b;text-transform:capitalize"><?= htmlspecialchars($ind['data_type'] ?? 'text') ?></td>
              <td>
                <div class="cov-bar-wrap">
                  <div class="cov-bar">
                    <div class="cov-bar-fill" style="width:<?= $covPct ?>%;background:<?= $covColor ?>"></div>
                  </div>
                  <span style="font-size:11px;font-weight:700;color:<?= $covColor ?>;white-space:nowrap"><?= $cov ?>/<?= $stats['companies'] ?></span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- ═══════════════════════════════════════════════════════
           TAB: REPORTS
      ════════════════════════════════════════════════════════ -->
      <?php elseif ($tab === 'reports'): ?>
      <div class="adm-section">
        <div class="adm-section-head">
          <span class="adm-section-title"><i class="bi bi-file-earmark-text-fill" style="color:#b45309"></i>All Reports (<?= count($allReports) ?>)</span>
        </div>
        <?php if (!$allReports): ?>
        <div style="padding:48px;text-align:center;color:#94a3b8">
          <i class="bi bi-file-earmark" style="font-size:40px;display:block;margin-bottom:12px"></i>
          No reports generated yet.
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="adm-table">
            <thead>
              <tr><th>Company</th><th>Report Title</th><th>Framework</th><th>Period</th><th>Score</th><th>Generated By</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($allReports as $r):
              $sc = (float)$r['score'];
              $sc_col = $scoreColor($sc);
            ?>
            <tr>
              <td style="font-weight:700"><?= htmlspecialchars($r['company_name'] ?? '—') ?></td>
              <td style="max-width:200px">
                <div style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($r['title']) ?></div>
              </td>
              <td><span style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700"><?= htmlspecialchars($r['framework']) ?></span></td>
              <td style="font-size:13px"><?= htmlspecialchars($r['period']) ?></td>
              <td><span class="score-pill" style="background:<?= $sc_col ?>"><?= number_format($sc, 1) ?></span></td>
              <td style="font-size:12px;color:#64748b"><?= htmlspecialchars($r['gbn'] ?? '—') ?></td>
              <td style="font-size:12px;color:#94a3b8"><?= date('d M Y', strtotime($r['generated_at'])) ?></td>
              <td>
                <a href="?tab=report&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary" style="font-size:11px">
                  <i class="bi bi-eye me-1"></i>View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- ═══════════════════════════════════════════════════════
           TAB: SINGLE REPORT VIEW
      ════════════════════════════════════════════════════════ -->
      <?php elseif ($tab === 'report'): ?>
      <div class="drilldown-breadcrumb">
        <a href="?tab=reports"><i class="bi bi-file-earmark-text me-1"></i>Reports</a>
        <span class="sep">/</span>
        <span style="color:#0f172a;font-weight:700"><?= htmlspecialchars($singleReport['title'] ?? 'Report') ?></span>
      </div>

      <?php if (!$singleReport): ?>
      <div class="alert alert-danger">Report not found.</div>
      <?php else: ?>
      <div class="adm-section mb-4">
        <div class="adm-section-head">
          <div>
            <div class="adm-section-title"><?= htmlspecialchars($singleReport['title']) ?></div>
            <div style="font-size:12px;color:#94a3b8;margin-top:4px">
              <?= htmlspecialchars($singleReport['company_name'] ?? '—') ?> &bull;
              <?= htmlspecialchars($singleReport['framework']) ?> &bull;
              Period: <?= htmlspecialchars($singleReport['period']) ?> &bull;
              Generated: <?= date('d M Y H:i', strtotime($singleReport['generated_at'])) ?>
              by <?= htmlspecialchars($singleReport['gbn'] ?? 'System') ?>
            </div>
          </div>
          <span class="score-pill" style="background:<?= $scoreColor((float)$singleReport['score']) ?>;font-size:16px;padding:6px 18px">
            <?= number_format((float)$singleReport['score'], 1) ?>
          </span>
        </div>
        <div style="padding:24px;border-top:1px solid #f1f5f9">
          <?= $singleReport['content_html'] ?: '<p style="color:#94a3b8">No content available.</p>' ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ═══════════════════════════════════════════════════════
           TAB: SETTINGS
      ════════════════════════════════════════════════════════ -->
      <?php elseif ($tab === 'settings'): ?>

      <?php if (empty($settings)): ?>
      <div class="alert alert-warning mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Platform settings table not found.</strong>
        Run <code>db/migrate_v5.sql</code> via phpMyAdmin to enable editable settings.
        Showing PHP config defaults below.
      </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="save_settings">

        <div class="row g-4">
          <!-- Left: Scoring -->
          <div class="col-lg-6">
            <div class="adm-section">
              <div class="adm-section-head">
                <span class="adm-section-title"><i class="bi bi-sliders" style="color:#16a34a"></i>ESG Scoring Weights</span>
                <span style="font-size:11px;color:#94a3b8">Must total 100%</span>
              </div>
              <div style="padding:20px">
                <div class="set-group">
                  <?php
                  $weightFields = [
                    ['weight_environment','Environment (E) weight','Controls how much E score impacts overall','%'],
                    ['weight_social',     'Social (S) weight',     'Controls how much S score impacts overall','%'],
                    ['weight_governance', 'Governance (G) weight', 'Controls how much G score impacts overall','%'],
                  ];
                  $defaults = ['weight_environment'=>WEIGHT_ENVIRONMENT,'weight_social'=>WEIGHT_SOCIAL,'weight_governance'=>WEIGHT_GOVERNANCE];
                  foreach ($weightFields as [$k,$label,$sublabel,$unit]):
                    $val = $settings[$k] ?? $defaults[$k] ?? '';
                  ?>
                  <div class="set-row">
                    <div class="set-label">
                      <?= $label ?>
                      <div class="set-sublabel"><?= $sublabel ?></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px">
                      <input type="number" name="<?= $k ?>" value="<?= htmlspecialchars($val) ?>"
                             min="0" max="100" step="1" class="set-input" style="width:80px">
                      <span style="font-size:13px;color:#94a3b8"><?= $unit ?></span>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>

                <div class="set-group">
                  <div class="set-group-title">Score Thresholds</div>
                  <?php
                  $threshFields = [
                    ['score_excellent','Excellent (≥ X)','',SCORE_EXCELLENT],
                    ['score_good',     'Good (≥ X)',     '',SCORE_GOOD],
                    ['score_moderate', 'Moderate (≥ X)', '',SCORE_MODERATE],
                    ['score_poor',     'Poor (< Moderate)','',SCORE_POOR],
                  ];
                  foreach ($threshFields as [$k,$label,$sub,$def]):
                    $val = $settings[$k] ?? $def;
                  ?>
                  <div class="set-row">
                    <div class="set-label"><?= $label ?></div>
                    <input type="number" name="<?= $k ?>" value="<?= htmlspecialchars($val) ?>"
                           min="0" max="100" step="1" class="set-input" style="width:80px">
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: General -->
          <div class="col-lg-6">
            <div class="adm-section">
              <div class="adm-section-head">
                <span class="adm-section-title"><i class="bi bi-gear" style="color:#7c3aed"></i>General Settings</span>
              </div>
              <div style="padding:20px">
                <div class="set-group">
                  <?php
                  $genFields = [
                    ['platform_name',     'text', 'Platform Name',         'Displayed across the app', APP_NAME],
                    ['reporting_year',    'number','Default Reporting Year','Year pre-filled on forms', REPORTING_YEAR],
                  ];
                  foreach ($genFields as [$k,$type,$label,$sub,$def]):
                    $val = $settings[$k] ?? $def;
                  ?>
                  <div class="set-row">
                    <div class="set-label">
                      <?= $label ?>
                      <div class="set-sublabel"><?= $sub ?></div>
                    </div>
                    <input type="<?= $type ?>" name="<?= $k ?>" value="<?= htmlspecialchars($val) ?>" class="set-input">
                  </div>
                  <?php endforeach; ?>

                  <div class="set-row">
                    <div class="set-label">
                      Default Framework
                      <div class="set-sublabel">Pre-selected on company setup</div>
                    </div>
                    <select name="default_framework" class="set-input" style="width:auto">
                      <?php foreach (['BURSA_SEDG','GRI','TCFD','CDP','ESRS','SASB'] as $fw): ?>
                      <option value="<?= $fw ?>" <?= ($settings['default_framework']??'BURSA_SEDG')===$fw?'selected':'' ?>><?= $fw ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <!-- Read-only info -->
                <div class="set-group">
                  <div class="set-group-title">Platform Info (read-only)</div>
                  <?php
                  $info = [
                    ['App Version',    APP_VERSION],
                    ['PHP Version',    phpversion()],
                    ['Timezone',       APP_TIMEZONE],
                    ['Currency',       APP_CURRENCY],
                  ];
                  foreach ($info as [$k,$v]):
                  ?>
                  <div class="set-row">
                    <div class="set-label"><?= $k ?></div>
                    <span style="font-size:13px;color:#64748b;font-family:monospace"><?= htmlspecialchars($v) ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:12px">
          <button type="submit" class="btn btn-success" style="font-weight:700">
            <i class="bi bi-check-lg me-2"></i>Save Settings
          </button>
          <a href="?tab=settings" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>

      <!-- ═══════════════════════════════════════════════════════
           TAB: AUDIT LOG
      ════════════════════════════════════════════════════════ -->
      <?php elseif ($tab === 'logs'): ?>
      <?php
      $logs = Database::fetchAll(
          'SELECT al.*, u.name un, u.role ur FROM activity_log al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 200'
      );
      $actionColors = ['LOGIN'=>'#16a34a','LOGOUT'=>'#94a3b8','COMPANY_CREATED'=>'#0891b2',
                       'ESG_DATA_SAVED'=>'#7c3aed','REPORT_GENERATED'=>'#b45309',
                       'ADMIN_TOGGLE_USER'=>'#dc2626','ADMIN_SET_ROLE'=>'#ea580c',
                       'ADMIN_DELETE_COMPANY'=>'#dc2626','ADMIN_SAVE_SETTINGS'=>'#0891b2'];
      ?>
      <div class="adm-section">
        <div class="adm-section-head">
          <span class="adm-section-title"><i class="bi bi-journal-text" style="color:#334155"></i>Audit Log (last 200 events)</span>
          <span style="font-size:12px;color:#94a3b8"><?= count($logs) ?> records</span>
        </div>
        <div class="table-responsive">
          <table class="adm-table">
            <thead>
              <tr><th>Time</th><th>User</th><th>Action</th><th>Description</th><th>IP Address</th></tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log):
              $dot = $actionColors[$log['action']] ?? '#cbd5e1';
            ?>
            <tr>
              <td style="font-size:12px;color:#94a3b8;white-space:nowrap"><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
              <td>
                <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($log['un'] ?? '—') ?></div>
                <?php if ($log['ur']): ?><?= rb($log['ur'], $RC) ?><?php endif; ?>
              </td>
              <td>
                <span style="display:inline-block;padding:2px 10px;border-radius:6px;background:<?=$dot?>22;color:<?=$dot?>;font-size:11px;font-weight:700">
                  <?= htmlspecialchars($log['action']) ?>
                </span>
              </td>
              <td style="font-size:12px;color:#64748b;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= htmlspecialchars($log['description'] ?? '') ?>
              </td>
              <td style="font-size:12px;color:#94a3b8;font-family:monospace"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php endif; // tab ?>

    </div><!-- /content-body -->
  </div><!-- /main-content -->
</div><!-- /app-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
