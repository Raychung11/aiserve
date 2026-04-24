<?php
require_once __DIR__.'/../config/app.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../src/Database.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/ActivityLog.php';

Auth::start();

if (Auth::check()) {
    $u = Auth::user();
    ActivityLog::record('auth.logout', 'User signed out', (int)$u['tenant_id'], (int)$u['id']);
    Auth::logout();
}

header('Location: '.APP_URL.'/login');
exit;
