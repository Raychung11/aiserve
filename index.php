<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/content_blocks.php';
require_once __DIR__ . '/inc/functions.php';

$pageTitle = 'AiServe.my | The AI Business Operating System';
$pageDescription = 'AiServe.my helps businesses move beyond static software with AI-powered systems for service, workflow, reporting, and intelligence.';

$heroTitle = get_setting('hero_banner_title', 'The AI Business Layer for Modern Companies');
$heroSubtitle = get_setting('hero_banner_subtitle', 'AiServe.my helps businesses move beyond static systems with AI-powered workflow, service, reporting, and intelligence.');
$heroCtaText = get_setting('hero_banner_cta_text', 'Get Started');
$heroCtaLink = get_setting('hero_banner_cta_link', '/contact.php');
$heroBgUrl = get_setting('hero_banner_bg_url', '');

$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;
$error   = isset($_GET['error']) ? trim((string)$_GET['error']) : '';

require_once __DIR__ . '/inc/public_header.php';
?>

<section class="hero-section" style="
    padding:72px 0 48px;
    background:
        linear-gradient(135deg, rgba(109,40,217,.92), rgba(139,92,246,.82))
        <?php if ($heroBgUrl !== ''): ?>, url('<?= h($heroBgUrl) ?>')<?php endif; ?>;
    background-size:cover;
    background-position:center;
    color:#fff;
">
    <div class="container">
        <div style="max-width:760px;">
            <div style="
                display:inline-block;
                padding:8px 12px;
                border-radius:999px;
                background:rgba(255,255,255,.14);
                font-size:13px;
                font-weight:700;
                margin-bottom:18px;
            ">
                AI + BI for Modern Business
            </div>

            <h1 style="font-size:clamp(34px, 6vw, 60px);line-height:1.06;margin:0 0 18px 0;">
                <?= h($heroTitle) ?>
            </h1>

            <p style="font-size:18px;line-height:1.7;max-width:680px;color:rgba(255,255,255,.92);margin:0 0 24px 0;">
                <?= h($heroSubtitle) ?>
            </p>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="<?= h($heroCtaLink) ?>" class="btn-primary"><?= h($heroCtaText) ?></a>
                <a href="/about.php" style="
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    min-height:46px;
                    padding:0 18px;
                    border-radius:999px;
                    color:#fff;
                    font-weight:700;
                    border:1px solid rgba(255,255,255,.35);
                    background:rgba(255,255,255,.08);
                ">Learn More</a>
            </div>
        </div>
    </div>
</section>

<section class="section" id="product">
    <div class="container">
        <div class="section-head">
            <div class="label">What is Ai-BOS</div>
            <h2>One AI-powered business layer across service, workflow, and intelligence</h2>
            <p>
                Ai-BOS is the proprietary business operating layer by AiServe.my.
                It connects customer interaction, internal workflow, business reporting,
                and decision support into one practical AI-enabled system.
            </p>
        </div>

        <div class="grid-3">
            <div class="card">
                <div class="icon">01</div>
                <h3>AI Customer Service</h3>
                <p>Answer inquiries, guide customers, qualify leads, and improve service consistency.</p>
            </div>
            <div class="card">
                <div class="icon">02</div>
                <h3>AI Workflow Engine</h3>
                <p>Turn business processes into guided action flows instead of manual follow-up chains.</p>
            </div>
            <div class="card">
                <div class="icon">03</div>
                <h3>AI + BI Reporting</h3>
                <p>Capture operational data and convert it into useful management visibility.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="capabilities">
    <div class="container">
        <div class="section-head">
            <div class="label">Capabilities</div>
            <h2>The intelligence layer for execution</h2>
            <p>
                AiServe.my is built for companies that want AI connected to real use cases, not just surface-level tools.
            </p>
        </div>

        <div class="grid-4">
            <div class="card">
                <div class="icon">A</div>
                <h3>Internal AI Assistant</h3>
                <p>Support SOP access, staff guidance, and internal questions.</p>
            </div>
            <div class="card">
                <div class="icon">B</div>
                <h3>Lead Qualification</h3>
                <p>Improve sales follow-up speed and lead handling quality.</p>
            </div>
            <div class="card">
                <div class="icon">C</div>
                <h3>Branch Reporting</h3>
                <p>Collect updates from outlets, kiosks, or teams in structured form.</p>
            </div>
            <div class="card">
                <div class="icon">D</div>
                <h3>Knowledge Layer</h3>
                <p>Make company knowledge usable at the point of action.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="industries">
    <div class="container">
        <div class="section-head">
            <div class="label">Industries</div>
            <h2>Built for real business use</h2>
            <p>
                Designed for SMEs, retail, furniture, F&amp;B, education, healthcare support, travel, and multi-branch operations.
            </p>
        </div>

        <div class="grid-3">
            <div class="card">
                <h3>Retail &amp; Consumer</h3>
                <p>AI inquiry flow, sales support, branch reporting, and customer operations.</p>
            </div>
            <div class="card">
                <h3>Furniture &amp; Distribution</h3>
                <p>Support product inquiry, agent workflows, order follow-up, and sales enablement.</p>
            </div>
            <div class="card">
                <h3>F&amp;B &amp; Kiosk Operations</h3>
                <p>Daily stock, sales, cash, wastage, and issue escalation using AI-guided reporting.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="subscribe">
    <div class="container subscribe-wrap">
        <div>
            <div class="section-head">
                <div class="label">Subscribe</div>
                <h2>Start your Ai-BOS journey</h2>
                <p>
                    Tell us about your company and what you want to improve.
                    Our team will review your use case and propose the right subscription path.
                </p>
            </div>

            <div class="card">
                <h3>Why subscribe</h3>
                <p>
                    This is not just software implementation. It is long-term business enablement using AI,
                    workflow automation, reporting, and operational intelligence.
                </p>
            </div>
        </div>

        <div class="form-box">
            <?php if ($success === 1): ?>
                <div class="alert alert-success">Thank you. Your inquiry has been submitted successfully.</div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="post" action="/contact_submit.php">
                <div class="form-grid">
                    <div class="field">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="field">
                        <label for="company_name">Company Name *</label>
                        <input type="text" id="company_name" name="company_name" required>
                    </div>

                    <div class="field">
                        <label for="email">Business Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="field">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone">
                    </div>

                    <div class="field">
                        <label for="interest">Primary Interest *</label>
                        <select id="interest" name="interest" required>
                            <option value="">Select one</option>
                            <option value="AI Customer Service">AI Customer Service</option>
                            <option value="AI Internal Assistant">AI Internal Assistant</option>
                            <option value="AI Workflow Automation">AI Workflow Automation</option>
                            <option value="AI + BI Reporting">AI + BI Reporting</option>
                            <option value="Full Ai-BOS Subscription">Full Ai-BOS Subscription</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="company_size">Company Size</label>
                        <select id="company_size" name="company_size">
                            <option value="">Select one</option>
                            <option value="1-10">1-10</option>
                            <option value="11-50">11-50</option>
                            <option value="51-200">51-200</option>
                            <option value="201+">201+</option>
                        </select>
                    </div>

                    <div class="field full">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="Tell us what you want to improve in your business"></textarea>
                    </div>

                    <div class="field full">
                        <button type="submit" class="btn btn-primary" style="width:100%">Submit Inquiry</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/inc/public_footer.php'; ?>