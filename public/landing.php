<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/layout.php';

auth_start_session();
if (auth_check()) {
    $role = auth_role();
    if ($role === 'super_admin') redirect(APP_URL . '/admin');
    elseif ($role === 'merchant') redirect(APP_URL . '/merchant');
    else redirect(APP_URL . '/dashboard');
}

$price     = get_active_gold_price();
$sell_p    = get_active_sell_price();
$db        = getDB();
$campaigns = $db->query("SELECT * FROM campaigns WHERE is_active=1 ORDER BY created_at DESC LIMIT 3")->fetchAll();
$user_count= (int)$db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$monthly_rate = get_setting('deposit_monthly_rate', '0.50');

layout_head('Kasih Gold Easy — Platform Emas Digital Malaysia');
layout_header(null);
?>
<style>
/* ── Landing page custom styles ──────────────────────────────────────── */
.lp-section { max-width:1140px; margin:0 auto; padding:0 20px; }
.lp-hero { position:relative; overflow:hidden; background:linear-gradient(135deg,#1a0e00 0%,#2d1a00 40%,#1a1000 100%); padding:90px 24px 80px; text-align:center; }
.lp-hero::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(201,168,76,.18),transparent); pointer-events:none; }
.lp-hero-tag { font-size:.72rem; letter-spacing:.18em; text-transform:uppercase; color:rgba(201,168,76,.65); margin-bottom:14px; }
.lp-hero-h1 { font-size:clamp(2rem,5vw,3.4rem); font-weight:900; color:#fff; line-height:1.12; margin-bottom:16px; }
.lp-hero-h1 span { color:var(--gold-light); }
.lp-hero-sub { font-size:clamp(.9rem,2vw,1.1rem); color:rgba(255,255,255,.65); max-width:600px; margin:0 auto 32px; line-height:1.7; }
.lp-price-bar { display:inline-flex; align-items:center; gap:16px; background:rgba(255,255,255,.07); border:1px solid rgba(201,168,76,.2); border-radius:999px; padding:10px 24px; flex-wrap:wrap; justify-content:center; }
.lp-price-item { font-size:.82rem; }
.lp-price-item span { color:var(--gold-light); font-weight:800; font-size:.95rem; }
.lp-cta-row { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-bottom:32px; }

.lp-stats { background:var(--gold-dark); }
.lp-stats-inner { display:flex; justify-content:center; gap:0; flex-wrap:wrap; }
.lp-stat { text-align:center; padding:24px 40px; border-right:1px solid rgba(255,255,255,.15); }
.lp-stat:last-child { border-right:none; }
.lp-stat-num { font-size:1.9rem; font-weight:900; color:#fff; }
.lp-stat-lbl { font-size:.75rem; color:rgba(255,255,255,.6); text-transform:uppercase; letter-spacing:.1em; margin-top:2px; }

.lp-pill { display:inline-block; background:#FEF3C7; color:#92400E; border-radius:999px; padding:4px 14px; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-bottom:10px; }
.lp-section-h { font-size:clamp(1.4rem,3vw,1.9rem); font-weight:800; color:var(--kasih-dark); margin-bottom:10px; }
.lp-section-sub { color:#6B7280; font-size:.9rem; max-width:560px; margin:0 auto; line-height:1.7; }

.svc-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:18px; }
.svc-card { background:#fff; border:1px solid #F3F4F6; border-radius:16px; padding:26px 22px; position:relative; overflow:hidden; transition:box-shadow .2s,transform .15s; }
.svc-card:hover { box-shadow:0 8px 32px rgba(180,120,0,.12); transform:translateY(-2px); }
.svc-card::after { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:16px 16px 0 0; }
.svc-card.gold-accent::after  { background:linear-gradient(90deg,#C9A84C,#F0C440); }
.svc-card.green-accent::after { background:linear-gradient(90deg,#10B981,#34D399); }
.svc-card.blue-accent::after  { background:linear-gradient(90deg,#3B82F6,#60A5FA); }
.svc-card.amber-accent::after { background:linear-gradient(90deg,#F59E0B,#FCD34D); }
.svc-card.purple-accent::after{ background:linear-gradient(90deg,#8B5CF6,#A78BFA); }
.svc-card.rose-accent::after  { background:linear-gradient(90deg,#F43F5E,#FB7185); }
.svc-icon { font-size:2.4rem; margin-bottom:12px; }
.svc-badge { display:inline-block; background:#FEF3C7; color:#92400E; border-radius:999px; padding:2px 10px; font-size:.68rem; font-weight:700; margin-bottom:8px; }
.svc-badge.new { background:#DCFCE7; color:#166534; }
.svc-title { font-size:1.05rem; font-weight:800; color:var(--kasih-dark); margin-bottom:6px; }
.svc-desc { font-size:.82rem; color:#6B7280; line-height:1.65; }
.svc-cta { display:inline-flex; align-items:center; gap:4px; margin-top:14px; font-size:.8rem; font-weight:700; color:var(--gold-dark); text-decoration:none; }
.svc-cta:hover { gap:8px; }

.deposit-hero { background:linear-gradient(135deg,#1a0e00,#2d1a00); border-radius:20px; padding:50px 40px; display:grid; grid-template-columns:1fr 1fr; gap:40px; align-items:center; }
@media(max-width:680px){ .deposit-hero { grid-template-columns:1fr; padding:32px 24px; } }
.deposit-rate { background:rgba(201,168,76,.15); border:1px solid rgba(201,168,76,.3); border-radius:16px; padding:28px; text-align:center; }
.deposit-rate-num { font-size:3.5rem; font-weight:900; color:var(--gold-light); }
.deposit-rate-unit { font-size:1rem; color:rgba(255,255,255,.65); }

.step-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:0; }
.step-card { text-align:center; padding:30px 20px; position:relative; }
.step-card::after { content:''; position:absolute; top:46px; right:0; width:50%; height:2px; background:linear-gradient(90deg,#E5E7EB,transparent); }
.step-card:last-child::after { display:none; }
@media(max-width:600px){ .step-card::after { display:none; } }
.step-num { width:52px; height:52px; border-radius:50%; background:var(--gold-dark); color:#fff; font-size:1.4rem; font-weight:900; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
.step-title { font-weight:800; font-size:.95rem; color:var(--kasih-dark); margin-bottom:6px; }
.step-desc { font-size:.8rem; color:#6B7280; line-height:1.6; }

.trust-row { display:flex; gap:12px; flex-wrap:wrap; justify-content:center; }
.trust-chip { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid #E5E7EB; border-radius:999px; padding:10px 18px; font-size:.82rem; font-weight:600; color:var(--kasih-dark); }

.cmp-card { background:linear-gradient(135deg,#FFFBEB,#FFF7ED); border:1px solid #FDE68A; border-radius:14px; padding:20px; }
.cmp-tag { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#92400E; margin-bottom:6px; }
.cmp-title { font-size:1rem; font-weight:800; color:var(--kasih-dark); margin-bottom:8px; }
</style>

<!-- ══ HERO ══════════════════════════════════════════════════════════════════ -->
<section class="lp-hero">
  <div style="position:relative;z-index:1;">
    <div class="lp-hero-tag">Kasih Gold Easy × SLV Group × Kasih AP Gold</div>
    <h1 class="lp-hero-h1">Platform Emas Digital<br><span>Paling Lengkap</span> di Malaysia</h1>
    <p class="lp-hero-sub">Beli, simpan, depositkan, dan gadaikan emas dari telefon anda. Patuh Syariah, selamat berdaftar, mulai RM5.</p>
    <div class="lp-cta-row">
      <a href="<?= APP_URL ?>/register" class="btn-gold btn-lg">Mulakan Sekarang →</a>
      <a href="<?= APP_URL ?>/login" class="btn-gold-outline btn-lg" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.25);color:#fff;">Log Masuk</a>
    </div>
    <?php if ($price): ?>
    <div class="lp-price-bar">
      <div class="lp-price-item">Harga Beli: <span>RM <?= number_format((float)$price['price_per_g'],2) ?>/g</span></div>
      <?php if ($sell_p): ?>
      <div style="width:1px;height:18px;background:rgba(255,255,255,.2);"></div>
      <div class="lp-price-item">Harga Belian Balik: <span>RM <?= number_format((float)$sell_p['price_per_g'],2) ?>/g</span></div>
      <?php endif; ?>
      <div style="width:1px;height:18px;background:rgba(255,255,255,.2);"></div>
      <div class="lp-price-item">Faedah Deposit: <span><?= number_format((float)$monthly_rate,2) ?>%/bln</span></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══ STATS ════════════════════════════════════════════════════════════════ -->
<section class="lp-stats">
  <div class="lp-stats-inner">
    <div class="lp-stat"><div class="lp-stat-num"><?= number_format(max($user_count,1500)) ?>+</div><div class="lp-stat-lbl">Pengguna Berdaftar</div></div>
    <div class="lp-stat"><div class="lp-stat-num">RM10M</div><div class="lp-stat-lbl">Peti Besi Diinsuranskan</div></div>
    <div class="lp-stat"><div class="lp-stat-num">3 Tahap</div><div class="lp-stat-lbl">Komisen Rujukan</div></div>
    <div class="lp-stat"><div class="lp-stat-num">Patuh</div><div class="lp-stat-lbl">Syariah Islam</div></div>
  </div>
</section>

<!-- ══ SERVICES ══════════════════════════════════════════════════════════════ -->
<section style="padding:70px 0;background:#FAFAFA;">
  <div class="lp-section" style="text-align:center;margin-bottom:40px;">
    <div class="lp-pill">Produk Kami</div>
    <h2 class="lp-section-h">Ekosistem Emas Paling Lengkap</h2>
    <p class="lp-section-sub">Semua yang anda perlukan untuk melabur, menyimpan, dan memanfaatkan emas — dalam satu platform.</p>
  </div>
  <div class="lp-section">
    <div class="svc-grid">

      <div class="svc-card gold-accent">
        <div class="svc-icon">💛</div>
        <span class="svc-badge">Paling Popular</span>
        <div class="svc-title">Simpanan Emas Digital</div>
        <div class="svc-desc">Beli Gold Points mulai RM5. 100 pts = 1g emas. Simpan dalam wallet digital anda dengan selamat.</div>
        <a href="<?= APP_URL ?>/register" class="svc-cta">Mula simpan →</a>
      </div>

      <div class="svc-card green-accent">
        <div class="svc-icon">🏦</div>
        <span class="svc-badge new">Baru</span>
        <div class="svc-title">Deposit Emas Fizikal</div>
        <div class="svc-desc">Bawa emas fizikal ke pejabat kami. Kami simpan dengan selamat dan beri anda <strong><?= number_format((float)$monthly_rate,2) ?>% faedah setiap bulan</strong>.</div>
        <a href="<?= APP_URL ?>/register" class="svc-cta">Deposit sekarang →</a>
      </div>

      <div class="svc-card blue-accent">
        <div class="svc-icon">🕌</div>
        <span class="svc-badge">Patuh Syariah</span>
        <div class="svc-title">Ar Rahnu — Gadai Emas Islam</div>
        <div class="svc-desc">Perlukan wang tunai? Gadaikan emas digital atau emas deposit anda. Dapatkan pembiayaan sehingga 70% nilai emas tanpa menjualnya.</div>
        <a href="<?= APP_URL ?>/register" class="svc-cta">Mohon pembiayaan →</a>
      </div>

      <div class="svc-card amber-accent">
        <div class="svc-icon">🥇</div>
        <div class="svc-title">Tebus Emas Fizikal</div>
        <div class="svc-desc">Tukar Gold Points digital kepada plat emas fizikal 0.2g. Kutip di pejabat kami. Minimum 1 plat = 20 pts.</div>
        <a href="<?= APP_URL ?>/register" class="svc-cta">Tebus plat →</a>
      </div>

      <div class="svc-card purple-accent">
        <div class="svc-icon">🛍️</div>
        <div class="svc-title">Pasaran Maya</div>
        <div class="svc-desc">Tebus Gold Points untuk produk, perkhidmatan, dan hadiah dari pedagang berlesen di seluruh Malaysia.</div>
        <a href="<?= APP_URL ?>/marketplace" class="svc-cta">Terokai pasaran →</a>
      </div>

      <div class="svc-card rose-accent">
        <div class="svc-icon">📢</div>
        <div class="svc-title">Program Rujukan 3 Tahap</div>
        <div class="svc-desc">Jana komisen pasif: <strong>5%</strong> dari Tahap 1, <strong>3%</strong> Tahap 2, <strong>1%</strong> Tahap 3 — setiap kali kenalan anda membeli emas.</div>
        <a href="<?= APP_URL ?>/register" class="svc-cta">Mula jana →</a>
      </div>

    </div>
  </div>
</section>

<!-- ══ DEPOSIT HIGHLIGHT ═════════════════════════════════════════════════════ -->
<section style="padding:70px 0;">
  <div class="lp-section">
    <div class="deposit-hero">
      <div>
        <div class="lp-pill" style="background:rgba(201,168,76,.2);color:var(--gold-light);">Produk Terkini</div>
        <h2 style="font-size:clamp(1.5rem,3vw,2.2rem);font-weight:900;color:#fff;margin-bottom:14px;line-height:1.2;">Depositkan Emas Anda<br>Dapat Faedah Tetap</h2>
        <p style="color:rgba(255,255,255,.65);font-size:.9rem;line-height:1.7;margin-bottom:24px;">
          Bawa emas jongkong, syiling, atau barang kemas ke pejabat kami. Kami simpan dalam peti besi berdaftar dan bayar anda faedah setiap bulan atau setiap tahun — terus ke wallet digital atau akaun bank anda.
        </p>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:28px;">
          <?php foreach (['Emas disimpan dalam peti besi berdaftar diinsuranskan RM10 juta','Faedah bulanan atau tahunan — pilihan anda','Boleh digadaikan melalui Ar Rahnu tanpa keluarkan emas','Boleh ditebus pada bila-bila masa (1–3 hari) '] as $pt): ?>
          <div style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:rgba(255,255,255,.8);">
            <span style="color:var(--gold-light);font-size:1rem;">✦</span> <?= $pt ?>
          </div>
          <?php endforeach; ?>
        </div>
        <a href="<?= APP_URL ?>/register" class="btn-gold btn-lg">Buka Akaun Percuma →</a>
      </div>
      <div>
        <div class="deposit-rate">
          <div style="font-size:.75rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;">Kadar Faedah Semasa</div>
          <div class="deposit-rate-num"><?= number_format((float)$monthly_rate,2) ?><span style="font-size:1.5rem;">%</span></div>
          <div class="deposit-rate-unit">setiap bulan</div>
          <div style="margin:16px 0;border-top:1px solid rgba(255,255,255,.15);"></div>
          <div style="font-size:1.8rem;font-weight:900;color:var(--gold-light);"><?= number_format((float)$monthly_rate*12,2) ?>%</div>
          <div style="font-size:.82rem;color:rgba(255,255,255,.55);">setahun</div>
          <div style="margin-top:16px;font-size:.75rem;color:rgba(255,255,255,.4);">Contoh: 100g emas → <?= number_format(100*(float)$monthly_rate/100,3) ?>g faedah/bulan</div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;">
          <div style="background:rgba(255,255,255,.07);border-radius:10px;padding:14px;text-align:center;">
            <div style="font-size:1.3rem;margin-bottom:4px;">🥇</div>
            <div style="font-size:.78rem;font-weight:700;color:rgba(255,255,255,.85);">Emas Jongkong</div>
          </div>
          <div style="background:rgba(255,255,255,.07);border-radius:10px;padding:14px;text-align:center;">
            <div style="font-size:1.3rem;margin-bottom:4px;">🪙</div>
            <div style="font-size:.78rem;font-weight:700;color:rgba(255,255,255,.85);">Emas Syiling</div>
          </div>
          <div style="background:rgba(255,255,255,.07);border-radius:10px;padding:14px;text-align:center;">
            <div style="font-size:1.3rem;margin-bottom:4px;">💍</div>
            <div style="font-size:.78rem;font-weight:700;color:rgba(255,255,255,.85);">Barang Kemas</div>
          </div>
          <div style="background:rgba(255,255,255,.07);border-radius:10px;padding:14px;text-align:center;">
            <div style="font-size:1.3rem;margin-bottom:4px;">🏦</div>
            <div style="font-size:.78rem;font-weight:700;color:rgba(255,255,255,.85);">Peti Besi Selamat</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ HOW IT WORKS ══════════════════════════════════════════════════════════ -->
<section style="padding:70px 0;background:#FAFAFA;">
  <div class="lp-section" style="text-align:center;margin-bottom:40px;">
    <div class="lp-pill">Mudah & Pantas</div>
    <h2 class="lp-section-h">Mula dalam 4 Langkah</h2>
  </div>
  <div class="lp-section">
    <div class="step-row">
      <?php foreach ([
        ['1','📱','Daftar Akaun','Buka akaun percuma dalam minit. Masukkan e-mel, nama, dan kata laluan.'],
        ['2','🪪','Selesaikan eKYC','Muat naik gambar MyKad untuk pengesahan identiti. Diluluskan dalam 1–3 hari.'],
        ['3','💳','Beli atau Deposit','Beli Gold Points secara dalam talian, atau depositkan emas fizikal di pejabat kami.'],
        ['4','🎯','Urus Portfolio','Jual balik, tukar fizikal, gadai melalui Ar Rahnu, atau tebus di Pasaran Maya.'],
      ] as [$n,$icon,$title,$desc]): ?>
      <div class="step-card">
        <div class="step-num"><?= $n ?></div>
        <div style="font-size:2rem;margin-bottom:8px;"><?= $icon ?></div>
        <div class="step-title"><?= $title ?></div>
        <div class="step-desc"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ AR RAHNU HIGHLIGHT ═════════════════════════════════════════════════════ -->
<section style="padding:70px 0;">
  <div class="lp-section">
    <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:20px;padding:48px 40px;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;">
      <div>
        <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#1E40AF;background:#DBEAFE;padding:4px 12px;border-radius:999px;">Patuh Syariah</span>
        <h2 style="font-size:1.9rem;font-weight:900;color:var(--kasih-dark);margin:14px 0;">🕌 Ar Rahnu<br>Gadaikan Tanpa Jual</h2>
        <p style="color:#6B7280;font-size:.9rem;line-height:1.7;margin-bottom:20px;">
          Perlukan wang tunai segera? Jangan jual emas anda. Gadaikan sahaja dan dapatkan pembiayaan tunai sehingga <strong>70% nilai emas</strong>. Apabila dapat wang, tebus emas anda kembali.
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px;">
          <div style="background:#fff;border:1px solid #BFDBFE;border-radius:10px;padding:14px;">
            <div style="font-size:1.4rem;font-weight:900;color:#1E40AF;">70%</div>
            <div style="font-size:.78rem;color:#6B7280;">Pembiayaan maks. dari nilai emas</div>
          </div>
          <div style="background:#fff;border:1px solid #BFDBFE;border-radius:10px;padding:14px;">
            <div style="font-size:1.4rem;font-weight:900;color:#1E40AF;">3–12</div>
            <div style="font-size:.78rem;color:#6B7280;">Bulan tempoh fleksibel</div>
          </div>
          <div style="background:#fff;border:1px solid #BFDBFE;border-radius:10px;padding:14px;">
            <div style="font-size:1.4rem;font-weight:900;color:#065F46;">✅</div>
            <div style="font-size:.78rem;color:#6B7280;">Emas digital atau fizikal deposit</div>
          </div>
          <div style="background:#fff;border:1px solid #BFDBFE;border-radius:10px;padding:14px;">
            <div style="font-size:1.4rem;font-weight:900;color:#1E40AF;">⚡</div>
            <div style="font-size:.78rem;color:#6B7280;">Proses cepat 2–3 hari bekerja</div>
          </div>
        </div>
        <a href="<?= APP_URL ?>/register" class="btn-gold" style="padding:12px 28px;">Mohon Ar Rahnu →</a>
      </div>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div style="background:#fff;border-radius:12px;padding:18px;border:1px solid #E5E7EB;">
          <div style="font-size:.72rem;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Cara Ar Rahnu</div>
          <?php foreach ([
            ['Gadai emas digital atau deposit fizikal','1'],
            ['Admin semak & tetapkan kadar ujrah','2'],
            ['Wang dicreditkan ke akaun bank','3'],
            ['Bayar balik + ujrah untuk tebus emas','4'],
          ] as [$s,$n]): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:6px 0;font-size:.83rem;color:var(--kasih-dark);border-bottom:1px solid #F3F4F6;">
            <span style="width:22px;height:22px;background:var(--gold-dark);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;flex-shrink:0;"><?= $n ?></span>
            <?= $s ?>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:12px;padding:14px;font-size:.8rem;color:#92400E;">
          ✨ <strong>Kelebihan Unik:</strong> Depositkan emas fizikal anda — kemudian gadaikan terus melalui Ar Rahnu tanpa perlu bawa emas ke pejabat sekali lagi!
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ CAMPAIGNS ════════════════════════════════════════════════════════════ -->
<?php if (!empty($campaigns)): ?>
<section style="padding:60px 0;background:#FAFAFA;">
  <div class="lp-section">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:10px;">
      <div>
        <div class="lp-pill">Simpanan Bersasar</div>
        <h2 style="font-size:1.5rem;font-weight:800;color:var(--kasih-dark);margin-top:6px;">Kempen Simpanan Aktif</h2>
      </div>
      <a href="<?= APP_URL ?>/campaigns" class="btn-gold-outline btn-sm">Lihat Semua Kempen</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
      <?php foreach ($campaigns as $c): ?>
      <div class="cmp-card">
        <div class="cmp-tag">Kempen Aktif</div>
        <div class="cmp-title"><?= h($c['title']) ?></div>
        <?php if ($c['description']): ?><p style="font-size:.82rem;color:#6B7280;margin-bottom:10px;line-height:1.6;"><?= h(substr($c['description'],0,100)) ?>…</p><?php endif; ?>
        <?php if ($c['target_points']): ?><div style="font-size:.78rem;color:var(--gold-dark);font-weight:600;margin-bottom:12px;">Sasaran: <?= gold_format_points($c['target_points']) ?> pts</div><?php endif; ?>
        <a href="<?= APP_URL ?>/register" class="btn-gold btn-sm"><?= h($c['cta_text'] ?: 'Sertai Kempen') ?> →</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ TRUST ════════════════════════════════════════════════════════════════ -->
<section style="padding:60px 0;">
  <div class="lp-section" style="text-align:center;">
    <h2 class="lp-section-h" style="margin-bottom:30px;">Dipercayai & Berdaftar</h2>
    <div class="trust-row">
      <div class="trust-chip">🏢 Berdaftar SSM Malaysia</div>
      <div class="trust-chip">🛡️ Peti Besi Diinsuranskan RM10M</div>
      <div class="trust-chip">☪️ Mematuhi Prinsip Syariah</div>
      <div class="trust-chip">🔒 Data Dilindungi PDPA</div>
      <div class="trust-chip">🏦 Penyimpan Berlesen</div>
    </div>
    <div style="margin-top:28px;padding:16px 24px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;display:inline-block;max-width:680px;text-align:left;font-size:.78rem;color:#92400E;line-height:1.7;">
      ⚠️ <strong>Penafian:</strong> Kasih Gold Easy adalah platform penjagaan emas (custodian) yang berdaftar di bawah SSM dan tidak dikawal selia oleh SC atau BNM. Pelaburan dalam emas melibatkan risiko pasaran. Pulangan lalu tidak menjamin pulangan masa depan.
    </div>
  </div>
</section>

<!-- ══ FINAL CTA ═════════════════════════════════════════════════════════════ -->
<section style="background:linear-gradient(135deg,#1a0e00,#2d1a00);padding:80px 24px;text-align:center;">
  <div style="font-size:3rem;margin-bottom:16px;">✦</div>
  <h2 style="font-size:clamp(1.6rem,4vw,2.4rem);font-weight:900;color:#fff;margin-bottom:12px;">Mula Hari Ini.<br><span style="color:var(--gold-light);">Emas Anda, Masa Depan Anda.</span></h2>
  <p style="color:rgba(255,255,255,.6);font-size:.95rem;margin-bottom:32px;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.7;">Sertai <?= number_format(max($user_count,1500)) ?>+ pengguna yang sedang membina kekayaan melalui simpanan emas digital Kasih Gold Easy.</p>
  <a href="<?= APP_URL ?>/register" class="btn-gold btn-lg" style="font-size:1.05rem;padding:16px 36px;">Daftar Percuma — Bermula RM5 →</a>
  <div style="margin-top:16px;font-size:.75rem;color:rgba(255,255,255,.35);">Tiada yuran bulanan. Tiada komitmen. Boleh berhenti bila-bila masa.</div>
</section>

<?php layout_footer(); ?>
