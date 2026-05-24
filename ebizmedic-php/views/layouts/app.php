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
                <?php $__av = Auth::user()['avatar'] ?? null; ?>
                <?php if ($__av): ?>
                <img src="<?= asset($__av) ?>" class="w-9 h-9 rounded-full object-cover flex-shrink-0" alt="">
                <?php else: ?>
                <div class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    <?= strtoupper(substr(Auth::user()['name'], 0, 1)) ?>
                </div>
                <?php endif; ?>
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
            <?= navLink('admin/dispensary', 'fa-capsules', 'Dispensary') ?>
            <?= navLink('admin/reports', 'fa-chart-bar', 'Reports') ?>
            <?= navLink('admin/settings', 'fa-gear', 'Settings') ?>

            <?php elseif ($role === 'medic'): ?>
            <p class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Medic</p>
            <?= navLink('medic/dashboard', 'fa-gauge', 'Dashboard') ?>
            <?= navLink('medic/appointments', 'fa-calendar-check', 'Appointments') ?>
            <?= navLink('medic/records', 'fa-notes-medical', 'Medical Records') ?>
            <?= navLink('medic/dispensary', 'fa-prescription-bottle-medical', 'Dispensary') ?>
            <?= navLink('medic/schedule', 'fa-clock', 'My Schedule') ?>
            <?= navLink('medic/profile', 'fa-user', 'My Profile') ?>

            <?php elseif ($role === 'organisation'): ?>
            <p class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Organisation</p>
            <?= navLink('organisation/dashboard', 'fa-gauge', 'Dashboard') ?>
            <?= navLink('organisation/doctors', 'fa-user-doctor', 'Our Doctors') ?>
            <?= navLink('organisation/services', 'fa-stethoscope', 'Services') ?>
            <?= navLink('organisation/appointments', 'fa-calendar-check', 'Appointments') ?>
            <?= navLink('organisation/dispensary', 'fa-capsules', 'Dispensary') ?>
            <?= navLink('organisation/profile', 'fa-building', 'Profile') ?>

            <?php elseif ($role === 'pharmacist'): ?>
            <p class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pharmacist</p>
            <?= navLink('pharmacist/dashboard', 'fa-gauge', 'Dashboard') ?>
            <?= navLink('pharmacist/dispense', 'fa-hand-holding-medical', 'Dispense') ?>
            <?= navLink('pharmacist/medicines', 'fa-capsules', 'Medicines') ?>
            <?= navLink('pharmacist/history', 'fa-clock-rotate-left', 'History') ?>

            <?php elseif ($role === 'user'): ?>
            <p class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Patient</p>
            <?= navLink('user/dashboard', 'fa-gauge', 'Dashboard') ?>
            <?= navLink('user/appointments', 'fa-calendar-check', 'My Appointments') ?>
            <?= navLink('user/records', 'fa-notes-medical', 'Medical Records') ?>
            <?= navLink('user/dispensary', 'fa-prescription-bottle-medical', 'My Medicines') ?>
            <?= navLink('user/health-profile', 'fa-heart-pulse', 'Health Profile') ?>
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
        <?php
        $notifUnread  = 0;
        $notifRecent  = [];
        if (Auth::check()) {
            $notifUnread = (int) (Database::queryOne(
                'SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0',
                [Auth::id()]
            )['c'] ?? 0);
            $notifRecent = Database::query(
                'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
                [Auth::id()]
            );
        }
        ?>
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 flex-shrink-0">
            <div>
                <h1 class="text-lg font-semibold text-gray-800"><?= e($pageTitle ?? '') ?></h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500"><?= date('D, d M Y') ?></span>

                <!-- Notification Bell -->
                <div class="relative" id="notifBell">
                    <button onclick="document.getElementById('notifDropdown').classList.toggle('hidden')"
                            class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                        <i class="fa-regular fa-bell text-lg"></i>
                        <?php if ($notifUnread > 0): ?>
                        <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none"><?= min($notifUnread, 9) ?></span>
                        <?php endif; ?>
                    </button>

                    <!-- Dropdown -->
                    <div id="notifDropdown" class="hidden absolute right-0 top-12 w-80 bg-white rounded-2xl border border-gray-200 shadow-xl z-50 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <span class="font-semibold text-sm text-gray-900">Notifications</span>
                            <?php if ($notifUnread > 0): ?>
                            <form method="POST" action="<?= url('notifications/read') ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-xs text-blue-600 hover:underline">Mark all read</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($notifRecent)): ?>
                        <p class="text-sm text-gray-400 text-center py-6">No notifications yet</p>
                        <?php else: ?>
                        <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
                            <?php foreach ($notifRecent as $n):
                                $nIcons = [
                                    'appointment' => ['fa-calendar-check', 'blue'],
                                    'record'      => ['fa-notes-medical',  'green'],
                                    'dispensing'  => ['fa-capsules',        'purple'],
                                    'approval'    => ['fa-user-check',      'teal'],
                                ];
                                [$nIcon, $nColor] = $nIcons[$n['type']] ?? ['fa-bell', 'gray'];
                            ?>
                            <div class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 <?= !$n['is_read'] ? 'bg-blue-50/40' : '' ?>">
                                <div class="w-8 h-8 rounded-full bg-<?= $nColor ?>-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i class="fa-solid <?= $nIcon ?> text-<?= $nColor ?>-600 text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <?php if ($n['link']): ?>
                                    <a href="<?= url($n['link']) ?>" class="block">
                                    <?php endif; ?>
                                        <p class="text-sm font-medium text-gray-900 truncate"><?= e($n['title']) ?></p>
                                        <p class="text-xs text-gray-400 mt-0.5"><?= ago($n['created_at']) ?></p>
                                    <?php if ($n['link']): ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?= url('notifications') ?>" class="block text-center text-xs text-blue-600 hover:underline py-3 border-t border-gray-100">
                            View all notifications
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        <script>
        document.addEventListener('click', function(e) {
            var bell = document.getElementById('notifBell');
            if (bell && !bell.contains(e.target)) {
                document.getElementById('notifDropdown').classList.add('hidden');
            }
        });
        </script>

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
