# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Adcellent ESG OS** — a PHP 8.x multi-tenant ESG (Environmental, Social, Governance) reporting platform for Malaysian SMEs. Hosted on Hostinger shared hosting. No build system, no Composer, no npm. Pure PHP + MySQL + Bootstrap 5 via CDN.

## Deployment (no local dev server)

There is no `localhost` test environment. Changes are deployed by:
1. Editing files in this repo
2. Uploading changed files manually to Hostinger `public_html/` via FTP or File Manager
3. Running SQL migrations via **phpMyAdmin** (`hPanel → phpMyAdmin`)

**Database migrations run order:** `db/schema.sql` → `migrate_v2.sql` → `migrate_v3.sql` → `migrate_v4.sql` → `migrate_v5.sql`

> `db/schema.sql` has a stale role ENUM (`admin, consultant, sme_owner`). The full 6-role ENUM and `parent_id` column were added via the migration files. Always run all migrations on a fresh install.

**Demo accounts** (all password `Demo@1234`):
- `admin@demo.com` — admin
- `principal@demo.com` — principal
- `associate@demo.com` — associate
- `manager@demo.com` — manager
- `consultant@demo.com` — consultant
- `sme@demo.com` — sme_owner (has "Demo Sdn Bhd" linked)

## Architecture

### Request Lifecycle

```
Browser → .htaccess (rewrite) → index.php (router) → pages/*.php
```

`index.php` is the front controller. It maps URL slugs to `pages/` files via a `$routes` array. Clean URLs (`/dashboard`) are rewritten to `index.php` by `.htaccess`; query-string mode (`?page=dashboard`) also works.

Every protected page starts with:
```php
require_once __DIR__ . '/../includes/auth_check.php';
```

`auth_check.php` bootstraps everything: loads all config files, requires all `src/` classes, starts the session, enforces auth, and sets these variables available to every page:
- `$currentUser` — `['id', 'name', 'email', 'role', 'parent_id']`
- `$activeCompanyId` — int|null from session
- `$activeCompany` — company row array|null
- `$activePlan` — plan array (starter/standard/professional)
- `$unlockedIds` — array of unlocked indicator IDs, or `'all'`

Also defines the `url(string $page)` helper for generating app URLs.

### Page Layout Pattern

All authenticated pages use this HTML structure:

```php
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar"> ... </div>
    <div class="content-body"> ... </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
```

### CSS Strategy

`assets/css/app.css` holds global styles (`app-layout`, `topbar`, `sidebar`, `stat-card`, `company-card`, etc.). Because Hostinger uploads are manual and app.css may be stale, **page-specific CSS must be embedded as an inline `<style>` block in the page file itself** — do not rely on app.css for new styles. See `pages/carbon.php` and `pages/onboarding.php` for the pattern.

### Key Source Classes (`src/`)

| Class | Responsibility |
|---|---|
| `Database` | PDO singleton. Use `Database::fetchOne()`, `fetchAll()`, `query()`, `insert()`, `upsert()` — never construct PDO directly. |
| `Auth` | Session management, login/logout, CSRF (`Auth::csrfToken()` / `Auth::verifyCsrf()`), active company (`Auth::activeCompanyId()` / `Auth::setActiveCompany()`). |
| `Company` | CRUD for companies. `Company::getForUser($userId, $role)` for direct-linked companies. Admin role returns all companies. |
| `Hierarchy` | Org-tree traversal. `Hierarchy::getAccessibleCompanies($userId, $role)` — **must be used instead of `Company::getForUser()`** for principal/associate/manager roles. |
| `ESGDataManager` | Save/retrieve ESG indicator values. `getCompletionStats($companyId, $framework, $year)` returns per-category scores. `calcOverallScore($stats)` applies E=40/S=35/G=25 weights. |
| `GapAnalyzer` | Identifies missing/low-scoring indicators and generates prioritised recommendations. |
| `ReportGenerator` | Builds HTML/PDF ESG reports. |
| `Benchmarker` | Compares company ESG scores against industry peers. |
| `Subscription` | Plan access control. `getActivePlan($companyId)` — priority: active DB subscription → 14-day new-company trial (professional) → starter (free). `getUnlockedIndicatorIds($companyId)` returns array or `'all'`. |
| `CarbonCalculator` | Scope 1/2/3 emission factor calculations. |

### Role System

Six roles with distinct access patterns:

| Role | Description | Company Access Method |
|---|---|---|
| `admin` | Platform super-admin. Redirected to `/admin` on login. No active company. | `Company::getForUser()` (returns all) |
| `principal` | Top of consultant hierarchy. Sees all companies under their entire org tree. | `Hierarchy::getPrincipalCompanies()` |
| `associate` | Mid-tier. Sees own + all managers' companies. | `Hierarchy::getAssociateCompanies()` |
| `manager` | Sees only directly linked companies. | `Hierarchy::getDirectCompanies()` |
| `consultant` | Independent consultant. Direct company links. | `Company::getForUser()` |
| `sme_owner` | Company owner. Direct company links. | `Company::getForUser()` |

Hierarchy parent-child is stored in `users.parent_id`. `Hierarchy::descendantIds()` does a BFS traversal to get all descendants.

**Sidebar navigation** (`includes/sidebar.php`) has three separate branches by role: admin, hierarchy (principal/associate/manager), and sme_owner/consultant. ESG tool links (Data Entry, Gap Analysis, Reports, Carbon, Benchmarking) are only shown when `$activeCompany` is set.

### Configuration Files (`config/`)

- `app.php` — App constants. `APP_URL` is auto-detected from document root (no manual edit needed). ESG score weights: `WEIGHT_ENVIRONMENT=40`, `WEIGHT_SOCIAL=35`, `WEIGHT_GOVERNANCE=25`.
- `database.php` — DB credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`).
- `frameworks.php` — Registry of all supported ESG frameworks (BURSA_SEDG, GRI, ISSB, ESRS, etc.) with metadata. Add new frameworks here.
- `plans.php` — Subscription plan definitions (starter/standard/professional) with `indicator_ids` arrays. `'all'` means no gating.
- `collections.php` — Add-on indicator collections.

### Database Schema (key tables)

- `users` — `id, name, email, password, role, parent_id, is_active`
- `companies` — `id, name, industry, revenue_tier, employee_count, framework, reporting_year, created_by, is_pre_ipo`
- `user_companies` — junction: `user_id, company_id, role(owner|editor|viewer)`
- `esg_data` — `company_id, indicator_id, framework, category, value, period` — unique on `(company_id, indicator_id, period)`
- `subscriptions` — `company_id, plan_code, status, expires_at`
- `activity_log` — audit trail for all user actions
- `platform_settings` — key/value store for admin-editable settings (added in `migrate_v5.sql`)
- `gap_analysis_cache` — cached gap analysis results with `priority` field

### Adding a New Page

1. Create `pages/newpage.php` starting with `require_once __DIR__ . '/../includes/auth_check.php';`
2. Add the route to the `$routes` array in `index.php`
3. Add a nav link in the appropriate sidebar branch in `includes/sidebar.php`
4. Embed all page-specific CSS in a `<style>` block at the top of the page file
