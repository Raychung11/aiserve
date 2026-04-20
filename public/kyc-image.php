<?php
// Serves a user's own KYC images only.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';

auth_start_session();
if (!auth_check()) {
    http_response_code(403);
    exit('Forbidden');
}

$user_id = auth_id();
$file    = basename($_GET['f'] ?? '');

if (!$file || !preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|webp)$/i', $file)) {
    http_response_code(400);
    exit('Invalid file');
}

// Verify file belongs to requesting user
$db   = getDB();
$stmt = $db->prepare("SELECT id FROM kyc_submissions WHERE user_id=? AND (ic_front=? OR ic_back=?)");
$stmt->execute([$user_id, $file, $file]);
if (!$stmt->fetch() && !auth_is_admin()) {
    http_response_code(403);
    exit('Forbidden');
}

$path = UPLOAD_PATH . '/kyc/' . $file;
if (!file_exists($path) || !is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
