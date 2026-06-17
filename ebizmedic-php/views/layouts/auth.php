<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="h-full flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-slate-100 min-h-screen px-4">

<div class="w-full max-w-md">
    <!-- Logo -->
    <div class="text-center mb-8">
        <a href="<?= url('home') ?>" class="inline-flex items-center gap-2">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fa-solid fa-heart-pulse text-white"></i>
            </div>
            <span class="font-bold text-2xl text-blue-600">eBizMedic</span>
        </a>
    </div>

    <!-- Flash messages -->
    <?php if ($msg = flash('error')): ?>
    <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-exclamation text-red-500 flex-shrink-0"></i>
        <?= e($msg) ?>
    </div>
    <?php endif; ?>
    <?php if ($msg = flash('success')): ?>
    <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-check text-green-500 flex-shrink-0"></i>
        <?= e($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <?php include dirname(__DIR__) . '/' . $content . '.php'; ?>
    </div>
</div>

</body>
</html>
