<?php
declare(strict_types=1);
/**
 * Kasih Gold Easy — Layout & Rendering Helpers
 */

if (!function_exists('auth_check')) {
    require_once __DIR__ . '/auth.php';
}
if (!function_exists('flash_html')) {
    require_once __DIR__ . '/helpers.php';
}
if (!function_exists('csrf_token')) {
    require_once __DIR__ . '/csrf.php';
}

// ----------------------------------------------------------------
// layout_head
// ----------------------------------------------------------------
function layout_head(string $title, array $extra_css = []): void {
    $csrf = csrf_token();
    $full_title = h($title) . ' | Kasih Gold Easy';
    echo <<<HTML
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$full_title}</title>
  <meta name="csrf-token" content="{$csrf}">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            gold:  { DEFAULT:'#C9A84C', light:'#F0D070', dark:'#A07830' },
            kasih: { bg:'#FAFAF8', dark:'#1A1A2E' }
          },
          fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
        }
      }
    }
  </script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
HTML;
    foreach ($extra_css as $css) {
        echo '  <link rel="stylesheet" href="' . h($css) . '">' . "\n";
    }
    echo "</head>\n<body>\n";
}

// ----------------------------------------------------------------
// layout_header
// ----------------------------------------------------------------
function layout_header(?array $user = null): void {
    $user      = $user ?: auth_user();
    $role      = $user ? ($user['role'] ?? '') : '';
    $logged_in = !empty($user);
    $name      = $logged_in ? h($user['full_name'] ?? 'Pengguna') : '';
    $initials  = $logged_in ? strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) : 'U';
    $app_url   = APP_URL;

    // Nav links per role
    $links = [];
    if ($role === 'super_admin') {
        $links = [
            ['Dashboard', '/admin'],
            ['Harga Emas', '/admin/gold-price'],
            ['Pengguna', '/admin/users'],
            ['Pedagang', '/admin/merchants'],
            ['Pasaran', '/admin/marketplace'],
            ['Laporan', '/admin/reports'],
        ];
    } elseif ($role === 'merchant') {
        $links = [
            ['Dashboard', '/merchant'],
            ['Produk', '/merchant/products'],
            ['Pesanan', '/merchant/orders'],
            ['Bayaran', '/merchant/payouts'],
        ];
    } elseif ($role === 'user') {
        $links = [
            ['Dashboard', '/dashboard'],
            ['Wallet', '/wallet'],
            ['_group', '💛 Emas', [
                ['Beli Emas',     '/buy-gold'],
                ['Jual Emas',     '/sell-gold'],
                ['Emas Fizikal',  '/physical-gold'],
                ['Ar Rahnu',      '/ar-rahnu'],
            ]],
            ['_group', '🛍️ Aktiviti', [
                ['Pasaran',   '/marketplace'],
                ['Kempen',    '/campaigns'],
                ['Rujukan',   '/referrals'],
            ]],
            ['_group', '⚙️ Akaun', [
                ['Pindah',  '/transfer'],
                ['eKYC',    '/kyc'],
            ]],
        ];
    }

    $current_path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    echo '<nav class="nav-kasih"><div class="nav-inner">';
    // Brand
    echo '<a href="' . ($logged_in ? ($role === 'super_admin' ? '/admin' : ($role === 'merchant' ? '/merchant' : '/dashboard')) : '/') . '" class="nav-brand">';
    echo '✦ Kasih Gold Easy<small>Emas Mudah, Kaya Mudah.</small></a>';

    // Center nav links (supports flat items and _group dropdowns)
    if (!empty($links)) {
        echo '<ul class="nav-links hidden md:flex">';
        foreach ($links as $item) {
            if (($item[0] ?? '') === '_group') {
                [, $grp_label, $subitems] = $item;
                $grp_active = false;
                foreach ($subitems as [$sl, $sp]) {
                    if (strpos($current_path, $sp) === 0) { $grp_active = true; break; }
                }
                $btn_color  = $grp_active ? '#A07830' : '#374151';
                $btn_weight = $grp_active ? '700' : '500';
                echo '<li x-data="{open:false}" style="position:relative;list-style:none;">';
                echo '<button @click="open=!open" @click.outside="open=false" @keydown.escape="open=false"'
                   . ' style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:3px;'
                   . 'padding:8px 10px;font-size:0.875rem;font-weight:' . $btn_weight . ';color:' . $btn_color . ';white-space:nowrap;">'
                   . h($grp_label)
                   . '<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="margin-top:1px;flex-shrink:0;"><path d="M6 9l6 6 6-6"/></svg>'
                   . '</button>';
                echo '<div x-show="open" x-cloak style="position:absolute;top:calc(100% + 4px);left:0;min-width:170px;background:#fff;border:1px solid #EEECE8;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.10);padding:6px 0;z-index:100;">';
                foreach ($subitems as [$sl, $sp]) {
                    $sa = strpos($current_path, $sp) === 0;
                    $ss = $sa ? 'background:rgba(201,168,76,0.09);color:#A07830;font-weight:600;' : 'color:#374151;';
                    echo '<a href="' . h($app_url . $sp) . '" style="display:block;padding:9px 16px;font-size:0.875rem;text-decoration:none;' . $ss . '">' . h($sl) . '</a>';
                }
                echo '</div></li>';
            } else {
                [$label, $path] = $item;
                $active = (strpos($current_path, $path) === 0) ? ' active' : '';
                echo '<li><a href="' . h($app_url . $path) . '" class="' . $active . '">' . h($label) . '</a></li>';
            }
        }
        echo '</ul>';
    }

    // Right side
    echo '<div class="nav-right">';
    if ($logged_in) {
        // Wallet balance for users
        if ($role === 'user') {
            try {
                $bal = get_wallet_balance((int)$user['id']);
                echo '<a href="' . h($app_url . '/wallet') . '" class="hidden md:flex items-center gap-1 points-badge">';
                echo '⭐ ' . gold_format_points($bal['points']) . ' pts</a>';
            } catch (\Throwable $e) { /* ignore */ }
        }
        // User dropdown
        echo '<div x-data="{open:false}" class="relative">';
        echo '<button @click="open=!open" @keydown.escape="open=false" class="nav-user-btn">';
        echo '<span class="nav-avatar">' . h($initials) . '</span>';
        echo '<span class="hidden sm:inline">' . $name . '</span>';
        echo '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        echo '</button>';
        echo '<div x-show="open" x-cloak @click.outside="open=false" class="absolute right-0 top-full mt-1 w-44 bg-white border border-gray-100 rounded-xl shadow-lg py-1 z-50">';
        echo '<a href="' . h($app_url . '/profile') . '" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">👤 Profil Saya</a>';
        if ($role === 'user') {
            echo '<a href="' . h($app_url . '/wallet') . '" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">💛 Wallet Saya</a>';
        }
        echo '<div class="border-t border-gray-100 my-1"></div>';
        echo '<a href="' . h($app_url . '/logout') . '" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">🚪 Log Keluar</a>';
        echo '</div></div>'; // end dropdown
        // Hamburger (visible on mobile) — onclick wired directly, no JS init needed
        echo '<button id="hamburger-btn" onclick="kasihToggleNav()" class="hamburger-btn" aria-label="Menu" style="display:none;background:none;border:none;cursor:pointer;padding:8px;color:#1A1A2E;">';
        echo '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>';
        echo '</button>';
    } else {
        echo '<a href="' . h($app_url . '/login') . '" class="btn-gold-outline btn-sm">Log Masuk</a>';
        echo '<a href="' . h($app_url . '/register') . '" class="btn-gold btn-sm">Daftar</a>';
    }
    echo '</div>'; // nav-right
    echo '</div></nav>' . "\n";

    // ── Mobile nav panel (user pages only — no sidebar) ──────────────────────
    if ($logged_in && $role === 'user' && !empty($links)) {
        // Inline styles guarantee hidden on load regardless of CSS cache
        echo '<div id="mobile-nav-panel" style="display:none;position:fixed;top:64px;left:0;right:0;bottom:0;background:#fff;z-index:9999;overflow-y:auto;border-top:3px solid #C9A84C;">';
        // Wallet balance strip
        try {
            $bal = get_wallet_balance((int)$user['id']);
            echo '<div style="padding:14px 20px;background:#FAFAF8;border-bottom:1px solid #F3F4F6;">';
            echo '<div style="font-size:0.7rem;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:2px;">Baki Gold Points</div>';
            echo '<div style="font-size:1.3rem;font-weight:800;color:#A07830;">' . gold_format_points($bal['points']) . ' <span style="font-size:0.85rem;font-weight:500;color:#6B7280;">pts</span></div>';
            echo '</div>';
        } catch (\Throwable $e) { /* ignore */ }
        // Nav links — fully inline-styled so they render correctly even if app.css is stale
        foreach ($links as $item) {
            if (($item[0] ?? '') === '_group') {
                [, $grp_label, $subitems] = $item;
                echo '<div style="padding:9px 20px 5px;font-size:0.67rem;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.1em;font-weight:700;background:#FAFAF8;border-top:1px solid #F3F4F6;border-bottom:1px solid #F3F4F6;">' . h($grp_label) . '</div>';
                foreach ($subitems as [$sl, $sp]) {
                    $is_active = strpos($current_path, $sp) === 0;
                    $bg     = $is_active ? 'background:rgba(201,168,76,0.1);color:#A07830;font-weight:600;' : 'color:#374151;';
                    $border = $is_active ? 'border-left:3px solid #C9A84C;' : 'border-left:3px solid transparent;';
                    echo '<a href="' . h($app_url . $sp) . '" '
                       . 'style="display:flex;align-items:center;padding:13px 20px 13px 28px;font-size:0.9rem;'
                       . 'font-weight:500;text-decoration:none;border-bottom:1px solid #F9FAFB;' . $bg . $border . '">'
                       . h($sl) . '</a>';
                }
            } else {
                [$label, $path] = $item;
                $is_active = strpos($current_path, $path) === 0;
                $bg     = $is_active ? 'background:rgba(201,168,76,0.1);color:#A07830;font-weight:600;' : 'color:#374151;';
                $border = $is_active ? 'border-left:3px solid #C9A84C;' : 'border-left:3px solid transparent;';
                echo '<a href="' . h($app_url . $path) . '" '
                   . 'style="display:flex;align-items:center;padding:14px 20px;font-size:0.95rem;'
                   . 'font-weight:500;text-decoration:none;border-bottom:1px solid #F9FAFB;' . $bg . $border . '">'
                   . h($label) . '</a>';
            }
        }
        echo '<div style="height:1px;background:#F3F4F6;margin:8px 0;"></div>';
        echo '<a href="' . h($app_url . '/profile') . '" style="display:flex;align-items:center;gap:10px;padding:14px 20px;font-size:0.95rem;font-weight:500;color:#374151;text-decoration:none;border-bottom:1px solid #F9FAFB;">👤 Profil Saya</a>';
        echo '<a href="' . h($app_url . '/logout') . '" style="display:flex;align-items:center;gap:10px;padding:14px 20px;font-size:0.95rem;font-weight:500;color:#DC2626;text-decoration:none;">🚪 Log Keluar</a>';
        echo '</div>';
        // Dark overlay — closes panel on tap-outside
        echo '<div id="mobile-nav-overlay" onclick="kasihToggleNav()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9998;"></div>';

        // Inline script — defined immediately in HTML, no DOMContentLoaded or app.js dependency
        echo '<script>
function kasihToggleNav() {
    var panel   = document.getElementById("mobile-nav-panel");
    var overlay = document.getElementById("mobile-nav-overlay");
    var btn     = document.getElementById("hamburger-btn");
    if (!panel) return;
    var opening = panel.style.display !== "block";
    panel.style.display   = opening ? "block"  : "none";
    if (overlay) overlay.style.display = opening ? "block"  : "none";
    document.body.style.overflow = opening ? "hidden" : "";
    if (btn) btn.setAttribute("aria-expanded", opening);
}
</script>';
    }
}

// ----------------------------------------------------------------
// layout_footer
// ----------------------------------------------------------------
function layout_footer(): void {
    $wa      = h(get_setting('support_whatsapp', '+60123456789'));
    $year    = date('Y');
    $app_url = APP_URL;
    echo <<<HTML
<footer style="background:var(--kasih-dark);color:#fff;padding:40px 0 24px;margin-top:40px;">
  <div class="page-container">
    <div style="display:flex;flex-wrap:wrap;gap:32px;justify-content:space-between;align-items:flex-start;">
      <div>
        <div style="font-size:1.15rem;font-weight:800;color:var(--gold);margin-bottom:4px;">✦ Kasih Gold Easy</div>
        <div style="font-size:0.8rem;font-style:italic;color:rgba(240,208,112,0.7);margin-bottom:12px;">Emas Mudah, Kaya Mudah.</div>
        <p style="font-size:0.75rem;color:rgba(255,255,255,0.4);line-height:1.6;">
          Dibangunkan oleh SLV Group<br>dengan kerjasama Kasih AP Gold
        </p>
      </div>
      <div style="display:flex;gap:40px;flex-wrap:wrap;">
        <div>
          <div style="font-size:0.75rem;color:rgba(201,168,76,0.8);font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Platform</div>
          <div style="display:flex;flex-direction:column;gap:6px;font-size:0.82rem;color:rgba(255,255,255,0.55);">
            <a href="{$app_url}/marketplace" style="color:rgba(255,255,255,0.55);">Pasaran Maya</a>
            <a href="{$app_url}/campaigns"   style="color:rgba(255,255,255,0.55);">Kempen Simpanan</a>
            <a href="{$app_url}/referrals"   style="color:rgba(255,255,255,0.55);">Program Rujukan</a>
          </div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:rgba(201,168,76,0.8);font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Sokongan</div>
          <div style="display:flex;flex-direction:column;gap:6px;font-size:0.82rem;color:rgba(255,255,255,0.55);">
            <a href="{$app_url}/terms"   style="color:rgba(255,255,255,0.55);">Terma &amp; Syarat</a>
            <a href="{$app_url}/privacy" style="color:rgba(255,255,255,0.55);">Dasar Privasi</a>
            <a href="https://wa.me/{$wa}" style="color:rgba(255,255,255,0.55);">WhatsApp Kami</a>
          </div>
        </div>
      </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,0.08);margin-top:28px;padding-top:16px;text-align:center;font-size:0.72rem;color:rgba(255,255,255,0.28);">
      &copy; {$year} SLV Group &amp; Kasih AP Gold. Hak cipta terpelihara.<br>
      Sistem ini tidak memberikan nasihat kewangan. Sila rujuk penasihat berlesen untuk kepastian lanjut.
    </div>
  </div>
</footer>
<script src="/assets/js/app.js"></script>
</body>
</html>
HTML;
}

// ----------------------------------------------------------------
// layout_flash
// ----------------------------------------------------------------
function layout_flash(string $key = 'main'): void {
    echo flash_html($key);
}

// ----------------------------------------------------------------
// Page wrapper helpers
// ----------------------------------------------------------------
function layout_begin_admin(string $title): void {
    layout_head($title);
    layout_header(auth_user());
    echo '<div style="display:flex;">';
    layout_sidebar_admin();
    echo '<main class="main-with-sidebar" style="flex:1;">';
    layout_flash();
}
function layout_end_admin(): void {
    echo '</main></div>';
    layout_footer();
}

function layout_begin_merchant(string $title): void {
    layout_head($title);
    layout_header(auth_user());
    echo '<div style="display:flex;">';
    layout_sidebar_merchant();
    echo '<main class="main-with-sidebar" style="flex:1;">';
    layout_flash();
}
function layout_end_merchant(): void {
    echo '</main></div>';
    layout_footer();
}

function layout_begin_user(string $title): void {
    layout_head($title);
    layout_header(auth_user());
    echo '<main style="max-width:1100px;margin:0 auto;padding:24px 16px;">';
    layout_flash();
}
function layout_end_user(): void {
    echo '</main>';
    layout_footer();
}

// ----------------------------------------------------------------
// layout_sidebar_admin
// ----------------------------------------------------------------
function layout_sidebar_admin(): void {
    $app_url = APP_URL;
    $cp      = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

    function _slink(string $href, string $icon, string $label, string $cp, string $app_url): void {
        $active = (strpos($cp, parse_url($href, PHP_URL_PATH) ?: $href) === 0) ? ' active' : '';
        echo '<a href="' . h($app_url . $href) . '" class="sidebar-item' . $active . '">';
        echo '<span class="sidebar-icon">' . $icon . '</span>' . h($label) . '</a>';
    }

    echo '<aside class="sidebar-kasih">';
    echo '<div style="padding:16px 20px 8px;font-size:0.7rem;color:rgba(255,255,255,0.3);text-transform:uppercase;letter-spacing:0.12em;font-weight:700;">Admin Panel</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">UTAMA</div>';
    _slink('/admin',              '🏠', 'Dashboard',           $cp, $app_url);
    echo '</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">EMAS</div>';
    _slink('/admin/gold-price',   '💛', 'Harga Emas',          $cp, $app_url);
    _slink('/admin/purchases',    '🛒', 'Pembelian',           $cp, $app_url);
    _slink('/admin/transfers',    '↔️', 'Pindahan',            $cp, $app_url);
    echo '</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">PENGGUNA</div>';
    _slink('/admin/users',        '👤', 'Pengguna',            $cp, $app_url);
    _slink('/admin/merchants',    '🏪', 'Pedagang',            $cp, $app_url);
    echo '</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">PASARAN</div>';
    _slink('/admin/marketplace',  '🛍️', 'Pasaran Maya',       $cp, $app_url);
    _slink('/admin/campaigns',    '🎯', 'Kempen',              $cp, $app_url);
    echo '</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">KEWANGAN</div>';
    _slink('/admin/payouts',      '💰', 'Permintaan Bayaran',  $cp, $app_url);
    _slink('/admin/sell-gold',      '💵', 'Jual Emas',           $cp, $app_url);
    _slink('/admin/ar-rahnu',       '🕌', 'Ar Rahnu',            $cp, $app_url);
    _slink('/admin/physical-gold',  '🥇', 'Emas Fizikal',        $cp, $app_url);
    _slink('/admin/referrals',    '📢', 'Rujukan',             $cp, $app_url);
    _slink('/admin/kyc',          '🪪', 'eKYC',               $cp, $app_url);
    echo '</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">SISTEM</div>';
    _slink('/admin/settings',     '⚙️', 'Tetapan',             $cp, $app_url);
    _slink('/admin/ai-settings',  '🤖', 'Tetapan AI',          $cp, $app_url);
    _slink('/admin/audit-logs',   '📋', 'Log Audit',           $cp, $app_url);
    _slink('/admin/reports',      '📈', 'Laporan',             $cp, $app_url);
    echo '</div>';

    echo '</aside>';
}

// ----------------------------------------------------------------
// layout_sidebar_merchant
// ----------------------------------------------------------------
function layout_sidebar_merchant(): void {
    $app_url = APP_URL;
    $cp      = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

    function _mlink(string $href, string $icon, string $label, string $cp, string $app_url): void {
        $active = (strpos($cp, parse_url($href, PHP_URL_PATH) ?: $href) === 0) ? ' active' : '';
        echo '<a href="' . h($app_url . $href) . '" class="sidebar-item' . $active . '">';
        echo '<span class="sidebar-icon">' . $icon . '</span>' . h($label) . '</a>';
    }

    echo '<aside class="sidebar-kasih">';
    echo '<div style="padding:16px 20px 8px;font-size:0.7rem;color:rgba(255,255,255,0.3);text-transform:uppercase;letter-spacing:0.12em;font-weight:700;">Merchant Panel</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">UTAMA</div>';
    _mlink('/merchant',           '🏠', 'Dashboard',           $cp, $app_url);
    echo '</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">PERNIAGAAN</div>';
    _mlink('/merchant/products',  '📦', 'Produk Saya',         $cp, $app_url);
    _mlink('/merchant/orders',    '📋', 'Pesanan',             $cp, $app_url);
    echo '</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">KEWANGAN</div>';
    _mlink('/merchant/payouts',   '💰', 'Permintaan Bayaran',  $cp, $app_url);
    _mlink('/merchant/referrals', '📢', 'Rujukan Saya',        $cp, $app_url);
    echo '</div>';

    echo '<div class="sidebar-section"><div class="sidebar-group-label">AKAUN</div>';
    _mlink('/merchant/profile',   '👤', 'Profil & Tetapan',   $cp, $app_url);
    echo '</div>';

    echo '</aside>';
}
