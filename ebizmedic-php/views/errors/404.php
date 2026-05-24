<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 — Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="text-center">
        <p class="text-6xl font-bold text-blue-600 mb-4">404</p>
        <h1 class="text-xl font-semibold text-gray-800 mb-2">Page Not Found</h1>
        <p class="text-gray-500 text-sm mb-6">The page you're looking for doesn't exist.</p>
        <a href="<?= defined('APP_URL') ? APP_URL : '/' ?>" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Go Home</a>
    </div>
</body>
</html>
