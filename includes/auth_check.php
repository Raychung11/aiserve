<?php
/**
 * Auth check — include at top of every protected page
 * Bootstraps config, DB, classes, and verifies session
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Company.php';
require_once __DIR__ . '/../src/ESGDataManager.php';
require_once __DIR__ . '/../src/GapAnalyzer.php';
require_once __DIR__ . '/../src/ReportGenerator.php';
require_once __DIR__ . '/../src/Benchmarker.php';
require_once __DIR__ . '/../src/Subscription.php';
require_once __DIR__ . '/../src/Hierarchy.php';
require_once __DIR__ . '/../src/NotificationManager.php';
require_once __DIR__ . '/../src/ActionPlanManager.php';
require_once __DIR__ . '/../src/DepartmentManager.php';
require_once __DIR__ . '/../src/KPITracker.php';

Auth::startSession();
Auth::requireAuth();

$currentUser      = Auth::user();
$activeCompanyId  = Auth::activeCompanyId();
$activeCompany    = null;
$activePlan       = Subscription::getAllPlans()['starter']; // default
$unlockedIds      = $activePlan['indicator_ids'];           // default: 5 free IDs

if ($activeCompanyId) {
    $activeCompany = Company::getById($activeCompanyId, $currentUser['id'], $currentUser['role']);
    $activePlan    = Subscription::getActivePlan($activeCompanyId);
    $unlockedIds   = Subscription::getUnlockedIndicatorIds($activeCompanyId);
}

// Helper: get URL for a page
function url(string $page = ''): string {
    return APP_URL . ($page ? '/' . ltrim($page, '/') : '');
}

// Helper: determine active nav item
function isActive(string $page): string {
    $current = $_GET['page'] ?? 'dashboard';
    return $current === $page ? 'active' : '';
}

// Unread notification count for topbar bell
$_notifCount = NotificationManager::getUnreadCount($currentUser['id']);

// Helper: format number for display
function fmtNum($val, int $dec = 2): string {
    if ($val === null || $val === '') return '—';
    return number_format((float)$val, $dec);
}
