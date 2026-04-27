<?php
// Secure document download handler — works for both admin and owner roles
require_once __DIR__.'/../config/app.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../src/Database.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/ActivityLog.php';

Auth::start();
Auth::require();

$user      = Auth::user();
$tenantId  = Auth::tenantId();
$docId     = (int)($_GET['id'] ?? 0);

if (!$docId) { http_response_code(404); die('Not found.'); }

// Fetch document with property ownership info
$doc = Database::fetchOne(
    "SELECT d.*, p.owner_id AS prop_owner_id FROM owner_documents d
     JOIN properties p ON p.id = d.property_id
     WHERE d.id=? AND d.tenant_id=?",
    [$docId, $tenantId]
);

if (!$doc) { http_response_code(404); die('Document not found.'); }

// Access control: owners may only download docs for their own properties
if ($user['role'] === 'owner') {
    $ownerId = (int)($user['owner_id'] ?? 0);
    if ((int)$doc['prop_owner_id'] !== $ownerId) {
        http_response_code(403); die('Access denied.');
    }
}

ActivityLog::record('document.download', "Downloaded doc #$docId", $tenantId, $user['id']);

// External URL: redirect
if ($doc['url'] && !$doc['file_path']) {
    header('Location: ' . $doc['url']); exit;
}

// File download
$filePath = __DIR__.'/../'.$doc['file_path'];
if (!$doc['file_path'] || !file_exists($filePath)) {
    http_response_code(404); die('File not found on server.');
}

$mime     = $doc['mime_type'] ?: mime_content_type($filePath);
$filename = basename($filePath);
// Suggest a clean download name using the document title
$ext         = pathinfo($filename, PATHINFO_EXTENSION);
$cleanTitle  = preg_replace('/[^a-z0-9_\-]/i', '_', $doc['title']);
$downloadAs  = $cleanTitle.'.'.$ext;

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $downloadAs . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');
readfile($filePath);
exit;
