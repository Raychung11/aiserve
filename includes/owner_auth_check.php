<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/billplz.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ActivityLog.php';
require_once __DIR__ . '/../src/ComplianceEngine.php';
require_once __DIR__ . '/../src/ROIEngine.php';
require_once __DIR__ . '/../src/StrategyEngine.php';
require_once __DIR__ . '/../src/BillplzService.php';

Auth::start();
Auth::require();
$_user     = Auth::user();
$_tenantId = Auth::tenantId();

// Only owners may access these pages
if (($_user['role'] ?? '') !== 'owner') {
    header('Location: ' . APP_URL . '/dashboard'); exit;
}

$_ownerId = (int)($_user['owner_id'] ?? 0);
if (!$_ownerId) {
    Auth::logout();
    header('Location: ' . APP_URL . '/login?error=no_owner'); exit;
}
