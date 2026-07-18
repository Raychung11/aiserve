<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';

$bySource = db()->query("
    SELECT source_page, COUNT(*) AS total
    FROM contacts
    GROUP BY source_page
    ORDER BY total DESC
")->fetchAll();

$byInterest = db()->query("
    SELECT interest, COUNT(*) AS total
    FROM contacts
    WHERE interest IS NOT NULL AND interest <> ''
    GROUP BY interest
    ORDER BY total DESC
")->fetchAll();

$byStatus = db()->query("
    SELECT status, COUNT(*) AS total
    FROM contacts
    GROUP BY status
    ORDER BY total DESC
")->fetchAll();

admin_header('Analytics Breakdown');
?>

<div class="stats">
    <div class="card stat">
        <strong><?= count_table('contacts') ?></strong>
        <span class="muted">Total Contacts</span>
    </div>
    <div class="card stat">
        <strong><?= count_table('whatsapp_templates') ?></strong>
        <span class="muted">WhatsApp Templates</span>
    </div>
    <div class="card stat">
        <strong><?= count_table('media_library') ?></strong>
        <span class="muted">Media Files</span>
    </div>
    <div class="card stat">
        <strong><?= count_recipients_by_send_status('sent') ?></strong>
        <span class="muted">Emails Sent</span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">
    <div class="card chart-card">
        <h3 style="margin-top:0;">By Source</h3>
        <canvas id="sourceChart"></canvas>
    </div>
    <div class="card chart-card">
        <h3 style="margin-top:0;">By Status</h3>
        <canvas id="statusBreakdownChart"></canvas>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Contacts by Source</h3>
    <div class="table-wrap">
        <table class="desktop-table">
            <thead><tr><th>Source</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($bySource as $r): ?>
                <tr><td><?= h($r['source_page']) ?></td><td><?= (int)$r['total'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mobile-card-list">
            <?php foreach ($bySource as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Source</span><?= h($r['source_page']) ?></div>
                    <div class="row"><span class="label">Total</span><?= (int)$r['total'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Contacts by Interest</h3>
    <div class="table-wrap">
        <table class="desktop-table">
            <thead><tr><th>Interest</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($byInterest as $r): ?>
                <tr><td><?= h($r['interest']) ?></td><td><?= (int)$r['total'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mobile-card-list">
            <?php foreach ($byInterest as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Interest</span><?= h($r['interest']) ?></div>
                    <div class="row"><span class="label">Total</span><?= (int)$r['total'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <h3 style="margin-top:0;">Contacts by Status</h3>
    <div class="table-wrap">
        <table class="desktop-table">
            <thead><tr><th>Status</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($byStatus as $r): ?>
                <tr><td><?= h($r['status']) ?></td><td><?= (int)$r['total'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mobile-card-list">
            <?php foreach ($byStatus as $r): ?>
                <div class="mobile-card">
                    <div class="row"><span class="label">Status</span><?= h($r['status']) ?></div>
                    <div class="row"><span class="label">Total</span><?= (int)$r['total'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('sourceChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($bySource, 'source_page')) ?>,
        datasets: [{ label: 'Contacts', data: <?= json_encode(array_map('intval', array_column($bySource, 'total'))) ?> }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('statusBreakdownChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($byStatus, 'status')) ?>,
        datasets: [{ label: 'Contacts', data: <?= json_encode(array_map('intval', array_column($byStatus, 'total'))) ?> }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<style>
@media (max-width: 900px){
    body .main > div[style*="grid-template-columns:1fr 1fr"]{
        grid-template-columns:1fr !important;
    }
}
</style>

<?php admin_footer(); ?>