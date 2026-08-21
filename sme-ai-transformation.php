<?php
$pageTitle = 'SME AI Transformation | AiServe.my';
$pageDescription = 'AiServe SME AI Transformation is a 7-step agentic AI programme for SMEs — from business process mapping and SOP digitisation to AI customer service, AI business actions, and a live performance dashboard.';
include __DIR__ . '/inc/public_header.php';

$steps = [
    [
        'title' => 'Business Process Mapping',
        'desc'  => 'We start by understanding how your business actually runs today — workflows, handoffs, decisions, and bottlenecks — so AI is built around reality, not assumptions.',
    ],
    [
        'title' => 'SOP Digitisation',
        'desc'  => 'Your standard operating procedures are converted into structured, machine-readable form so they can be executed, referenced, and automated consistently.',
    ],
    [
        'title' => 'Knowledge Base',
        'desc'  => 'Company knowledge, policies, and product or service information are centralised into an AI-ready knowledge base that every AI agent can draw from.',
    ],
    [
        'title' => 'AI Customer Service',
        'desc'  => 'Deploy AI across WhatsApp, web, and other channels to handle inquiries, FAQs, and lead response with fast, consistent, on-brand answers.',
    ],
    [
        'title' => 'AI Business Actions',
        'desc'  => 'AI moves beyond answering — it triggers real actions such as bookings, orders, status updates, approvals, and internal notifications.',
    ],
    [
        'title' => 'BOS Integration',
        'desc'  => 'Everything connects into the AiServe Business Operating System so data and actions flow across departments instead of living in silos.',
    ],
    [
        'title' => 'AI Performance Dashboard',
        'desc'  => 'Management gets a live view of AI and business performance — volumes, response times, outcomes, and trends — for faster, clearer decisions.',
    ],
];
?>

<section class="hero" style="padding-bottom:20px;">
    <div class="container">
        <div class="eyebrow">SME Agentic AI Initiative</div>
        <h1 style="max-width:960px;">AiServe SME AI Transformation</h1>
        <p style="max-width:880px;">
            A structured, step-by-step programme that takes a small or medium business from manual operations
            to an AI-powered operating model. More than a chatbot — it is a full transformation across process,
            knowledge, service, and decision-making.
        </p>
        <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
            <a href="/contact.php" class="btn-primary">Talk to us about your business</a>
            <a href="/ai-bos.php" class="btn-secondary">See the AI-BOS</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="label">The 7-step journey</div>
            <h2>From process mapping to a live AI operating model</h2>
            <p>Each step builds on the last, so value compounds as your business moves through the programme.</p>
        </div>

        <div class="grid-4">
            <?php foreach ($steps as $i => $step): ?>
                <div class="card">
                    <div class="icon"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
                    <h3><?= h($step['title']) ?></h3>
                    <p><?= h($step['desc']) ?></p>
                </div>
            <?php endforeach; ?>
            <div class="card" style="background:var(--gradient-brand, linear-gradient(135deg,var(--primary),var(--primary2)));color:#fff;border:none;">
                <div class="icon" style="background:rgba(255,255,255,.18);color:#fff;">→</div>
                <h3 style="color:#fff;">Your AI-powered business</h3>
                <p style="color:rgba(255,255,255,.9);">A connected operating model where AI handles service, triggers actions, and reports performance in real time.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="label">Why it matters</div>
            <h2>A consulting + SaaS partnership, not a single tool</h2>
            <p>The programme pairs hands-on transformation advisory with the AiServe platform, so change is implemented and then sustained.</p>
        </div>

        <div class="grid-3">
            <div class="card">
                <h3>Advisory-led</h3>
                <p>We map, digitise, and design the AI operating model with your team — grounded in how your business really works.</p>
            </div>
            <div class="card">
                <h3>Platform-powered</h3>
                <p>The AiServe Business Operating System runs the AI agents, actions, knowledge, and dashboards day to day.</p>
            </div>
            <div class="card">
                <h3>Built to scale</h3>
                <p>Start with one workflow or channel, then extend across departments, outlets, and business functions over time.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="card" style="text-align:center;padding:40px 24px;">
            <h2 style="margin-top:0;">Ready to start your AI transformation?</h2>
            <p style="max-width:640px;margin:0 auto 20px;">
                Whether you begin with process mapping or AI customer service, we will help you plan a practical
                path for your business.
            </p>
            <a href="/contact.php" class="btn-primary">Book a discovery conversation</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/inc/public_footer.php'; ?>
