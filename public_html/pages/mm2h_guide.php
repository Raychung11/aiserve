<?php
$page_title = 'MM2H Application Guide & FAQ — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';

// Load all active FAQs grouped by category
$pdo = db();
$faqs_raw = [];
try {
    $faqs_raw = $pdo->query(
        "SELECT * FROM mm2h_faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
    )->fetchAll();
} catch (PDOException $e) { /* table may not exist yet */ }

// Group by category
$categories = [];
foreach ($faqs_raw as $row) {
    $categories[$row['category']][] = $row;
}

// Category icon map
$cat_icons = [
    'Visa Issuance & Extensions'  => ['bi-passport-fill',   '#C8A03C'],
    'Permissions & Domestic Helpers' => ['bi-person-check-fill', '#1B4965'],
    'Termination & Changes'       => ['bi-x-circle-fill',   '#dc3545'],
    'Fixed Deposit Withdrawals'   => ['bi-bank2',           '#198754'],
];

// Fallback static data when DB table doesn't exist yet
if (empty($categories)) {
    $categories = [
        'Visa Issuance & Extensions' => [
            ['question' => 'How do I obtain the initial Social Visit Pass (MM2H Principal)?', 'answer' => 'After your MM2H conditional approval letter is issued by Tourism Malaysia, you must enter Malaysia and apply to the Immigration Department (IMI) for your Social Visit Pass.', 'processing_time' => '1 business day', 'required_documents' => 'Conditional Approval Letter; Valid passport; IMI application form; Photographs; Fixed Deposit confirmation; Medical insurance', 'fees' => 'RM 90 per year'],
            ['question' => 'How do I obtain a Social Visit Pass for my spouse or children?', 'answer' => 'Dependants (spouse and unmarried children under 21) may apply for a Social Visit Pass after the principal holder receives their pass.', 'processing_time' => '30 working days', 'required_documents' => 'Principal MM2H pass copy; Dependant passport; Marriage/birth certificate; Medical insurance', 'fees' => 'RM 90 per year per dependant'],
            ['question' => 'Can I bring my parents to Malaysia under MM2H?', 'answer' => 'Yes. Parents may apply for a Social Visit Pass aligned to the principal\'s pass validity. A financial guarantee from the principal holder is required.', 'processing_time' => '30 working days', 'required_documents' => 'Principal MM2H pass copy; Parent passport; Relationship proof; Financial guarantee letter; Medical insurance', 'fees' => 'RM 90 per year'],
            ['question' => 'How do I renew my MM2H Social Visit Pass?', 'answer' => 'Before expiry, submit a renewal application to IMI. Ensure your Fixed Deposit is active, insurance is valid, and you have met the 90-day minimum annual stay.', 'processing_time' => '30 working days', 'required_documents' => 'Current pass; Passport; FD bank statement; Insurance renewal; Proof of 90-day stay', 'fees' => 'RM 90 per year'],
            ['question' => 'What happens when I renew my passport?', 'answer' => 'Transfer your MM2H endorsement to the new passport at any IMI office. Present both old and new passports.', 'processing_time' => '1 business day', 'required_documents' => 'Old passport; New passport; IMI form; Photographs', 'fees' => 'Nominal'],
        ],
        'Permissions & Domestic Helpers' => [
            ['question' => 'Can my child study in Malaysia on a dependant pass?', 'answer' => 'Yes. Children under 18 may apply for a study permission letter from IMI to enrol in Malaysian or international schools.', 'processing_time' => '30 working days', 'required_documents' => 'Child dependant pass; School acceptance letter; Principal pass copy', 'fees' => 'Nominal'],
            ['question' => 'Can MM2H holders work part-time?', 'answer' => 'MM2H holders may apply for permission to work part-time (up to 20 hours/week) with an approved Malaysian employer.', 'processing_time' => '30 working days', 'required_documents' => 'MM2H pass; Employer offer letter; Business registration documents', 'fees' => 'Standard IMI fees'],
            ['question' => 'How do I bring a foreign domestic helper?', 'answer' => 'Principal holders may employ one foreign domestic helper. Apply to IMI with financial proof and the helper\'s documents.', 'processing_time' => '30 working days', 'required_documents' => 'MM2H pass; Helper passport; Employment contract; Medical fitness certificate; Security bond', 'fees' => 'Work permit fee + security levy'],
            ['question' => 'How do I renew my domestic helper\'s work permit?', 'answer' => 'Initiate renewal at least one month before expiry with updated insurance and medical documentation.', 'processing_time' => '30 working days', 'required_documents' => 'Current permit; Helper passport; Updated medical certificate; Renewed insurance', 'fees' => 'Renewal permit fee + levy'],
        ],
        'Termination & Changes' => [
            ['question' => 'How do I voluntarily terminate my MM2H pass while in Malaysia?', 'answer' => 'Submit a termination letter and return your pass documents to IMI. After approval you may withdraw your Fixed Deposit in full.', 'processing_time' => '3 business days', 'required_documents' => 'Termination letter; All MM2H passes; Passport; FD account details', 'fees' => 'None'],
            ['question' => 'How do I terminate my MM2H pass from abroad?', 'answer' => 'Initiate at the nearest Malaysian Embassy. The embassy forwards to IMI in Kuala Lumpur.', 'processing_time' => '3 business days after IMI receipt', 'required_documents' => 'Notarised termination letter; Pass copies; Passport copies; FD details', 'fees' => 'Embassy fee may apply'],
            ['question' => 'What happens if the principal holder passes away in Malaysia?', 'answer' => 'Dependants must notify IMI within 7 days. IMI processes cancellation of all passes. The Fixed Deposit is released to the estate.', 'processing_time' => '3 business days', 'required_documents' => 'Death certificate; All passes; Dependant passports; Letter from next-of-kin', 'fees' => 'None'],
            ['question' => 'Can I change the principal MM2H holder?', 'answer' => 'In certain circumstances a dependant spouse may apply to become the new principal, subject to re-proving financial eligibility.', 'processing_time' => '30 working days', 'required_documents' => 'Application letter; All passes; New principal financial statements; FD proof; Legal documents', 'fees' => 'Standard IMI fee'],
        ],
        'Fixed Deposit Withdrawals' => [
            ['question' => 'When can I make a partial FD withdrawal?', 'answer' => 'From Year 2, one partial withdrawal per year is allowed for: residential property purchase, children\'s education, medical expenses, or annual interest. Minimum balance must be maintained (RM 150,000 under 50; RM 50,000 aged 50+).', 'processing_time' => 'Bank processing time', 'required_documents' => 'MM2H pass; FD withdrawal form; Supporting purpose documents; Tourism Malaysia approval', 'fees' => 'Bank fee'],
            ['question' => 'How much can I withdraw for a residential property purchase?', 'answer' => 'You may withdraw to finance a Malaysian residential property purchase. Present the Sale and Purchase Agreement to your bank.', 'processing_time' => 'Bank processing time', 'required_documents' => 'MM2H pass; Signed SPA; Bank withdrawal form', 'fees' => 'Bank charges'],
            ['question' => 'Can I withdraw for education expenses?', 'answer' => 'Yes. Tuition fees at recognised Malaysian institutions can be funded through a partial FD withdrawal.', 'processing_time' => 'Bank processing time', 'required_documents' => 'MM2H pass; Tuition fee invoice; Child dependant pass; Bank withdrawal form', 'fees' => 'Bank charges'],
            ['question' => 'Can I use my FD for medical expenses?', 'answer' => 'Medical expenses incurred in Malaysia can be funded through a partial FD withdrawal with the original hospital invoice.', 'processing_time' => 'Bank processing time', 'required_documents' => 'MM2H pass; Original medical invoice; Bank withdrawal form', 'fees' => 'Bank charges'],
            ['question' => 'Can I withdraw the annual interest on my FD?', 'answer' => 'Yes. Annual interest or profit may be withdrawn or transferred at any time without affecting the principal balance requirement.', 'processing_time' => 'Immediate bank transaction', 'required_documents' => 'Standard bank instruction form; MM2H pass copy', 'fees' => 'Standard transfer rates'],
        ],
    ];
}
?>

<!-- Hero -->
<section class="section section-dark" style="padding:4rem 0;">
  <div class="container">
    <div class="section-label">Official IMI Procedures</div>
    <h1 class="hero-title" style="font-size:clamp(1.8rem,4vw,3rem);">MM2H Application Guide</h1>
    <p class="hero-subtitle on-dark">Procedures, processing times, and required documents — sourced from the Immigration Department of Malaysia (IMI).</p>

    <!-- Category quick-jump pills -->
    <div class="d-flex flex-wrap gap-2 mt-4">
      <?php foreach (array_keys($categories) as $i => $cat): ?>
      <a href="#cat-<?= $i ?>" class="btn btn-outline-gold btn-sm">
        <i class="bi <?= $cat_icons[$cat][0] ?? 'bi-info-circle' ?> me-1"></i><?= h($cat) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Disclaimer banner -->
<div class="container mt-4">
  <div class="alert alert-warning d-flex gap-3 align-items-start" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
    <div>
      <strong>Important Notice:</strong> This guide is for general information only. Processing times and requirements may change. Always verify with the <strong>Immigration Department of Malaysia (IMI)</strong> or a licensed MM2H agent before submitting any application.
    </div>
  </div>
</div>

<!-- FAQ Categories -->
<?php foreach ($categories as $i => $cat_name): ?>
<?php
// $cat_name holds the category label (key), $i is the index
// Rewrite loop properly
?>
<?php endforeach; ?>

<?php $cat_index = 0; foreach ($categories as $cat_label => $items): ?>
<section class="section <?= $cat_index % 2 !== 0 ? 'section-alt' : '' ?>" id="cat-<?= $cat_index ?>">
  <div class="container">

    <!-- Category header -->
    <?php $icon_data = $cat_icons[$cat_label] ?? ['bi-info-circle', '#C8A03C']; ?>
    <div class="d-flex align-items-center gap-3 mb-4">
      <div class="stat-icon flex-shrink-0"
           style="background:<?= $icon_data[1] ?>18;color:<?= $icon_data[1] ?>;width:56px;height:56px;font-size:1.5rem;">
        <i class="bi <?= $icon_data[0] ?>"></i>
      </div>
      <div>
        <div class="section-label mb-0"><?= h($cat_label) ?></div>
        <h2 class="section-title mb-0" style="font-size:1.6rem;"><?= h($cat_label) ?></h2>
      </div>
    </div>

    <!-- Accordion -->
    <div class="accordion" id="accordion-cat-<?= $cat_index ?>">
      <?php foreach ($items as $j => $faq): ?>
      <?php $faq_id = 'faq-' . $cat_index . '-' . $j; ?>
      <div class="accordion-item border mb-2 rounded-mm2h overflow-hidden">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $j > 0 ? 'collapsed' : '' ?> fw-semibold"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#<?= $faq_id ?>"
                  aria-expanded="<?= $j === 0 ? 'true' : 'false' ?>">
            <?= h($faq['question']) ?>
          </button>
        </h2>
        <div id="<?= $faq_id ?>"
             class="accordion-collapse collapse <?= $j === 0 ? 'show' : '' ?>"
             data-bs-parent="#accordion-cat-<?= $cat_index ?>">
          <div class="accordion-body">
            <p class="mb-3"><?= nl2br(h($faq['answer'])) ?></p>

            <div class="row g-3">
              <?php if (!empty($faq['processing_time'])): ?>
              <div class="col-md-4">
                <div class="d-flex align-items-start gap-2">
                  <i class="bi bi-clock-fill text-gold mt-1 flex-shrink-0"></i>
                  <div>
                    <div class="fw-bold small text-uppercase text-muted mb-1">Processing Time</div>
                    <div class="small"><?= h($faq['processing_time']) ?></div>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <?php if (!empty($faq['fees'])): ?>
              <div class="col-md-4">
                <div class="d-flex align-items-start gap-2">
                  <i class="bi bi-cash-stack text-gold mt-1 flex-shrink-0"></i>
                  <div>
                    <div class="fw-bold small text-uppercase text-muted mb-1">Fees</div>
                    <div class="small"><?= h($faq['fees']) ?></div>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($faq['required_documents'])): ?>
            <div class="mt-3">
              <div class="fw-bold small text-uppercase text-muted mb-2">
                <i class="bi bi-paperclip me-1"></i>Required Documents
              </div>
              <ul class="list-unstyled mb-0">
                <?php
                $docs = array_filter(array_map('trim', explode(';', $faq['required_documents'])));
                foreach ($docs as $doc): ?>
                <li class="small mb-1">
                  <i class="bi bi-check-circle-fill me-2 text-gold"></i><?= h($doc) ?>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php $cat_index++; endforeach; ?>

<!-- CTA -->
<section class="section section-dark">
  <div class="container text-center">
    <h2 class="section-title on-dark">Ready to start your MM2H journey?</h2>
    <p class="hero-subtitle on-dark mb-4">Our concierge platform guides you through every step — from eligibility check to visa approval.</p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="<?= APP_URL ?>/register" class="btn btn-gold btn-lg px-5"><?= t('btn_get_started') ?></a>
      <a href="<?= APP_URL ?>/contact"  class="btn btn-outline-gold btn-lg px-5"><?= t('nav_contact') ?></a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
