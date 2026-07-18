<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/admin_layout.php';
require_once __DIR__ . '/../inc/chart_helper.php';

/*
|--------------------------------------------------------------------------
| Safe helpers
|--------------------------------------------------------------------------
*/
if (!function_exists('safeCountTable')) {
    function safeCountTable(string $table): int {
        try {
            $stmt = db()->query("SELECT COUNT(*) AS total FROM `{$table}`");
            $row = $stmt ? $stmt->fetch() : false;
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('safeCountWhere')) {
    function safeCountWhere(string $table, string $column, string $value): int {
        try {
            $stmt = db()->prepare("SELECT COUNT(*) AS total FROM `{$table}` WHERE `{$column}` = ?");
            $stmt->execute([$value]);
            $row = $stmt->fetch();
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('safeFetchAll')) {
    function safeFetchAll(string $sql, array $params = []): array {
        try {
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            return is_array($rows) ? $rows : [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('safeFetchOneValue')) {
    function safeFetchOneValue(string $sql, array $params = [], string $key = 'total'): int {
        try {
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return (int)($row[$key] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Core CRM / Marketing stats
|--------------------------------------------------------------------------
*/
$totalContacts   = safeCountTable('contacts');
$newContacts     = safeCountWhere('contacts', 'status', 'new');
$qualified       = safeCountWhere('contacts', 'status', 'qualified');
$totalCampaigns  = safeCountTable('email_campaigns');
$readyCampaigns  = safeCountWhere('email_campaigns', 'status', 'ready');
$sentCampaigns   = safeCountWhere('email_campaigns', 'status', 'sent');
$sentLogs        = safeCountWhere('email_campaign_recipients', 'send_status', 'sent');
$contentBlocks   = safeCountTable('content_blocks');

/*
|--------------------------------------------------------------------------
| Lead charts
|--------------------------------------------------------------------------
*/
$statusRows = safeFetchAll("
    SELECT status, COUNT(*) AS total
    FROM contacts
    GROUP BY status
    ORDER BY status ASC
");

$interestRows = safeFetchAll("
    SELECT interest, COUNT(*) AS total
    FROM contacts
    WHERE interest IS NOT NULL AND interest <> ''
    GROUP BY interest
    ORDER BY total DESC
    LIMIT 8
");

$statusLabels = [];
$statusValues = [];
foreach ($statusRows as $r) {
    $statusLabels[] = (string)($r['status'] ?? '');
    $statusValues[] = (int)($r['total'] ?? 0);
}

$interestLabels = [];
$interestValues = [];
foreach ($interestRows as $r) {
    $interestLabels[] = (string)($r['interest'] ?? '');
    $interestValues[] = (int)($r['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| WhatsApp grouped stats
|--------------------------------------------------------------------------
*/
$waConversations    = safeCountTable('wa_conversations');
$waMessages         = safeCountTable('wa_messages');
$waOutbound         = safeCountTable('wa_outbound_logs');
$waActionQueue      = safeCountTable('wa_action_queue');
$waAiQueue          = safeCountTable('wa_ai_queue');
$waAiLogs           = safeCountTable('wa_ai_logs');
$waLeadProfiles     = safeCountTable('wa_lead_profiles');
$waFollowups        = safeCountTable('wa_followups');
$waSalesTasks       = safeCountTable('wa_sales_tasks');
$waQuoteRequests    = safeCountTable('wa_quote_requests');
$waQuoteApprovals   = safeCountTable('wa_quote_approvals');
$waGeneratedQuotes  = safeCountTable('wa_generated_quotes');
$waVoiceNotes       = safeCountTable('wa_voice_notes');
$waFileRequests     = safeCountTable('wa_file_requests');
$waTemplateLibrary  = safeCountTable('wa_template_library');
$waKnowledgeBase    = safeCountTable('wa_knowledge_base');
$waRoutingRules     = safeCountTable('wa_department_routes');

/*
|--------------------------------------------------------------------------
| Optional manager / team numbers
|--------------------------------------------------------------------------
*/
$openTasks = safeFetchOneValue("
    SELECT COUNT(*) AS total
    FROM wa_sales_tasks
    WHERE task_status IN ('open','in_progress','waiting_customer')
");

$highPriorityTasks = safeFetchOneValue("
    SELECT COUNT(*) AS total
    FROM wa_sales_tasks
    WHERE task_status IN ('open','in_progress','waiting_customer')
      AND priority_level = 'high'
");

$pendingApprovals = safeFetchOneValue("
    SELECT COUNT(*) AS total
    FROM wa_quote_approvals
    WHERE approval_status = 'pending'
");

$pendingFollowups = safeFetchOneValue("
    SELECT COUNT(*) AS total
    FROM wa_followups
    WHERE followup_status = 'pending'
");

admin_header('Dashboard');
?>

<div class="stats">
    <div class="card stat">
        <strong><?= $totalContacts ?></strong>
        <span class="muted">Total Contacts</span>
    </div>
    <div class="card stat">
        <strong><?= $newContacts ?></strong>
        <span class="muted">New Leads</span>
    </div>
    <div class="card stat">
        <strong><?= $qualified ?></strong>
        <span class="muted">Qualified Leads</span>
    </div>
    <div class="card stat">
        <strong><?= $totalCampaigns ?></strong>
        <span class="muted">Campaign Drafts</span>
    </div>
</div>

<div class="stats" style="margin-top:16px;">
    <div class="card stat">
        <strong><?= $sentLogs ?></strong>
        <span class="muted">Emails Sent</span>
    </div>
    <div class="card stat">
        <strong><?= $contentBlocks ?></strong>
        <span class="muted">Content Blocks</span>
    </div>
    <div class="card stat">
        <strong><?= $readyCampaigns ?></strong>
        <span class="muted">Ready Campaigns</span>
    </div>
    <div class="card stat">
        <strong><?= $sentCampaigns ?></strong>
        <span class="muted">Sent Campaigns</span>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0 0 6px 0;">Quick Access</h3>
            <div class="muted">Shortcuts only. Use the sidebar for the full menu.</div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="/admin/contacts.php" class="btn">Contacts</a>
            <a href="/admin/whatsapp_inbox.php" class="btn-secondary">WA Inbox</a>
            <a href="/admin/whatsapp_tasks.php" class="btn-secondary">WA Tasks</a>
            <a href="/admin/whatsapp_quote_builder.php" class="btn-secondary">WA Quotes</a>
            <a href="/admin/whatsapp_sla.php" class="btn-secondary">WA SLA</a>
            <a href="/admin/campaigns.php" class="btn-secondary">Campaigns</a>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">
    <div class="card chart-card">
        <h3 style="margin-top:0;">Lead Status Overview</h3>
        <canvas id="statusChart"></canvas>
    </div>
    <div class="card chart-card">
        <h3 style="margin-top:0;">Top Interests</h3>
        <canvas id="interestChart"></canvas>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <h3 style="margin:0 0 6px 0;">WhatsApp Operations</h3>
            <div class="muted">Grouped WhatsApp status in one section.</div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="/admin/whatsapp_inbox_advanced.php" class="btn-secondary">Inbox+</a>
            <a href="/admin/whatsapp_leads.php" class="btn-secondary">Leads</a>
            <a href="/admin/whatsapp_followups.php" class="btn-secondary">Follow-ups</a>
            <a href="/admin/whatsapp_manager_dashboard.php" class="btn-secondary">Manager</a>
        </div>
    </div>

    <div class="stats" style="margin-top:16px;">
        <div class="card stat">
            <strong><?= $waConversations ?></strong>
            <span class="muted">WA Conversations</span>
        </div>
        <div class="card stat">
            <strong><?= $waMessages ?></strong>
            <span class="muted">WA Messages</span>
        </div>
        <div class="card stat">
            <strong><?= $waOutbound ?></strong>
            <span class="muted">WA Outbound Logs</span>
        </div>
        <div class="card stat">
            <strong><?= $waActionQueue ?></strong>
            <span class="muted">WA Action Queue</span>
        </div>
    </div>

    <div class="stats" style="margin-top:16px;">
        <div class="card stat">
            <strong><?= $waAiQueue ?></strong>
            <span class="muted">WA AI Queue</span>
        </div>
        <div class="card stat">
            <strong><?= $waAiLogs ?></strong>
            <span class="muted">WA AI Logs</span>
        </div>
        <div class="card stat">
            <strong><?= $waLeadProfiles ?></strong>
            <span class="muted">WA Lead Profiles</span>
        </div>
        <div class="card stat">
            <strong><?= $waFollowups ?></strong>
            <span class="muted">WA Follow-ups</span>
        </div>
    </div>

    <div class="stats" style="margin-top:16px;">
        <div class="card stat">
            <strong><?= $waSalesTasks ?></strong>
            <span class="muted">WA Sales Tasks</span>
        </div>
        <div class="card stat">
            <strong><?= $waQuoteRequests ?></strong>
            <span class="muted">WA Quote Requests</span>
        </div>
        <div class="card stat">
            <strong><?= $waQuoteApprovals ?></strong>
            <span class="muted">WA Quote Approvals</span>
        </div>
        <div class="card stat">
            <strong><?= $waGeneratedQuotes ?></strong>
            <span class="muted">Generated Quotes</span>
        </div>
    </div>

    <div class="stats" style="margin-top:16px;">
        <div class="card stat">
            <strong><?= $waVoiceNotes ?></strong>
            <span class="muted">WA Voice Notes</span>
        </div>
        <div class="card stat">
            <strong><?= $waFileRequests ?></strong>
            <span class="muted">WA File Requests</span>
        </div>
        <div class="card stat">
            <strong><?= $waTemplateLibrary ?></strong>
            <span class="muted">WA Template Library</span>
        </div>
        <div class="card stat">
            <strong><?= $waKnowledgeBase ?></strong>
            <span class="muted">WA Knowledge Base</span>
        </div>
    </div>

    <div class="stats" style="margin-top:16px;">
        <div class="card stat">
            <strong><?= $waRoutingRules ?></strong>
            <span class="muted">Routing Rules</span>
        </div>
        <div class="card stat">
            <strong><?= $openTasks ?></strong>
            <span class="muted">Open WA Tasks</span>
        </div>
        <div class="card stat">
            <strong><?= $highPriorityTasks ?></strong>
            <span class="muted">High Priority Tasks</span>
        </div>
        <div class="card stat">
            <strong><?= $pendingApprovals ?></strong>
            <span class="muted">Pending Approvals</span>
        </div>
    </div>

    <div class="stats" style="margin-top:16px;">
        <div class="card stat">
            <strong><?= $pendingFollowups ?></strong>
            <span class="muted">Pending Follow-ups</span>
        </div>
        <div class="card stat">
            <strong><?= safeCountTable('wa_internal_notifications') ?></strong>
            <span class="muted">Internal Notifications</span>
        </div>
        <div class="card stat">
            <strong><?= safeCountTable('wa_conversation_memory') ?></strong>
            <span class="muted">Conversation Memory</span>
        </div>
        <div class="card stat">
            <strong><?= safeCountTable('wa_conversation_notes') ?></strong>
            <span class="muted">Conversation Notes</span>
        </div>
    </div>
</div>

<script>
const statusData = <?= chart_json($statusLabels, $statusValues) ?>;
const interestData = <?= chart_json($interestLabels, $interestValues) ?>;

new Chart(document.getElementById('statusChart'), {
    type: 'bar',
    data: {
        labels: statusData.labels,
        datasets: [{
            label: 'Leads',
            data: statusData.values
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('interestChart'), {
    type: 'doughnut',
    data: {
        labels: interestData.labels,
        datasets: [{
            label: 'Interests',
            data: interestData.values
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<style>
@media (max-width: 900px){
    .chart-card canvas{max-height:260px;}
    body .main > div[style*="grid-template-columns:1fr 1fr"]{
        grid-template-columns:1fr !important;
    }
}
</style>

<?php admin_footer(); ?>