<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| AiServe.my — Corporate Identity (single source of truth)
|--------------------------------------------------------------------------
| These are the canonical brand tokens used by BOTH the public site
| (inc/public_header_css.php) and the admin panel (inc/admin_layout.php).
| Change the brand ONCE here and it updates everywhere.
|
| Usage:  :root { <?= brand_css_vars() ?> }
*/

if (!function_exists('brand_css_vars')) {
    /**
     * Canonical CSS custom properties. Emitted inside a `:root { ... }` block.
     */
    function brand_css_vars(): string {
        return <<<CSS
/* Brand palette */
--primary:#6d28d9;
--primary2:#8b5cf6;
--primary3:#ede9fe;
--dark:#140f24;

/* Surfaces */
--bg:#f6f3ff;
--bg2:#ffffff;
--card:#ffffff;
--sidebar:#130d22;
--sidebarText:#ddd6fe;

/* Text & lines */
--text:#1f1534;
--muted:#6f6487;
--line:#e7defc;

/* Status */
--success:#16a34a;
--danger:#dc2626;

/* Elevation */
--shadow:0 18px 45px rgba(109,40,217,.10);
--shadow-lg:0 28px 70px rgba(109,40,217,.16);

/* Geometry */
--radius:18px;
--radius-lg:24px;
--container:1180px;

/* Typography */
--font-sans:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;

/* Brand gradient */
--gradient-brand:linear-gradient(135deg,var(--primary),var(--primary2));
CSS;
    }
}
