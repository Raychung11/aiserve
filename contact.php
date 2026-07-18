<?php
$pageTitle = 'Contact | AiServe.io';
$pageDescription = 'Contact AiServe.io to discuss AI business operating system solutions.';
include __DIR__ . '/inc/public_header.php';

$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;
$error   = isset($_GET['error']) ? trim($_GET['error']) : '';
?>

<section class="hero" style="padding-bottom:20px;">
    <div class="container">
        <div class="eyebrow">Contact Us</div>
        <h1 style="max-width:900px;">Let’s discuss your AI business transformation</h1>
        <p style="max-width:820px;">
            Tell us what you want to improve in your customer service, workflow, reporting, or operational intelligence.
        </p>
    </div>
</section>

<section class="section">
    <div class="container subscribe-wrap">
        <div>
            <div class="card">
                <h3>AiServe.io</h3>
                <p>
                    The AI Business Operating System by <?= h(COMPANY_NAME) ?>.
                    Built for companies that want more than just websites, chatbots, or static software.
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
                        <textarea id="message" name="message" placeholder="Tell us your use case"></textarea>
                    </div>

                    <div class="field full">
                        <button type="submit" class="btn btn-primary" style="width:100%">Submit Inquiry</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/inc/public_footer.php'; ?>