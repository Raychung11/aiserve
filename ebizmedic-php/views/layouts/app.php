<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8' },
                        sidebar: '#0f172a'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="h-full">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 flex flex-col bg-slate-900 text-white" id="sidebar">
        <!-- Logo -->
        <div class="flex items-center h-16 px-6 border-b border-slate-700">
            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                <i class="fa-solid fa-heart-pulse text-white text-sm"></i>
            </div>
            <span class="font-bold text-lg tracking-tight">eBizMedic</span>
        </div>

        <!-- User Info -->
        <div class="px-4 py-4 border-b border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    <?= strtoupper(substr(Auth::user()['name'], 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate"><?= e(Auth::user()['name']) ?></p>
                    <p class="text-xs text-slate-400 capitalize"><?= e(Auth::role()) ?></p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-4 overflow-y-auto">
            <?php $role = Auth::role(); ?>

            <?php if ($role === 'admin'): ?>
            <?php $pendingCount = Database::queryOne('SELECT COUNT(*) as c FROM users WHERE approved = 0 AND role IN ("medic","organisation")')['c'] ?? 0; ?>
            <p class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Admin</p>
            <?= navLink('admin/dashboard', 'fa-gauge', 'Dashboard') ?>
            <?= navLink('admin/doctors', 'fa-user-doctor', 'Doctors') ?>
            <?= navLink('admin/organisations', 'fa-hospital', 'Organisations') ?>
            <?= navLink('admin/appointments', 'fa-calendar-check', 'Appointments') ?>
            <?= navLink('admin/users', 'fa-users', 'Users') ?>
            <?= navLinkBadge('admin/approvals', 'fa-user-check', 'Approvals', $pendingCount) ?>
            <?= navLink('admin/reports', 'fa-chart-bar', 'Reports') ?>
            <?= navLink('admin/settings', 'fa-gear', 'Settings') ?>

            <?php elseif ($role === 'medic'): ?>
            <p class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Medic</p>
            <?= navLink('medic/dashboard', 'fa-gauge', 'Dashboard') ?>
            <?= navLink('medic/appointments', 'fa-calendar-check', 'Appointments') ?>
            <?= navLink('medic/records', 'fa-notes-medical', 'Medical Records') ?>
            <?= navLink('medic/schedule', 'fa-clock', 'My Schedule') ?>
            <?= navLink('medic/profile', 'fa-user', 'My Profile') ?>

            <?php elseif ($role === 'organisation'): ?>
            <p class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Organisation</p>
            <?= navLink('organisation/dashboard', 'fa-gauge', 'Dashboard') ?>
            <?= navLink('organisation/doctors', 'fa-user-doctor', 'Our Doctors') ?>
            <?= navLink('organisation/services', 'fa-stethoscope', 'Services') ?>
            <?= navLink('organisation/appointments', 'fa-calendar-check', 'Appointments') ?>
            <?= navLink('organisation/profile', 'fa-building', 'Profile') ?>

            <?php elseif ($role === 'user'): ?>
            <p class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Patient</p>
            <?= navLink('user/dashboard', 'fa-gauge', 'Dashboard') ?>
            <?= navLink('user/appointments', 'fa-calendar-check', 'My Appointments') ?>
            <?= navLink('user/records', 'fa-notes-medical', 'Medical Records') ?>
            <?= navLink('user/profile', 'fa-user', 'My Profile') ?>
            <?= navLink('doctors', 'fa-user-doctor', 'Find Doctors') ?>
            <?php endif; ?>

            <div class="mt-6 pt-4 border-t border-slate-700">
                <?= navLink('home', 'fa-house', 'Public Site') ?>
                <a href="<?= url('logout') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-red-500/10 hover:text-red-400 transition-colors text-sm mt-1">
                    <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Top bar -->
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 flex-shrink-0">
            <div>
                <h1 class="text-lg font-semibold text-gray-800"><?= e($pageTitle ?? '') ?></h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500"><?= date('D, d M Y') ?></span>
            </div>
        </header>

        <!-- Flash messages -->
        <div class="px-6 pt-4">
            <?php if ($msg = flash('success')): ?>
            <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
                <i class="fa-solid fa-circle-check text-green-500"></i>
                <?= e($msg) ?>
            </div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
            <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
                <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                <?= e($msg) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Page content -->
        <main class="flex-1 overflow-y-auto px-6 pb-8">
            <?php include dirname(__DIR__) . '/' . $content . '.php'; ?>
        </main>

    </div>
</div>

<?php
function navLinkBadge(string $path, string $icon, string $label, int $badge): string {
    $current = trim($_GET['url'] ?? '', '/');
    if ($current === '') $current = 'home';
    $active = $current === $path;
    $cls = $active ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white';
    $badgeHtml = $badge > 0
        ? '<span class="ml-auto bg-yellow-400 text-yellow-900 text-xs font-bold px-1.5 py-0.5 rounded-full">' . $badge . '</span>'
        : '';
    return sprintf(
        '<a href="%s" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-sm mb-0.5 %s">
            <i class="fa-solid %s w-4 text-center"></i><span>%s</span>%s
         </a>',
        url($path), $cls, $icon, htmlspecialchars($label, ENT_QUOTES), $badgeHtml
    );
}

function navLink(string $path, string $icon, string $label): string {
    $current = trim($_GET['url'] ?? '', '/');
    if ($current === '') $current = 'home';
    $active = $current === $path;
    $cls = $active
        ? 'bg-blue-600 text-white'
        : 'text-slate-400 hover:bg-slate-800 hover:text-white';
    return sprintf(
        '<a href="%s" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-sm mb-0.5 %s">
            <i class="fa-solid %s w-4 text-center"></i><span>%s</span>
         </a>',
        url($path), $cls, $icon, htmlspecialchars($label, ENT_QUOTES)
    );
}
?>

</body>
</html>
