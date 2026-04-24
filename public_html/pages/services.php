<?php
$page_title = t('services_title') . ' — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section section-dark" style="padding:4rem 0;">
  <div class="container">
    <div class="section-label">What We Offer</div>
    <h1 class="hero-title" style="font-size:clamp(1.8rem,4vw,3rem);"><?= t('services_title') ?></h1>
    <p class="hero-subtitle">A complete concierge system from visa to settlement — and beyond.</p>
  </div>
</section>

<?php
$services = [
  [
    'bi-passport-fill', '#C8A03C', t('svc_visa'), 'MM2H Visa Guidance',
    'We work with licensed MM2H agents to guide you through the application process. Our platform tracks every stage, manages documents, and ensures nothing is missed.',
    ['Eligibility assessment', 'Agent matching', 'Document checklist', 'Application tracking', 'Status updates'],
  ],
  [
    'bi-buildings', '#1B4965', t('svc_property'), 'Property Matching',
    'Our curated property database features MM2H-suitable homes across Kuala Lumpur, Penang, Johor Bahru, and beyond — matched to your budget and lifestyle.',
    ['Residential & commercial', 'Developer introductions', 'ROI analysis', 'Property tours', 'Agent introductions'],
  ],
  [
    'bi-bank2', '#198754', t('svc_banking'), 'Banking Support',
    'Opening a Malaysian bank account and setting up the mandatory Fixed Deposit is a critical step in MM2H. We connect you with relationship managers at top banks.',
    ['Account opening support', 'Fixed deposit setup', 'Bank appointment booking', 'Document preparation', 'Status tracking'],
  ],
  [
    'bi-people-fill', '#6366f1', t('svc_business'), 'Business Networking',
    'Connect with Malaysian entrepreneurs, industry leaders, and investment groups across property, fintech, F&B, healthcare, manufacturing, and more.',
    ['Sector matching', 'Investor introductions', 'JV opportunities', 'Business registration guidance', 'Community events'],
  ],
  [
    'bi-graph-up-arrow', '#f59e0b', t('svc_investment'), 'Investment Concierge',
    'Whether you\'re targeting real estate, equities, or private business stakes, our investment concierge connects you with the right deals and advisors in Malaysia.',
    ['Investment matching', 'Deal introductions', 'Legal/accounting referrals', 'Market insights', 'Portfolio support'],
  ],
  [
    'bi-diagram-3-fill', '#dc3545', t('svc_partner'), 'Partner Programme',
    'Agents, consultants, and businesses can join our partner programme, refer clients, and earn competitive commissions of 15–25% on successful deals.',
    ['15–25% commission', 'Real-time lead tracking', 'Partner dashboard', 'Marketing materials', 'Dedicated support'],
  ],
];
foreach ($services as $i => [$icon, $color, $tag, $title, $desc, $features]):
$alt = $i % 2 !== 0;
?>
<section class="section <?= $alt ? 'section-alt' : '' ?>">
  <div class="container">
    <div class="row g-5 align-items-center <?= $alt ? 'flex-lg-row-reverse' : '' ?>">
      <div class="col-lg-6">
        <div class="section-label"><?= h($tag) ?></div>
        <h2 class="section-title"><?= h($title) ?></h2>
        <p class="text-muted mb-4"><?= h($desc) ?></p>
        <ul class="list-unstyled">
          <?php foreach ($features as $f): ?>
          <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color:<?= $color ?>"></i><?= h($f) ?></li>
          <?php endforeach; ?>
        </ul>
        <a href="<?= APP_URL ?>/register" class="btn btn-gold mt-3"><?= t('btn_get_started') ?></a>
      </div>
      <div class="col-lg-6 text-center">
        <div style="width:160px;height:160px;background:<?= $color ?>18;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;">
          <i class="bi <?= $icon ?>" style="font-size:4rem;color:<?= $color ?>"></i>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
