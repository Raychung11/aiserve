<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/admin_csrf.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!empty($_FILES['media_file']['name']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/media/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $originalName = basename($_FILES['media_file']['name']);
        $tmpPath = $_FILES['media_file']['tmp_name'];
        $mimeType = mime_content_type($tmpPath) ?: 'application/octet-stream';

        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = time() . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');
        $targetPath = $uploadDir . $safeName;

        if (move_uploaded_file($tmpPath, $targetPath)) {
            $fileUrl = APP_URL . '/uploads/media/' . $safeName;

            $stmt = db()->prepare("
                INSERT INTO media_library (file_name, file_path, file_url, file_type, uploaded_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $originalName,
                '/uploads/media/' . $safeName,
                $fileUrl,
                $mimeType,
                $_SESSION['admin_id'] ?? null
            ]);

            $message = 'Media uploaded successfully.';
        } else {
            $message = 'Upload failed.';
        }
    } else {
        $message = 'Please choose a file.';
    }
}

admin_header('Upload Media');
?>

<div class="card">
    <h3 style="margin-top:0;">Upload Media</h3>

    <?php if ($message !== ''): ?>
        <div class="pill" style="margin-bottom:14px;"><?= h($message) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <div class="form-grid">
            <div class="field full">
                <label>Choose File</label>
                <input type="file" name="media_file" required>
            </div>
            <div class="field full">
                <button type="submit" class="btn">Upload</button>
            </div>
        </div>
    </form>
</div>

<?php admin_footer(); ?>