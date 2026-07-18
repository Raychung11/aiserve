<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

admin_header('Email Draft');
?>

<div class="card">
    <h3 style="margin-top:0;">Email Marketing</h3>
    <p class="muted">
        Use Campaigns to create and manage reusable email content for outreach and follow-up.
    </p>

    <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
        <a href="/admin/campaigns.php" class="btn">Open Campaign Manager</a>
        <a href="/admin/contacts.php" class="btn-secondary">View Contacts</a>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Recommended next upgrade</h3>
    <p class="muted">
        Next, connect SMTP or Brevo / Mailgun and add actual sending, recipient logging, unsubscribe handling, and campaign history.
    </p>
</div>

<?php admin_footer(); ?>