<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ActivityLog.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::start();
if (Auth::check()) {
    $dest = Auth::isOwner() ? '/owner-portal' : '/dashboard';
    header('Location: ' . APP_URL . $dest); exit;
}

// Featured rooms from CoLive (same database)
try {
    $featRooms = Database::fetchAll("
        SELECT r.id, r.room_no, r.room_type, r.base_rent, r.capacity,
               r.has_attached_bath, r.deposit_months,
               u.unit_no, b.name AS building_name, b.city, c.brand_color
        FROM rooms r
        JOIN units u ON u.id = r.unit_id
        JOIN buildings b ON b.id = u.building_id
        JOIN companies c ON c.id = r.company_id AND c.status IN ('trial','active')
        LEFT JOIN tenancies t ON t.room_id = r.id AND t.status = 'active'
        WHERE r.is_active = 1 AND t.id IS NULL
        ORDER BY r.base_rent ASC
        LIMIT 6
    ");
} catch (Throwable $e) {
    $featRooms = [];
}

$roomTypeLabels = [
    'single'=>'Single','twin'=>'Twin','master'=>'Master',
    'studio'=>'Studio','common'=>'Common Room',
];

$pageTitle = 'Roomee — Malaysia\'s Smart Property Management Platform';
include __DIR__ . '/../includes/landing_header.php';
?>

<style>
/* ── Global landing styles ── */
.lp-section   { padding:5rem 0; }
.lp-section-sm{ padding:3.5rem 0; }
.section-label { font-size:.72rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#6366f1; margin-bottom:.6rem; }
.section-title { font-size:2.2rem; font-weight:800; line-height:1.25; color:#0f172a; }
.section-sub   { font-size:1.05rem; color:#64748b; line-height:1.7; max-width:560px; }

/* ── Hero ── */
.hero { background:linear-gradient(160deg,#f8f7ff 0%,#eef2ff 40%,#f0fdf4 100%); padding:6rem 0 5rem; overflow:hidden; }
.hero-title { font-size:clamp(2.2rem,5vw,3.4rem); font-weight:900; line-height:1.15; color:#0f172a; }
.hero-title span { color:#6366f1; }
.hero-badge { display:inline-flex; align-items:center; gap:.5rem; background:#ede9fe; color:#5b21b6;
              font-size:.75rem; font-weight:700; padding:.35rem .9rem; border-radius:20px; margin-bottom:1.25rem; }
.hero-cta-primary { background:#6366f1; color:#fff; font-weight:700; padding:.8rem 2rem;
                    border-radius:10px; font-size:.95rem; text-decoration:none; border:none;
                    transition:background .15s,transform .15s; display:inline-flex; align-items:center; gap:.5rem; }
.hero-cta-primary:hover { background:#4f46e5; color:#fff; transform:translateY(-1px); }
.hero-cta-secondary { color:#475569; font-weight:600; font-size:.9rem; text-decoration:none;
                      display:inline-flex; align-items:center; gap:.4rem; transition:color .15s; }
.hero-cta-secondary:hover { color:#6366f1; }

/* Dashboard mockup */
.mockup-wrap { position:relative; }
.mockup-shell { background:#fff; border-radius:16px; border:1px solid #e2e8f0;
                box-shadow:0 20px 60px rgba(99,102,241,.15); overflow:hidden; }
.mockup-bar   { background:#0f172a; padding:.5rem .75rem; display:flex; align-items:center; gap:.35rem; }
.mockup-dot   { width:10px; height:10px; border-radius:50%; }
.mockup-content { padding:1.25rem; }
.stat-mini    { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:.75rem; text-align:center; }
.stat-mini-val{ font-size:1.2rem; font-weight:800; color:#0f172a; }
.stat-mini-lbl{ font-size:.65rem; color:#94a3b8; margin-top:1px; }

/* ── Stats bar ── */
.stats-bar { background:#0f172a; padding:2.5rem 0; }
.stat-item  { text-align:center; }
.stat-num   { font-size:2rem; font-weight:800; color:#fff; }
.stat-txt   { font-size:.8rem; color:#64748b; margin-top:.2rem; }

/* ── Feature cards ── */
.feature-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:1.75rem;
                transition:box-shadow .2s,transform .2s; height:100%; }
.feature-card:hover { box-shadow:0 8px 32px rgba(99,102,241,.12); transform:translateY(-3px); }
.feature-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center;
                justify-content:center; font-size:1.3rem; margin-bottom:1rem; }

/* ── How it works ── */
.step-num { width:40px; height:40px; border-radius:50%; background:#6366f1; color:#fff;
            font-weight:800; font-size:.95rem; display:flex; align-items:center; justify-content:center;
            flex-shrink:0; }
.step-connector { width:2px; background:#e2e8f0; margin:0 auto; height:40px; }

/* ── Pricing ── */
.price-card { border:2px solid #e2e8f0; border-radius:16px; padding:2rem; background:#fff;
              transition:border-color .2s,box-shadow .2s; height:100%; }
.price-card.popular { border-color:#6366f1; box-shadow:0 8px 32px rgba(99,102,241,.15); }
.price-card .price-amt { font-size:2.4rem; font-weight:900; color:#0f172a; }
.price-card .price-per { font-size:.85rem; color:#94a3b8; }
.price-check { color:#6366f1; margin-right:.5rem; }
.popular-badge { background:#6366f1; color:#fff; font-size:.7rem; font-weight:700;
                 padding:.2rem .7rem; border-radius:20px; letter-spacing:.04em; }

/* ── Testimonials ── */
.testimonial-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:1.75rem; }
.avatar { width:44px; height:44px; border-radius:50%; background:#6366f1; color:#fff;
          font-weight:700; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; }

/* ── Room listing cards ── */
.rooms-section { background:#fff; padding:5rem 0; }
.rm-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;
           transition:box-shadow .2s,transform .2s; height:100%; display:flex; flex-direction:column; }
.rm-card:hover { box-shadow:0 8px 32px rgba(99,102,241,.13); transform:translateY(-4px); }
.rm-card-img { height:140px; display:flex; align-items:center; justify-content:center; position:relative; }
.rm-type-pill { position:absolute; top:.65rem; left:.65rem; color:#fff; font-size:.68rem; font-weight:700;
                padding:.22rem .65rem; border-radius:20px; text-transform:capitalize; }
.rm-body { padding:1.1rem; flex:1; display:flex; flex-direction:column; }
.rm-price { font-size:1.3rem; font-weight:800; color:#0f172a; line-height:1; }
.rm-price span { font-size:.74rem; font-weight:400; color:#94a3b8; }
.rm-loc { font-size:.75rem; color:#64748b; margin:.25rem 0 .6rem; }
.rm-chips { display:flex; flex-wrap:wrap; gap:.3rem; margin-bottom:auto; }
.rm-chip { background:#f1f5f9; color:#475569; font-size:.68rem; padding:.18rem .5rem; border-radius:20px; }
.rm-chip.g { background:#f0fdf4; color:#166534; }
.rm-btn { margin-top:1rem; display:block; text-align:center; border-radius:8px; padding:.52rem;
          font-size:.83rem; font-weight:700; text-decoration:none; color:#fff;
          background:#6366f1; transition:background .15s; }
.rm-btn:hover { background:#4f46e5; color:#fff; }

/* ── Owner partnership ── */
.owner-section { background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 100%); padding:5.5rem 0; }
.owner-benefit { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1);
                 border-radius:16px; padding:1.75rem; height:100%; }
.owner-benefit-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center;
                      justify-content:center; font-size:1.4rem; margin-bottom:1.1rem; }
.owner-stat-row { display:flex; flex-wrap:wrap; gap:2rem; justify-content:center;
                  background:rgba(255,255,255,.04); border-radius:16px; padding:2rem;
                  border:1px solid rgba(255,255,255,.08); margin-bottom:3rem; }
.owner-stat { text-align:center; }
.owner-stat .num { font-size:2rem; font-weight:900; color:#a5b4fc; line-height:1; }
.owner-stat .lbl { font-size:.75rem; color:#64748b; margin-top:.3rem; }
.btn-owner-primary { background:#6366f1; color:#fff; font-weight:700; padding:.8rem 2.25rem;
                     border-radius:10px; font-size:.95rem; text-decoration:none;
                     display:inline-flex; align-items:center; gap:.5rem; transition:background .15s; }
.btn-owner-primary:hover { background:#4f46e5; color:#fff; }
.btn-owner-outline { background:transparent; color:#e2e8f0; font-weight:600; padding:.8rem 2.25rem;
                     border-radius:10px; font-size:.95rem; text-decoration:none;
                     border:1px solid rgba(255,255,255,.2); display:inline-flex; align-items:center;
                     gap:.5rem; transition:background .15s; }
.btn-owner-outline:hover { background:rgba(255,255,255,.08); color:#fff; }

/* ── CTA band ── */
.cta-band { background:linear-gradient(135deg,#6366f1 0%,#4f46e5 60%,#7c3aed 100%); padding:5rem 0; }

/* ── Responsive tweaks ── */
@media(max-width:768px){
  .hero { padding:4rem 0 3rem; }
  .section-title { font-size:1.7rem; }
  .lp-section { padding:3.5rem 0; }
}
</style>

<!-- ════════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════ -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">

      <div class="col-lg-6">
        <div class="hero-badge"><i class="bi bi-stars"></i> Powered by AI · Built for Malaysia</div>
        <h1 class="hero-title mb-3">
          Manage Every Rental.<br>
          <span>Maximise Every Ringgit.</span>
        </h1>
        <p class="section-sub mb-4">
          Roomee is the all-in-one platform for property managers handling STR, mid-term,
          sublet and corporate leases — with compliance tracking, ROI analytics and
          an owner portal, all in one place.
        </p>
        <div class="d-flex flex-wrap align-items-center gap-3">
          <a href="<?= APP_URL ?>/register" class="hero-cta-primary">
            <i class="bi bi-rocket-takeoff"></i> Start Free 14-Day Trial
          </a>
          <a href="#features" class="hero-cta-secondary">
            See what's included <i class="bi bi-arrow-down-short"></i>
          </a>
        </div>
        <div class="d-flex flex-wrap gap-3 mt-4" style="font-size:.78rem;color:#94a3b8;">
          <span><i class="bi bi-check-circle-fill me-1" style="color:#10b981;"></i>No credit card required</span>
          <span><i class="bi bi-check-circle-fill me-1" style="color:#10b981;"></i>Cancel anytime</span>
          <span><i class="bi bi-check-circle-fill me-1" style="color:#10b981;"></i>Setup in minutes</span>
        </div>
      </div>

      <!-- Dashboard mockup -->
      <div class="col-lg-6 mockup-wrap">
        <div class="mockup-shell">
          <div class="mockup-bar">
            <div class="mockup-dot" style="background:#ef4444;"></div>
            <div class="mockup-dot" style="background:#f59e0b;"></div>
            <div class="mockup-dot" style="background:#10b981;"></div>
            <span style="color:#475569;font-size:.7rem;margin-left:.5rem;">Roomee — Dashboard</span>
          </div>
          <div class="mockup-content">
            <!-- KPI row -->
            <div class="row g-2 mb-3">
              <?php
              $kpis = [
                ['RM 28,400','Monthly Revenue','#6366f1'],
                ['RM 4,200','Expenses','#ef4444'],
                ['92%','Occupancy','#10b981'],
                ['18','Properties','#f59e0b'],
              ];
              foreach($kpis as [$val,$lbl,$col]): ?>
              <div class="col-6 col-md-3">
                <div class="stat-mini">
                  <div class="stat-mini-val" style="color:<?= $col ?>;"><?= $val ?></div>
                  <div class="stat-mini-lbl"><?= $lbl ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <!-- Mini bar chart -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem;">
              <div style="font-size:.7rem;font-weight:600;color:#475569;margin-bottom:.75rem;">Revenue — Last 6 Months</div>
              <div class="d-flex align-items-end justify-content-between gap-1" style="height:80px;">
                <?php
                $bars = [
                  ['Oct','65%','#c7d2fe'],['Nov','75%','#c7d2fe'],['Dec','85%','#c7d2fe'],
                  ['Jan','70%','#c7d2fe'],['Feb','80%','#c7d2fe'],['Mar','100%','#6366f1'],
                ];
                foreach($bars as [$mo,$h,$c]): ?>
                <div class="d-flex flex-column align-items-center flex-fill gap-1">
                  <div style="background:<?= $c ?>;width:100%;height:<?= $h ?>;border-radius:4px 4px 0 0;"></div>
                  <div style="font-size:.6rem;color:#94a3b8;"><?= $mo ?></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <!-- Alert row -->
            <div class="d-flex gap-2 mt-2">
              <div style="flex:1;background:#fef9c3;border-radius:8px;padding:.5rem .75rem;font-size:.7rem;color:#92400e;">
                <i class="bi bi-exclamation-triangle me-1"></i>3 overdue payments
              </div>
              <div style="flex:1;background:#dcfce7;border-radius:8px;padding:.5rem .75rem;font-size:.7rem;color:#15803d;">
                <i class="bi bi-check-circle me-1"></i>2 leases expiring soon
              </div>
            </div>
          </div>
        </div>
        <!-- Floating badges -->
        <div style="position:absolute;top:-14px;right:-10px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:.5rem .9rem;box-shadow:0 4px 16px rgba(0,0,0,.08);font-size:.75rem;font-weight:700;color:#15803d;">
          <i class="bi bi-graph-up-arrow me-1"></i>+34% revenue
        </div>
        <div style="position:absolute;bottom:20px;left:-18px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:.5rem .9rem;box-shadow:0 4px 16px rgba(0,0,0,.08);font-size:.75rem;">
          <span style="font-weight:700;color:#6366f1;">AI</span>
          <span style="color:#64748b;"> compliance check</span>
          <i class="bi bi-shield-check ms-1" style="color:#10b981;"></i>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ── CoLive OS callout ── -->
<div style="background:linear-gradient(90deg,#7c3aed 0%,#9333ea 100%);padding:1rem 0;">
  <div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <span style="background:rgba(255,255,255,.15);border-radius:8px;padding:.35rem .7rem;font-size:.75rem;font-weight:700;color:#fff;letter-spacing:.06em;">NEW</span>
        <span style="color:#fff;font-weight:600;font-size:.95rem;">
          Running a <strong>co-living</strong> or <strong>hostel</strong>? Try <strong>CoLive OS</strong> &mdash; built for multi-room operators.
        </span>
      </div>
      <a href="/colive/" style="background:#fff;color:#7c3aed;font-weight:700;font-size:.85rem;padding:.45rem 1.25rem;border-radius:8px;text-decoration:none;white-space:nowrap;">
        Explore CoLive OS &rarr;
      </a>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════
     STATS BAR
════════════════════════════════════════════════════════ -->
<div class="stats-bar">
  <div class="container">
    <div class="row g-3 justify-content-center">
      <?php
      $stats = [
        ['500+','Properties Managed'],
        ['RM 2M+','Revenue Tracked'],
        ['98%','Uptime'],
        ['14 days','Free Trial'],
      ];
      foreach($stats as [$num,$txt]): ?>
      <div class="col-6 col-md-3 stat-item">
        <div class="stat-num"><?= $num ?></div>
        <div class="stat-txt"><?= $txt ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════
     FEATURED ROOMS
════════════════════════════════════════════════════════ -->
<section class="rooms-section" id="listings">
  <div class="container">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-5">
      <div>
        <div class="section-label">Available Now</div>
        <h2 class="section-title">Find Your Next Room</h2>
        <p class="section-sub mt-2 mb-0">Quality co-living rooms across Klang Valley — managed by verified operators on Roomee.</p>
      </div>
      <a href="/colive/listings.php"
         style="background:#6366f1;color:#fff;font-weight:700;border-radius:10px;padding:.6rem 1.5rem;font-size:.875rem;text-decoration:none;white-space:nowrap;">
        View All Rooms <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <?php if ($featRooms): ?>
    <div class="row g-3 mb-4">
      <?php foreach ($featRooms as $rm):
        $brand = !empty($rm['brand_color']) ? $rm['brand_color'] : '#6366f1';
      ?>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="rm-card">
          <div class="rm-card-img"
               style="background:linear-gradient(135deg,<?= htmlspecialchars($brand) ?>18 0%,<?= htmlspecialchars($brand) ?>40 100%);">
            <i class="bi bi-door-open-fill"
               style="font-size:2.8rem;color:<?= htmlspecialchars($brand) ?>;opacity:.55;"></i>
            <span class="rm-type-pill" style="background:<?= htmlspecialchars($brand) ?>;">
              <?= htmlspecialchars($roomTypeLabels[$rm['room_type']] ?? ucfirst($rm['room_type'])) ?>
            </span>
          </div>
          <div class="rm-body">
            <div class="rm-price">
              RM <?= number_format((float)$rm['base_rent'], 0) ?><span>/month</span>
            </div>
            <div class="rm-loc">
              <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($rm['building_name']) ?>
              <?php if ($rm['city']): ?>&bull; <?= htmlspecialchars($rm['city']) ?><?php endif; ?>
            </div>
            <div class="rm-chips">
              <span class="rm-chip"><i class="bi bi-person me-1"></i><?= (int)$rm['capacity'] ?> pax</span>
              <?php if ($rm['has_attached_bath']): ?>
              <span class="rm-chip g"><i class="bi bi-droplet me-1"></i>En-suite</span>
              <?php endif; ?>
              <?php if ((int)$rm['deposit_months'] === 0): ?>
              <span class="rm-chip g"><i class="bi bi-star me-1"></i>Zero deposit</span>
              <?php else: ?>
              <span class="rm-chip"><?= (int)$rm['deposit_months'] ?>-month deposit</span>
              <?php endif; ?>
            </div>
            <a href="/colive/listings.php" class="rm-btn">
              <i class="bi bi-send me-1"></i>Inquire Now
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center">
      <a href="/colive/listings.php"
         style="color:#6366f1;font-size:.875rem;font-weight:600;text-decoration:none;">
        See all available rooms &rarr;
      </a>
    </div>
    <?php else: ?>
    <div class="text-center py-5" style="color:#94a3b8;">
      <i class="bi bi-house-slash" style="font-size:2.5rem;display:block;margin-bottom:.75rem;"></i>
      <p style="margin:0;font-size:.9rem;">No rooms listed yet &mdash; check back soon.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════
     FEATURES
════════════════════════════════════════════════════════ -->
<section class="lp-section" id="features" style="background:#f8fafc;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Features</div>
      <h2 class="section-title">Everything you need to run your portfolio</h2>
      <p class="section-sub mx-auto mt-2">From onboarding to annual reports — every tool in one platform, no integrations required.</p>
    </div>
    <div class="row g-4">
      <?php
      $features = [
        ['bi-buildings','#ede9fe','#6366f1','Multi-Property Portfolio',
         'Manage unlimited units across different strategies — STR, mid-term, sublet, and corporate leases — with a unified dashboard.'],
        ['bi-shield-check','#dcfce7','#15803d','Compliance Engine',
         'Auto-classify each property as Green, Amber, or Red based on strategy, occupancy, and regulatory flags. Stay ahead of audits.'],
        ['bi-calculator-fill','#dbeafe','#1e40af','ROI Calculator',
         'Factor in purchase price, renovation, financing, and ongoing expenses to get a clear return on investment for every unit.'],
        ['bi-person-vcard-fill','#fce7f3','#9d174d','Owner Portal',
         'Give property owners a read-only portal to track their income, expenses, occupancy, and documents — no spreadsheets needed.'],
        ['bi-cash-coin','#fef9c3','#92400e','Rent Payment Ledger',
         'Track monthly payments, flag overdue accounts, generate payment schedules, and record receipts per tenancy with one click.'],
        ['bi-people-fill','#f0fdf4','#15803d','Renter Profiles',
         'Store full tenant details — IC, employment, emergency contact — and link them to tenancies for a complete history.'],
        ['bi-folder-fill','#ede9fe','#5b21b6','Document Management',
         'Upload contracts, invoices and photos as PDF or images. Attach to properties or renters and share securely with owners.'],
        ['bi-trophy-fill','#fff7ed','#c2410c','Agent Leaderboard',
         'Track agent performance by revenue, occupancy, and property count. Reward top performers with data-backed visibility.'],
        ['bi-bar-chart-fill','#f0fdf4','#166534','Annual Reports',
         'One-click revenue and expense reports filterable by property, strategy, and period. Export-ready for tax season.'],
      ];
      foreach($features as [$icon,$bg,$color,$title,$desc]): ?>
      <div class="col-md-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon" style="background:<?= $bg ?>;color:<?= $color ?>;"><i class="bi <?= $icon ?>"></i></div>
          <h6 class="fw-bold mb-2"><?= $title ?></h6>
          <p class="text-muted mb-0" style="font-size:.875rem;line-height:1.65;"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════
     HOW IT WORKS
════════════════════════════════════════════════════════ -->
<section class="lp-section" id="how">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <div class="section-label">How It Works</div>
        <h2 class="section-title mb-3">Up and running in minutes</h2>
        <p class="section-sub mb-4">No onboarding calls, no consultants. Create your account, add properties, and start tracking today.</p>
        <a href="<?= APP_URL ?>/register" class="hero-cta-primary" style="display:inline-flex;">
          <i class="bi bi-play-circle"></i> Get Started Free
        </a>
      </div>
      <div class="col-lg-7">
        <?php
        $steps = [
          ['1','Create your account','Sign up in under 2 minutes. No credit card, no setup fees — your 14-day trial starts immediately.','#ede9fe','#6366f1'],
          ['2','Add your properties','Register each unit with its strategy mode, compliance details, assigned agent, and owner information.','#dbeafe','#1e40af'],
          ['3','Invite owners &amp; agents','Create read-only owner portal accounts and link agents to their managed properties.','#dcfce7','#15803d'],
          ['4','Track, collect &amp; report','Log rent payments, upload documents, monitor compliance, and pull reports whenever you need them.','#fff7ed','#c2410c'],
        ];
        foreach($steps as $i => [$num,$title,$desc,$bg,$col]): ?>
        <div class="d-flex gap-3 mb-<?= $i < count($steps)-1 ? '0' : '0' ?>">
          <div class="d-flex flex-column align-items-center">
            <div class="step-num" style="background:<?= $col ?>;"><?= $num ?></div>
            <?php if($i < count($steps)-1): ?>
            <div class="step-connector"></div>
            <?php endif; ?>
          </div>
          <div class="pb-<?= $i < count($steps)-1 ? '4' : '0' ?>">
            <h6 class="fw-bold mb-1"><?= $title ?></h6>
            <p class="text-muted mb-0" style="font-size:.875rem;line-height:1.65;"><?= $desc ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════
     STRATEGY MODES
════════════════════════════════════════════════════════ -->
<section class="lp-section-sm" style="background:#f8fafc;">
  <div class="container">
    <div class="text-center mb-4">
      <div class="section-label">Strategy Modes</div>
      <h2 class="section-title">One platform. Every rental type.</h2>
    </div>
    <div class="row g-3 justify-content-center">
      <?php
      $modes = [
        ['STR','Short-Term Rental','Airbnb, Agoda, booking.com — track nightly occupancy and platform revenue.','#dbeafe','#1e40af'],
        ['Mid-Term','30–90 Day Stays','Corporate housing, relocation — balance flexibility with steady income.','#ede9fe','#5b21b6'],
        ['Sublet','Room-by-Room','Maximise yield by subletting individual rooms in larger units.','#fce7f3','#9d174d'],
        ['Corporate','Long-Term Lease','Stable tenancy with full compliance tracking and owner reporting.','#dcfce7','#15803d'],
      ];
      foreach($modes as [$badge,$title,$desc,$bg,$col]): ?>
      <div class="col-sm-6 col-lg-3">
        <div class="text-center p-4" style="background:<?= $bg ?>;border-radius:16px;height:100%;">
          <div style="font-size:.75rem;font-weight:800;color:<?= $col ?>;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.6rem;"><?= $badge ?></div>
          <h6 class="fw-bold mb-2"><?= $title ?></h6>
          <p style="font-size:.8rem;color:#64748b;margin:0;line-height:1.6;"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════
     PRICING
════════════════════════════════════════════════════════ -->
<section class="lp-section" id="pricing">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Pricing</div>
      <h2 class="section-title">Simple, transparent pricing</h2>
      <p class="section-sub mx-auto mt-2">Every plan includes a 14-day free trial. Upgrade, downgrade, or cancel at any time.</p>
    </div>
    <div class="row g-4 justify-content-center">
      <?php foreach(PLAN_LIMITS as $key => $p):
        $popular = $key === 'growth';
        $features = [
          'starter'    => ['Up to 5 properties','2 agents','Rent payment tracking','Owner portal','Document uploads','Compliance engine','ROI calculator'],
          'growth'     => ['Up to 20 properties','10 agents','Everything in Starter','Annual reports','Agent leaderboard','Priority email support'],
          'enterprise' => ['Unlimited properties','Unlimited agents','Everything in Growth','Custom branding','Dedicated support','SLA guarantee'],
        ];
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="price-card <?= $popular ? 'popular' : '' ?>">
          <?php if($popular): ?>
          <div class="mb-3"><span class="popular-badge">Most Popular</span></div>
          <?php endif; ?>
          <div class="fw-bold mb-1" style="font-size:1rem;"><?= ucfirst($key) ?></div>
          <div class="d-flex align-items-baseline gap-1 mb-1">
            <span class="price-amt">RM <?= number_format($p['price_monthly']) ?></span>
            <span class="price-per">/month</span>
          </div>
          <div style="font-size:.75rem;color:#94a3b8;margin-bottom:1.25rem;">
            or RM <?= number_format($p['price_annual']) ?>/year — save <?= round((1-$p['price_annual']/($p['price_monthly']*12))*100) ?>%
          </div>
          <hr style="border-color:#f1f5f9;">
          <ul class="list-unstyled mb-4 mt-3">
            <?php foreach($features[$key] as $feat): ?>
            <li class="mb-2" style="font-size:.875rem;color:#475569;">
              <i class="bi bi-check-lg price-check"></i><?= $feat ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= APP_URL ?>/register?plan=<?= $key ?>"
             class="btn w-100 py-2 fw-600"
             style="<?= $popular ? 'background:#6366f1;color:#fff;border:none;' : 'background:#f8fafc;color:#0f172a;border:1px solid #e2e8f0;' ?> border-radius:10px;font-weight:600;font-size:.9rem;">
            Start Free Trial
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="text-center text-muted mt-4 mb-0" style="font-size:.8rem;">
      All plans include a 14-day free trial. No credit card required. Prices in Malaysian Ringgit (RM).
    </p>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════
     TESTIMONIALS
════════════════════════════════════════════════════════ -->
<section class="lp-section-sm" style="background:#f8fafc;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Testimonials</div>
      <h2 class="section-title">Trusted by property managers across Malaysia</h2>
    </div>
    <div class="row g-4">
      <?php
      $testimonials = [
        ['SR','Sarah R.','Property Manager, KL','Growth',
         '"Roomee replaced 3 separate spreadsheets for us. The owner portal alone saved me hours of weekly reporting."'],
        ['AM','Ahmad M.','STR Operator, Johor Bahru','Starter',
         '"The compliance engine flagged an issue with one of my Airbnb units before my owner even noticed. Incredibly useful."'],
        ['LH','Lim H.','Agency Director, Penang','Enterprise',
         '"We manage 40+ units across 5 owners. The agent leaderboard keeps the team motivated and the reports keep owners happy."'],
      ];
      foreach($testimonials as [$init,$name,$role,$plan,$quote]): ?>
      <div class="col-md-4">
        <div class="testimonial-card h-100">
          <div class="d-flex gap-1 mb-3">
            <?php for($i=0;$i<5;$i++): ?>
            <i class="bi bi-star-fill" style="color:#f59e0b;font-size:.8rem;"></i>
            <?php endfor; ?>
          </div>
          <p style="font-size:.9rem;color:#475569;line-height:1.7;margin-bottom:1.25rem;"><?= $quote ?></p>
          <div class="d-flex align-items-center gap-3">
            <div class="avatar"><?= $init ?></div>
            <div>
              <div class="fw-semibold" style="font-size:.875rem;"><?= $name ?></div>
              <div style="font-size:.75rem;color:#94a3b8;"><?= $role ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════
     OWNER PARTNERSHIP
════════════════════════════════════════════════════════ -->
<section class="owner-section" id="owners">
  <div class="container">

    <!-- Header -->
    <div class="text-center mb-5">
      <div style="display:inline-flex;align-items:center;gap:.5rem;background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.35);color:#a5b4fc;border-radius:20px;padding:.3rem .9rem;font-size:.75rem;font-weight:700;margin-bottom:1rem;">
        <i class="bi bi-buildings-fill"></i> For Property Owners
      </div>
      <h2 style="font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;color:#fff;line-height:1.2;margin-bottom:.75rem;">
        Let Your Property<br><span style="color:#a5b4fc;">Work Harder for You</span>
      </h2>
      <p style="color:#94a3b8;font-size:1rem;max-width:520px;margin:0 auto;line-height:1.7;">
        Partner with Roomee-managed operators. We handle everything &mdash; tenants, billing, maintenance &mdash;
        while you earn steady passive income with full visibility.
      </p>
    </div>

    <!-- Stats -->
    <div class="owner-stat-row">
      <?php foreach ([
        ['RM 2M+','Revenue tracked on platform'],
        ['500+','Units under management'],
        ['98%','On-time payment rate'],
        ['14 days','Average time to fill a room'],
      ] as [$n,$l]): ?>
      <div class="owner-stat">
        <div class="num"><?= $n ?></div>
        <div class="lbl"><?= $l ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Benefits grid -->
    <div class="row g-4 mb-5">
      <?php
      $benefits = [
        ['bi-cash-stack','#dbeafe','#1e40af',
         'Guaranteed Monthly Reports',
         'Every payout comes with a detailed breakdown — gross rent, deductions, net income — downloadable from your owner portal anytime.'],
        ['bi-shield-check','#dcfce7','#15803d',
         'Verified Operator Network',
         'We only partner with operators who pass our compliance checks. Your property is managed by screened professionals, not random subletters.'],
        ['bi-eye-fill','#ede9fe','#5b21b6',
         'Real-Time Portal Access',
         'Log in anytime to see unit occupancy, active tenancies, maintenance status and payment history — no need to chase your manager for updates.'],
        ['bi-tools','#fff7ed','#c2410c',
         'Maintenance Handled',
         'Issues are logged, assigned and tracked inside Roomee. You get visibility without being on-call. Resolution photos attached to every ticket.'],
        ['bi-person-check-fill','#fce7f3','#9d174d',
         'Quality Tenant Matching',
         'Operators on Roomee keep full IC, employment and emergency contact records per tenant. No faceless subletting.'],
        ['bi-graph-up-arrow','#f0fdf4','#166534',
         'ROI Benchmarking',
         'See how your unit stacks up. Compare rental yield vs area average and get operator-suggested pricing strategies backed by real data.'],
      ];
      foreach ($benefits as [$icon,$bg,$col,$title,$desc]): ?>
      <div class="col-md-6 col-lg-4">
        <div class="owner-benefit">
          <div class="owner-benefit-icon" style="background:<?= $bg ?>;color:<?= $col ?>;">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h6 class="fw-bold mb-2" style="color:#e2e8f0;font-size:.95rem;"><?= $title ?></h6>
          <p style="color:#64748b;font-size:.84rem;line-height:1.65;margin:0;"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Owner CTA -->
    <div style="background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.25);border-radius:20px;padding:2.5rem;text-align:center;">
      <div style="font-size:2rem;margin-bottom:.75rem;">&#127968;</div>
      <h3 style="color:#fff;font-weight:800;font-size:1.6rem;margin-bottom:.5rem;">Ready to list your property?</h3>
      <p style="color:#94a3b8;font-size:.9rem;max-width:440px;margin:0 auto 1.75rem;line-height:1.7;">
        Connect with a Roomee-verified operator today. Fill in your details and we&rsquo;ll match you within 48 hours.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="<?= APP_URL ?>/owner-portal" class="btn-owner-primary">
          <i class="bi bi-person-vcard-fill"></i> Access Owner Portal
        </a>
        <a href="<?= APP_URL ?>/register" class="btn-owner-outline">
          <i class="bi bi-envelope"></i> Get in Touch
        </a>
      </div>
    </div>

  </div>
</section>

<!-- ════════════════════════════════════════════════════════
     CTA BAND
════════════════════════════════════════════════════════ -->
<section class="cta-band">
  <div class="container text-center">
    <div style="font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.6);margin-bottom:.75rem;">
      Start today — free for 14 days
    </div>
    <h2 style="font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;color:#fff;margin-bottom:1rem;line-height:1.2;">
      Ready to manage smarter?
    </h2>
    <p style="color:rgba(255,255,255,.75);font-size:1rem;max-width:480px;margin:0 auto 2rem;line-height:1.7;">
      Join property managers across Malaysia who use Roomee to save time, reduce errors, and grow their portfolios.
    </p>
    <div class="d-flex flex-wrap justify-content-center gap-3">
      <a href="<?= APP_URL ?>/register"
         style="background:#fff;color:#4f46e5;font-weight:700;padding:.85rem 2.25rem;border-radius:10px;text-decoration:none;font-size:.95rem;transition:opacity .15s;display:inline-flex;align-items:center;gap:.5rem;"
         onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
        <i class="bi bi-rocket-takeoff"></i> Start Free Trial
      </a>
      <a href="<?= APP_URL ?>/login"
         style="background:rgba(255,255,255,.12);color:#fff;font-weight:600;padding:.85rem 2.25rem;border-radius:10px;text-decoration:none;font-size:.95rem;border:1px solid rgba(255,255,255,.25);transition:background .15s;display:inline-flex;align-items:center;gap:.5rem;"
         onmouseover="this.style.background='rgba(255,255,255,.2)'" onmouseout="this.style.background='rgba(255,255,255,.12)'">
        <i class="bi bi-box-arrow-in-right"></i> Sign In
      </a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/landing_footer.php'; ?>
