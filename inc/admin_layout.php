<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/brand.php';

function admin_header(string $title = 'Admin Dashboard'): void {
    $user = admin_user();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?= h($title) ?> | AiServe.my Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            /* Brand tokens come from inc/brand.php (single source of truth). */
            :root{
<?= brand_css_vars() ?>
            }

            *{box-sizing:border-box}
            html,body{margin:0;padding:0}
            body{
                font-family:var(--font-sans);
                background:var(--bg);
                color:var(--text);
            }

            a{text-decoration:none;color:inherit}

            .layout{
                display:grid;
                grid-template-columns:280px 1fr;
                min-height:100vh;
            }

            .sidebar{
                background:var(--sidebar);
                color:#fff;
                padding:18px 16px;
                position:sticky;
                top:0;
                height:100vh;
                overflow:auto;
            }

            .logo{
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:12px;
                margin-bottom:18px;
            }

            .logo-left{
                display:flex;
                align-items:center;
                gap:12px;
            }

            .logo-mark{
                width:42px;
                height:42px;
                border-radius:14px;
                display:grid;
                place-items:center;
                font-weight:800;
                background:linear-gradient(135deg,var(--primary),var(--primary2));
            }

            .logo-title{font-weight:800}
            .logo-sub{font-size:12px;color:#c4b5fd}

            .sidebar-toggle{
                display:none;
                border:none;
                background:rgba(255,255,255,.08);
                color:#fff;
                padding:10px 12px;
                border-radius:12px;
                cursor:pointer;
                font-weight:700;
            }

            .menu-groups{
                display:grid;
                gap:10px;
            }

            .menu-group{
                background:rgba(255,255,255,.04);
                border:1px solid rgba(255,255,255,.06);
                border-radius:16px;
                overflow:hidden;
            }

            .menu-group summary{
                list-style:none;
                cursor:pointer;
                padding:13px 14px;
                font-weight:700;
                color:#fff;
                display:flex;
                justify-content:space-between;
                align-items:center;
            }

            .menu-group summary::-webkit-details-marker{display:none}
            .menu-group summary:hover{
                background:rgba(255,255,255,.05);
            }

            .menu-items{
                display:grid;
                gap:6px;
                padding:0 10px 12px 10px;
            }

            .menu-items a{
                display:block;
                padding:10px 12px;
                border-radius:12px;
                color:var(--sidebarText);
                font-weight:600;
                font-size:14px;
            }

            .menu-items a:hover{
                background:rgba(255,255,255,.08);
                color:#fff;
            }

            .main{
                padding:24px;
                min-width:0;
            }

            .top{
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:16px;
                margin-bottom:22px;
            }

            .page-title{
                margin:0;
                font-size:30px;
                line-height:1.15;
            }

            .muted{color:var(--muted)}

            .card{
                background:var(--card);
                border:1px solid var(--line);
                border-radius:var(--radius);
                padding:22px;
                box-shadow:var(--shadow);
            }

            .stats{
                display:grid;
                grid-template-columns:repeat(4,1fr);
                gap:16px;
            }

            .stat strong{
                display:block;
                font-size:30px;
                margin-bottom:6px;
            }

            .btn,
            .btn-secondary{
                display:inline-flex;
                align-items:center;
                justify-content:center;
                min-height:42px;
                padding:0 16px;
                border-radius:999px;
                font-weight:700;
                border:none;
                cursor:pointer;
            }

            .btn{
                background:linear-gradient(135deg,var(--primary),var(--primary2));
                color:#fff;
            }

            .btn-secondary{
                background:#fff;
                color:var(--primary);
                border:1px solid var(--line);
            }

            .pill{
                display:inline-block;
                padding:6px 10px;
                border-radius:999px;
                font-size:12px;
                font-weight:700;
                background:#ede9fe;
                color:#6d28d9;
            }

            .form-grid{
                display:grid;
                grid-template-columns:1fr 1fr;
                gap:14px;
            }

            .field{display:grid;gap:8px}
            .field.full{grid-column:1 / -1}

            input,textarea,select{
                width:100%;
                min-height:48px;
                padding:12px 14px;
                border:1px solid var(--line);
                border-radius:12px;
                background:#fff;
                font:inherit;
                color:var(--text);
            }

            textarea{
                min-height:180px;
                resize:vertical;
            }

            table{
                width:100%;
                border-collapse:collapse;
                background:#fff;
            }

            th,td{
                padding:14px;
                border-bottom:1px solid var(--line);
                text-align:left;
                font-size:14px;
                vertical-align:top;
            }

            th{background:#f5f0ff}

            .table-wrap{
                overflow:auto;
                -webkit-overflow-scrolling:touch;
            }

            .chart-card canvas{
                width:100% !important;
                max-height:320px;
            }

            @media (max-width: 1100px){
                .layout{grid-template-columns:1fr}
                .sidebar{
                    position:relative;
                    height:auto;
                    overflow:visible;
                }
                .sidebar-toggle{display:inline-flex}
                .sidebar-panels{display:none;margin-top:12px}
                .sidebar.open .sidebar-panels{display:block}
                .stats{grid-template-columns:repeat(2,1fr)}
            }

            @media (max-width: 760px){
                .main{padding:16px}
                .top{
                    flex-direction:column;
                    align-items:flex-start;
                }
                .stats{grid-template-columns:1fr}
                .form-grid{grid-template-columns:1fr}
                .page-title{font-size:24px}
            }
        </style>
    </head>
    <body>
    <div class="layout">
        <aside class="sidebar" id="adminSidebar">
            <div class="logo">
                <div class="logo-left">
                    <div class="logo-mark">A</div>
                    <div>
                        <div class="logo-title">AiServe.my</div>
                        <div class="logo-sub">Admin Panel</div>
                    </div>
                </div>
                <button class="sidebar-toggle" type="button" onclick="toggleAdminSidebar()">Menu</button>
            </div>

            <div class="sidebar-panels">
                <div class="menu-groups">
                    <details class="menu-group" open>
                        <summary>Overview <span>▾</span></summary>
                        <div class="menu-items">
                            <a href="/admin/index.php">Dashboard</a>
                            <a href="/admin/analytics_breakdown.php">Analytics</a>
                            <a href="/admin/leads_kanban.php">Lead Kanban</a>
                            <a href="/admin/reminders.php">Reminders</a>
                        </div>
                    </details>

                    <details class="menu-group" open>
                        <summary>CRM & Content <span>▾</span></summary>
                        <div class="menu-items">
                            <a href="/admin/contacts.php">Contacts</a>
                            <a href="/admin/projects.php">Projects</a>
                            <a href="/admin/blog_posts.php">Blog & Cases</a>
                            <a href="/admin/landing_pages.php">Landing Pages</a>
                            <a href="/admin/content_blocks.php">Content Blocks</a>
                            <a href="/admin/seo_pages.php">SEO Meta</a>
                            <a href="/admin/media_library.php">Media</a>
                            <a href="/admin/homepage_settings.php">Homepage Settings</a>
                            <a href="/admin/industries_builder.php">Industries Builder</a>
                            <a href="/admin/demo_sites.php">Demo Sites</a>
                            <a href="/admin/ai_cs_page_builder.php">AI CS Page Builder</a>
                            <a href="/admin/ai_cs_showcases.php">AI CS Showcases</a>
                            <a href="/admin/client_logos.php">Client Logos</a>
                        </div>
                    </details>

                    <details class="menu-group">
                        <summary>WhatsApp Core <span>▾</span></summary>
                        <div class="menu-items">
                            <a href="/admin/whatsapp_inbox.php">Inbox</a>
                            <a href="/admin/whatsapp_inbox_advanced.php">Inbox Advanced</a>
                            <a href="/admin/whatsapp_ai_settings.php">AI Settings</a>
                            <a href="/admin/whatsapp_ai_logs.php">AI Logs</a>
                            <a href="/admin/whatsapp_templates.php">Templates</a>
                            <a href="/admin/whatsapp_template_library.php">Template Library</a>
                            <a href="/admin/whatsapp_knowledge_base.php">Knowledge Base</a>
                            <a href="/admin/whatsapp_routing.php">Routing</a>
                        </div>
                    </details>

                    <details class="menu-group">
                        <summary>WhatsApp Sales <span>▾</span></summary>
                        <div class="menu-items">
                            <a href="/admin/whatsapp_leads.php">Leads</a>
                            <a href="/admin/whatsapp_tasks.php">Tasks</a>
                            <a href="/admin/whatsapp_followups.php">Follow-ups</a>
                            <a href="/admin/whatsapp_notifications.php">Notifications</a>
                            <a href="/admin/whatsapp_quote_builder.php">Quote Requests</a>
                            <a href="/admin/whatsapp_quotes_generated.php">Generated Quotes</a>
                            <a href="/admin/whatsapp_quote_approvals.php">Quote Approvals</a>
                            <a href="/admin/whatsapp_file_requests.php">File Requests</a>
                            <a href="/admin/whatsapp_voice_notes.php">Voice Notes</a>
                            <a href="/admin/whatsapp_sla.php">SLA</a>
                            <a href="/admin/whatsapp_branch_dashboard.php">Branch Dashboard</a>
                            <a href="/admin/whatsapp_leaderboard.php">Leaderboard</a>
                            <a href="/admin/whatsapp_manager_dashboard.php">Manager Dashboard</a>
                        </div>
                    </details>

                    <details class="menu-group">
                        <summary>Marketing <span>▾</span></summary>
                        <div class="menu-items">
                            <a href="/admin/campaigns.php">Campaigns</a>
                            <a href="/admin/campaign_analytics.php">Campaign Analytics</a>
                            <a href="/admin/email_marketing.php">Email Draft</a>
                        </div>
                    </details>

                    <details class="menu-group">
                        <summary>System <span>▾</span></summary>
                        <div class="menu-items">
                            <a href="/admin/admin_users.php">Admin Users</a>
                            <a href="/admin/settings.php">Settings</a>
                            <a href="/admin_logout.php">Logout</a>
                        </div>
                    </details>
                </div>
            </div>
        </aside>

        <main class="main">
            <div class="top">
                <div>
                    <h1 class="page-title"><?= h($title) ?></h1>
                    <div class="muted"><?= h(COMPANY_NAME) ?></div>
                </div>
                <div class="muted">Logged in as <?= h($user['name'] ?? 'Admin') ?></div>
            </div>

            <script>
                function toggleAdminSidebar() {
                    document.getElementById('adminSidebar').classList.toggle('open');
                }
            </script>
    <?php
}

function admin_footer(): void {
    echo '</main></div></body></html>';
}