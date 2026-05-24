<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-white text-gray-800">

<!-- Navbar -->
<nav class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="<?= url('home') ?>" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-heart-pulse text-white text-sm"></i>
                </div>
                <span class="font-bold text-xl text-blue-600">eBizMedic</span>
            </a>

            <!-- Links -->
            <div class="hidden md:flex items-center gap-8">
                <a href="<?= url('home') ?>"    class="text-sm text-gray-600 hover:text-blue-600 transition-colors">Home</a>
                <a href="<?= url('doctors') ?>" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">Find Doctors</a>
            </div>

            <!-- Auth -->
            <div class="flex items-center gap-3">
                <?php if (Auth::check()): ?>
                <a href="<?= url(Auth::dashboardPath()) ?>" class="text-sm text-blue-600 font-medium hover:underline">Dashboard</a>
                <a href="<?= url('logout') ?>" class="text-sm text-gray-500 hover:text-gray-700">Logout</a>
                <?php else: ?>
                <a href="<?= url('login') ?>" class="text-sm text-gray-600 hover:text-blue-600">Login</a>
                <a href="<?= url('register') ?>" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Flash messages -->
<?php if ($msg = flash('success')): ?>
<div class="max-w-7xl mx-auto px-4 mt-4">
    <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-check text-green-500"></i> <?= e($msg) ?>
    </div>
</div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
<div class="max-w-7xl mx-auto px-4 mt-4">
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i> <?= e($msg) ?>
    </div>
</div>
<?php endif; ?>

<!-- Page content -->
<?php include dirname(__DIR__) . '/' . $content . '.php'; ?>

<!-- Footer -->
<footer class="bg-slate-900 text-slate-400 py-12 mt-20">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <div class="flex items-center justify-center gap-2 mb-4">
            <div class="w-7 h-7 bg-blue-500 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-heart-pulse text-white text-xs"></i>
            </div>
            <span class="text-white font-bold text-lg">eBizMedic</span>
        </div>
        <p class="text-sm">Healthcare at your fingertips &mdash; connect with trusted doctors and clinics.</p>
        <p class="text-xs mt-4 text-slate-600">&copy; <?= date('Y') ?> eBizMedic. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
