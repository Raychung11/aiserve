<?php
$page_title = t('contact_title') . ' — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';

$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$message) {
        $error = 'Please fill in all required fields with a valid email.';
    } else {
        // Log enquiry in activity_logs (email sending would go here in production)
        try {
            db()->prepare(
                'INSERT INTO activity_logs (action, ip_address) VALUES (?, ?)'
            )->execute(['contact_form: ' . $email, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (PDOException $e) { /* non-fatal */ }
        $sent = true;
    }
}
?>

<section class="section section-dark" style="padding:4rem 0;">
  <div class="container">
    <div class="section-label"><?= t('nav_contact') ?></div>
    <h1 class="hero-title" style="font-size:clamp(1.8rem,4vw,3rem);"><?= t('contact_title') ?></h1>
    <p class="hero-subtitle">Our team speaks English, Mandarin, and Cantonese. We'll respond within 24 hours.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-5">
        <h3 class="mb-4">Get in Touch</h3>
        <?php
        $contacts = [
          ['bi-envelope-fill',   'Email',    get_setting('site_email', 'info@mm2h.com')],
          ['bi-telephone-fill',  'Phone / WhatsApp', get_setting('site_phone', '+60 3-XXXX XXXX')],
          ['bi-geo-alt-fill',    'Office',   'Kuala Lumpur, Malaysia'],
          ['bi-clock-fill',      'Hours',    'Mon–Fri 9:00–18:00 MYT (UTC+8)'],
        ];
        foreach ($contacts as [$icon, $label, $value]):
        ?>
        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="stat-icon" style="background:rgba(200,160,60,.1);color:var(--secondary);flex-shrink:0;">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <div>
            <div class="fw-bold small text-uppercase text-muted mb-1"><?= h($label) ?></div>
            <div><?= h($value) ?></div>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="mt-4">
          <div class="fw-bold mb-2">Follow us</div>
          <div class="d-flex gap-3">
            <?php foreach (['bi-facebook', 'bi-linkedin', 'bi-whatsapp', 'bi-wechat'] as $ic): ?>
            <a href="#" class="btn btn-outline-gold btn-sm"><i class="bi <?= $ic ?>"></i></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <?php if ($sent): ?>
        <div class="alert alert-success">
          <i class="bi bi-check-circle-fill me-2"></i>
          Thank you for your message! Our team will reply within 24 hours.
        </div>
        <?php else: ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        <div class="mm2h-form-card">
          <form method="POST">
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label"><?= t('contact_name') ?> <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= h($_POST['name'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label"><?= t('contact_email') ?> <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?= h($_POST['email'] ?? '') ?>" required>
              </div>
              <div class="col-12">
                <label class="form-label">Subject</label>
                <select name="subject" class="form-select">
                  <option>MM2H Application Enquiry</option>
                  <option>Property Matching</option>
                  <option>Banking Support</option>
                  <option>Business Networking</option>
                  <option>Partner Programme</option>
                  <option>Technical Support</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label"><?= t('contact_message') ?> <span class="text-danger">*</span></label>
                <textarea name="message" class="form-control" rows="5" required><?= h($_POST['message'] ?? '') ?></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-gold px-4 py-2">
                  <i class="bi bi-send me-2"></i><?= t('contact_send') ?>
                </button>
              </div>
            </div>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
