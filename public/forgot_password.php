<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
if (auth_check()) redirect(APP_URL . '/dashboard');

// MVP: show admin contact instead of email reset
layout_head('Lupa Kata Laluan');
?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:var(--kasih-bg);">
  <div style="width:100%;max-width:420px;">
    <div style="text-align:center;margin-bottom:24px;">
      <a href="<?= APP_URL ?>/" style="font-size:1.5rem;font-weight:900;color:var(--gold-dark);">✦ Kasih Gold Easy</a>
    </div>
    <div class="card-kasih card-gold">
      <h1 style="font-size:1.2rem;font-weight:700;margin-bottom:16px;">🔑 Lupa Kata Laluan</h1>
      <div class="alert alert-info">
        Untuk menetapkan semula kata laluan, sila hubungi sokongan kami melalui WhatsApp:<br>
        <strong><a href="https://wa.me/<?= h(get_setting('support_whatsapp','')) ?>"><?= h(get_setting('support_whatsapp','')) ?></a></strong><br>
        <small>Berikan e-mel berdaftar anda untuk pengesahan.</small>
      </div>
      <a href="<?= APP_URL ?>/login" class="btn-gold-outline btn-block" style="text-align:center;">← Kembali ke Log Masuk</a>
    </div>
  </div>
</div>
<?php layout_footer(); ?>
