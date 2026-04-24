<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();
require_role('super_admin'); // Only super admin

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $allowed_keys = ['site_name','site_email','site_phone','default_commission','premium_price','enable_ai','openai_key','n8n_webhook','maintenance_mode'];
    foreach ($allowed_keys as $key) {
        if (isset($_POST[$key])) {
            $pdo->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?')
                ->execute([trim($_POST[$key]), $key]);
        }
    }
    log_activity('admin_update_settings');
    flash('success', 'Settings saved successfully.');
    redirect('admin/settings');
}

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = 'Settings — Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <div class="main-content">
    <div class="page-header"><h1><i class="bi bi-gear me-2 text-gold"></i>Platform Settings</h1></div>
    <?php render_flash(); ?>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <form method="POST">
          <?= csrf_field() ?>
          <!-- General -->
          <div class="mm2h-form-card mb-4">
            <h5 class="mb-4">General</h5>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Platform Name</label>
                <input type="text" name="site_name" class="form-control" value="<?= h($settings['site_name'] ?? 'MM2H 管家') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Contact Email</label>
                <input type="email" name="site_email" class="form-control" value="<?= h($settings['site_email'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Contact Phone</label>
                <input type="tel" name="site_phone" class="form-control" value="<?= h($settings['site_phone'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Premium Plan Price (MYR/month)</label>
                <input type="number" name="premium_price" class="form-control" step="1" value="<?= h($settings['premium_price'] ?? '99') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Default Commission Rate (%)</label>
                <input type="number" name="default_commission" class="form-control" step="0.5" min="0" max="50" value="<?= h($settings['default_commission'] ?? '20') ?>">
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check me-4">
                  <input type="checkbox" class="form-check-input" name="maintenance_mode" id="maint" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="maint">Maintenance Mode</label>
                </div>
              </div>
            </div>
          </div>

          <!-- AI Integration -->
          <div class="mm2h-form-card mb-4">
            <h5 class="mb-4">AI & Integrations</h5>
            <div class="row g-3">
              <div class="col-md-6 d-flex align-items-center gap-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="enable_ai" id="enableAi" value="1"
                         <?= ($settings['enable_ai'] ?? '0') === '1' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="enableAi">Enable AI Onboarding</label>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label">OpenAI API Key</label>
                <input type="password" name="openai_key" class="form-control"
                       value="<?= h($settings['openai_key'] ?? '') ?>" placeholder="sk-…">
                <div class="form-text">Used for AI eligibility assessment. Leave blank to use rule-based logic.</div>
              </div>
              <div class="col-12">
                <label class="form-label">n8n Webhook URL</label>
                <input type="url" name="n8n_webhook" class="form-control"
                       value="<?= h($settings['n8n_webhook'] ?? '') ?>" placeholder="https://your-n8n.com/webhook/…">
                <div class="form-text">Used for workflow automation integration.</div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-gold px-5 py-2">
            <i class="bi bi-check-lg me-2"></i>Save Settings
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
