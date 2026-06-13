# ESG gen — Platform Features Reference

**Adcellent Biz Sdn Bhd (1511714-V)** · PHP 8.x · MySQL · Bootstrap 5 · Hostinger Shared Hosting

---

## 1. Architecture Overview

```
Browser
  └── .htaccess (clean URL rewrite)
        └── index.php  (front controller — $routes array)
              └── pages/*.php  (page handlers)
                    └── includes/auth_check.php  (session + class bootstrap)
                          └── src/*.php  (business logic classes)
                                └── Database.php  (PDO singleton → MySQL)
```

- **No build system.** Pure PHP + CDN-hosted Bootstrap 5, Chart.js, Bootstrap Icons.
- **Session-based auth.** Active company stored in `$_SESSION['active_company_id']`.
- **Multi-tenant.** Every data table is scoped by `company_id`.
- **Config-driven.** Frameworks, plans, indicators, and weights are all PHP config files — no hardcoded logic.

---

## 2. Authentication & Session Management

**Class:** `Auth` · **Table:** `users`

| Feature | Detail |
|---|---|
| Registration | Email + bcrypt password, role assignment, session start |
| Login | Credential validation, role-based redirect, auto-company selection |
| Logout | Session clear + activity log |
| CSRF Protection | `Auth::csrfToken()` / `Auth::verifyCsrf()` on all form submissions |
| Session Timeout | 24-hour lifetime (`SESSION_LIFETIME = 86400`) |
| Active Company | `Auth::activeCompanyId()` / `Auth::setActiveCompany()` via session |
| Activity Logging | LOGIN / LOGOUT recorded with IP + user-agent |

**Login Redirects by Role:**
- `admin` → `/admin`
- `principal / associate / manager` → `/dashboard`
- `sme_owner / consultant` → `/onboarding` (if no company) or `/companies`

---

## 3. Role System

**6 roles** stored in `users.role` ENUM. Hierarchy stored in `users.parent_id`.

| Role | Description | Company Access Method |
|---|---|---|
| `admin` | Platform super-admin, no active-company context | `Company::getForUser()` (returns all) |
| `principal` | Top of consultant org tree, sees all descendants recursively | `Hierarchy::getPrincipalCompanies()` |
| `associate` | Mid-tier, sees own + all managers' companies | `Hierarchy::getAssociateCompanies()` |
| `manager` | Sees only directly-linked companies | `Hierarchy::getDirectCompanies()` |
| `consultant` | Independent, sees only directly-linked companies | `Company::getForUser()` |
| `sme_owner` | Business owner, sees own companies | `Company::getForUser()` |

**Sidebar navigation** renders three separate branches: admin / hierarchy (principal–associate–manager) / sme+consultant.

---

## 4. Multi-Tenancy & Company Management

**Classes:** `Company`, `Hierarchy` · **Tables:** `companies`, `user_companies`

- **Company metadata:** name, registration_no, industry, revenue tier, employee count, framework, reporting year, Bursa sector, reporting scope (HQ / factory / group), report level, pre-IPO flag.
- **Access junction:** `user_companies` (user_id, company_id, role: owner / editor / viewer).
- **Company selector** for multi-company users (switch active company in-session).
- Auto-assigns creator as `owner`. Auto-creates 6 default departments on creation.
- Admin can view / delete any company.

**Key methods:**
- `Company::create()` — validate, insert, assign owner
- `Company::getForUser()` — direct-linked companies
- `Company::getById()` — with access check
- `Hierarchy::getAccessibleCompanies()` — role-aware dispatcher
- `Hierarchy::descendantIds()` — BFS traversal of parent_id tree

---

## 5. ESG Data Entry

**Class:** `ESGDataManager` · **Table:** `esg_data`

### Supported Frameworks (10 total)

| Framework | Indicators | Notes |
|---|---|---|
| BURSA_SEDG | 41 | Mandatory for Bursa-listed & pre-IPO |
| GRI Standards | 33 | Global voluntary standard |
| ISSB (IFRS S1 & S2) | 20 | Bursa mandating 2025–26 |
| TCFD | 11 | Climate financial disclosures |
| UN SDGs | 17 | 17 goals, required for grants |
| SASB Manufacturing | 16 | Industry-specific |
| SASB Food & Beverage | 15 | Industry-specific |
| SASB Technology | 14 | Industry-specific |
| CDP | 15 | Climate — required by 280+ MNCs |
| ESRS (EU CSRD) | 22 | For export-facing companies |

### ESG Categories & Weights

| Category | Weight | Examples |
|---|---|---|
| Environment (E) | 40% | Emissions, energy, water, waste |
| Social (S) | 35% | Safety, diversity, training, turnover |
| Governance (G) | 25% | Board, anti-corruption, policies |

### Data Model

Each indicator has: `code`, `name`, `category`, `data_type` (number / percentage / boolean / text), `unit`, `required` flag, `priority`.

Each saved value has: `value`, `unit`, `notes`, `data_source`, `verified` (boolean), `period` (year), `entered_by`.

**Unique constraint:** `(company_id, indicator_id, period)` — one value per indicator per year.

### Key Methods
- `ESGDataManager::saveBulk()` — batch upsert with validation
- `ESGDataManager::getCompletionStats()` — per-category completion % and scores
- `ESGDataManager::calcOverallScore()` — applies E40 / S35 / G25 weights
- `ESGDataManager::scoreLabel()` — maps score → Excellent / Good / Moderate / Needs Work

---

## 6. Gap Analysis

**Class:** `GapAnalyzer` · **Table:** `gap_analyses` (cache)

- Detects missing or low-scoring indicators and ranks them by priority.
- **Priority levels:** critical / high / medium / low
- **Context-aware:** Pre-IPO companies elevate required indicators to critical; large companies elevate to high.
- Per-indicator output: recommendation text (Malaysia-specific), business impact, effort estimate (low / medium / high), quick-win flag.

**Malaysian Financing Program Matching:**
- BNM Low Carbon Transition Facility
- CGC Green Lane
- SME Corp Sustainability Grant
- Sustainability-Linked Loan (SLL) scoring

- Caches full analysis JSON in `gap_analyses` table. Re-runs on demand.

**Key Methods:**
- `GapAnalyzer::analyze()` — full run, stores cache
- `GapAnalyzer::loadCached()` — retrieve existing result
- `GapAnalyzer::getFinancingOpportunities()` — Malaysian program matching

---

## 7. Report Generation

**Class:** `ReportGenerator` · **Table:** `reports`

**Output:** Multi-page HTML (printable to PDF via browser).

**Report Sections:**
1. Cover page — company name, ESG score, framework, industry/size
2. Executive summary — overall score + E/S/G breakdown cards
3. Category tables — Environment, Social, Governance disclosures with status (Disclosed / MISSING-Required / Not Disclosed)
4. Gap Analysis table — top 30 gaps ranked by priority with recommendations and impact
5. Green Financing Opportunities — programme / benefit / eligibility / requirement
6. Disclaimer footer

**Activity log:** `REPORT_GENERATED` with framework + period.

---

## 8. Carbon Calculator

**Class:** `CarbonCalculator` · **Emission Factors:** MyGHG 2023 (Malaysia)

| Scope | Sources | Factor Basis |
|---|---|---|
| Scope 1 — Stationary | Diesel, petrol, natural gas, LPG, CNG | tCO₂e per litre / m³ |
| Scope 1 — Fleet | Diesel km, petrol km | tCO₂e per km |
| Scope 2 — Electricity | TNB Peninsular, Sabah grid, Sarawak grid | tCO₂e per kWh |
| Scope 3 — Air Travel | Domestic, international flights | tCO₂e per km |
| Scope 3 — Waste | Landfill, recycled | tCO₂e per kg |
| Scope 3 — Water | Water consumption | tCO₂e per m³ |

- Calculates total + sub-category breakdown per scope.
- **Auto-saves** scope 1/2/3 totals into the ESG indicator system (mapped per framework — e.g., BURSA_SEDG: E04/E05/E06; GRI: 305-1/305-2/305-3).
- **Industry intensity benchmarks** (Manufacturing / Services / Trading / Construction / Other × 3 revenue tiers) for peer comparison.

---

## 9. Benchmarking

**Class:** `Benchmarker` · **Source:** Embedded industry + Bursa sector benchmarks

**Embedded Benchmarks:**
- 5 industries × 3 revenue tiers × overall/E/S/G scores
- 13 Bursa Malaysia sectors × 3 revenue tiers × overall/E/S/G scores

**Peer Matching Logic:**
1. Prefer Bursa sector match (when `bursa_sector` is set on company)
2. Fall back to industry + revenue tier
3. Pull up to 20 matching peers from live database
4. Calculate peer average scores

**Output:** My scores vs. peer average vs. industry/sector benchmark + **percentile rank** (Top 10% / Top 25% / Average / Bottom 25% / Bottom 10%).

---

## 10. Subscription & Plan Gating

**Class:** `Subscription` · **Tables:** `subscriptions`, `company_collections`

### Plans

| Plan | Price | Indicators | Features |
|---|---|---|---|
| **Starter** | Free forever | 15 Bursa SEDG mandatory | Dashboard, basic report, no credit card |
| **Standard** | RM 1,500/year | All 200+ across all 10 frameworks | Full OS, gap analysis, carbon calc, benchmarking, PDF export, email support |
| **Professional** | Internal | All | Same as Standard — used for 14-day new-company trial |

**Subscription Resolution Priority:**
1. Active DB subscription (`status = active`, `expires_at > NOW()`)
2. 14-day trial (new company, within 14 days of `created_at`)
3. Starter free (fallback)

**Gating:**
- `Subscription::getUnlockedIndicatorIds()` returns array of IDs or `'all'`
- Data entry, gap analysis, carbon calc, benchmarking — all check against unlocked IDs
- Admin can manually activate plans + collection add-ons

---

## 11. KPI Trends

**Class:** `KPITracker` · **Table:** `monthly_kpi_snapshots`

**Monthly Snapshot (auto-captured on page load):**
- Overall ESG score, E score, S score, G score
- Carbon Scope 1, 2, 3 (tCO₂e)
- Data completion %
- Indicators filled / total

**Analytics:**
- Month-over-month delta per metric
- Chart.js-ready time series (last N months)
- Snapshot table for all months
- CSV export (last 36 months)

**Unique constraint:** `(company_id, year, month)` — one snapshot per company per month.

---

## 12. Action Plans

**Class:** `ActionPlanManager` · **Tables:** `action_plans`, `action_plan_comments`

- Convert gap analysis gaps directly into action plans (pre-filled from recommendation).
- Manual creation also supported.
- Fields: title, description, recommendation, priority (critical/high/medium/low), status (open/in_progress/completed/deferred), due_date, assigned_to (user), department_id, indicator_id.
- **Overdue detection** — flags plans past due_date with open/in_progress status.
- **Comments** on each action plan for team collaboration.
- Assignment triggers a notification to the assigned user.
- Completion timestamp auto-set on status change to `completed`.

---

## 13. Notifications

**Class:** `NotificationManager` · **Table:** `notifications`

| Type | Trigger |
|---|---|
| `action_plan` | Plan assigned to user |
| `overdue_submission` | Deadline passed |
| `kpi_alert` | KPI threshold breached |
| `system` | Platform-level alert |
| `gap_alert` | Critical gap identified |
| `report` | Report generated |

- Unread badge count in sidebar.
- Mark single / mark-all-read.
- Auto-delete read notifications older than 60 days.

---

## 14. Team & Department Management

**Class:** `DepartmentManager` · **Tables:** `departments`, `department_users`

**6 Default Department Templates (auto-created per company):**

| Dept | Colour | ESG Focus |
|---|---|---|
| HR & Admin | Purple | Training, turnover, gender diversity, parental leave, CSR |
| Production | Blue | Electricity, waste, water, gas, Scope 1 emissions |
| HSE | Red | Incidents, LTIFR, fatalities, safety training, near-miss |
| Energy & Emissions | Amber | Scope 1/2/3, intensity, renewable usage |
| Procurement | Green | Sustainable sourcing %, supply chain ESG, supplier screening |
| Logistics | Indigo | Fleet fuel, freight emissions, last-mile carbon |

- Company can create additional custom departments.
- Each department has: name, type, description, color, sort order.
- Members have roles: `head` or `member`.

---

## 15. Admin Panel

**File:** `pages/admin.php` · **Access:** `admin` role only

| Tab | Features |
|---|---|
| Overview | Platform stats: users, active users, companies, ESG entries, reports, logins today |
| Users | List all users, toggle active/inactive, reassign roles |
| Companies | List all companies, delete with cascade |
| ESG Data | View / filter by framework, category, company |
| Settings | Edit platform config (weights, score thresholds, default framework, reporting year) |

**Platform Settings** (stored in `platform_settings` key-value table):
- `WEIGHT_ENVIRONMENT` (default 40), `WEIGHT_SOCIAL` (35), `WEIGHT_GOVERNANCE` (25)
- `SCORE_EXCELLENT` (80), `SCORE_GOOD` (60), `SCORE_MODERATE` (40), `SCORE_POOR` (20)
- `DEFAULT_FRAMEWORK`, `REPORTING_YEAR`, `PLATFORM_NAME`

---

## 16. Hierarchy System (Consulting Org Tree)

**Class:** `Hierarchy` · **Column:** `users.parent_id`

```
Principal
  └── Associate(s)
        └── Manager(s)
              └── Companies (via user_companies)
```

- `Hierarchy::descendantIds()` — BFS traversal of the `parent_id` tree to get all descendants.
- **Portfolio dashboards** per role: principal sees full org tree analytics; associate sees sub-team; manager sees own companies.
- `getPortfolioSummary()` — all accessible companies with per-company ESG scores + critical gap count.
- `getAssociatesWithStats()` / `getManagersWithStats()` — org-level team performance stats.

---

## 17. Company Onboarding

**File:** `pages/onboarding.php`

**Steps:**
1. Company name + optional registration number
2. Industry, revenue tier, employee count
3. Framework selection (BURSA_SEDG default)
4. Reporting year
5. Submit → auto-assign as owner → auto-create 6 departments → redirect to dashboard

**Shown to:** New `sme_owner` / `consultant` users with no company linked.

---

## 18. Company Settings

**File:** `pages/company_settings.php`

- Basic: name, registration_no, industry, employee count, revenue tier, pre-IPO flag
- Bursa-specific: sector (13 Bursa sectors), reporting scope (HQ / factory / group), report level
- Framework: switch framework, change reporting year
- Access: owner/editor via `user_companies`; viewers cannot edit

**Activity log:** `FRAMEWORK_CHANGED` when framework is updated.

---

## 19. Billing

**File:** `pages/billing.php`

- Shows active plan + source (subscription / 14-day trial / free).
- Non-admin users see upgrade prompt → logs `UPGRADE_REQUESTED` → support follow-up.
- **Admin controls** (visible only to `admin` role):
  - Activate plan: select plan code + duration in months → `Subscription::activatePlan()`
  - Activate collection add-on: select collection + duration → `Subscription::activateCollection()`

---

## 20. Export

**File:** `pages/export_csv.php`

| Export | Format | Columns |
|---|---|---|
| ESG Data | CSV (UTF-8 BOM) | Indicator ID/Code/Name/Category/Framework/Required/Unit/Type/Value/Source/Notes/Verified/Period/Updated |
| Action Plans | CSV | ID/Title/Priority/Status/Assigned/Dept/Indicator/Description/Recommendation/Due/Completed/Created |
| KPI Trends | CSV | Year/Month/Overall/E/S/G/Scope1/2/3/Completion%/Filled/Total/Snapshot timestamp |
| PDF Reports | Browser print-to-PDF | Full HTML report from ReportGenerator (print-optimised CSS) |

---

## 21. Public & Legal Pages

| Page | Route | Purpose |
|---|---|---|
| Landing | `/` | Marketing — hero, quiz, features, frameworks, FAQ, pricing CTA |
| Pricing | `/pricing` | 3-tier plan comparison |
| Login | `/login` | Email + password auth |
| Register | `/register` | Account creation |
| Privacy Policy | `/privacy` | 11-section PDPA-compliant privacy disclosure |
| PDPA Notice | `/pdpa` | Malaysia PDPA 2010 (Act 709) compliance |
| Terms of Use | `/terms` | Platform usage terms, pricing, liability |
| Cookie Policy | `/cookies` | Session cookie disclosure (strictly necessary only) |

All public pages use `Auth::startSession()` + `Auth::check()` (not `auth_check.php`), so they are accessible without login. Logo with `onerror` fallback to icon+text across all pages.

---

## 22. Database Schema (17 Tables)

| Table | Purpose |
|---|---|
| `users` | 6-role user accounts with hierarchy `parent_id` |
| `companies` | Multi-tenant company profiles |
| `user_companies` | Company–user access junction (owner/editor/viewer) |
| `esg_data` | All ESG indicator values, unique per (company, indicator, period) |
| `subscriptions` | Plan activations with expiry |
| `company_collections` | Collection add-on activations |
| `reports` | Generated HTML reports |
| `gap_analyses` | Cached gap analysis JSON |
| `activity_log` | Full platform audit trail |
| `platform_settings` | Admin-editable key-value config |
| `departments` | Company department definitions |
| `department_users` | Department membership with role |
| `notifications` | User notification feed |
| `action_plans` | Gap-linked or manual tasks |
| `action_plan_comments` | Action plan collaboration comments |
| `indicator_comments` | Per-indicator notes during data entry |
| `monthly_kpi_snapshots` | Monthly ESG + carbon + completion snapshots |

---

## 23. Key Configuration Files

| File | Controls |
|---|---|
| `config/app.php` | APP_NAME, APP_URL, timezone, ESG weights, score thresholds, session lifetime |
| `config/database.php` | DB host, name, user, password, charset |
| `config/frameworks.php` | All 10 framework definitions with metadata |
| `config/plans.php` | 3 subscription tiers with feature lists and indicator_ids |
| `config/collections.php` | Indicator collection add-on definitions |
| `config/indicators/{key}.php` | Per-framework indicator arrays (E/S/G categories) |
| `config/bursa_sectors.php` | 13 Bursa Malaysia sector options |

---

## 24. Cross-Cutting Concerns

| Concern | Implementation |
|---|---|
| **Security** | CSRF tokens on all forms, bcrypt passwords, session auth, PDO prepared statements |
| **Audit Trail** | `activity_log` records all significant actions with user, company, IP, timestamp |
| **Plan Gating** | `$unlockedIds` checked before rendering any indicator; locked indicators shown with upgrade prompt |
| **Framework Switching** | Company can switch frameworks at any time; existing esg_data is preserved under old framework code |
| **Malaysia-Specific** | MyGHG 2023 emission factors, Bursa SEDG indicators, 13 Bursa sectors, PDPA Act 709, BNM/CGC/SME Corp financing programs |
| **No Build System** | All CSS inline in page files; all JS inline in script blocks; no Composer/npm |

---

*Generated from codebase audit · ESG gen v1 · Adcellent Biz Sdn Bhd (1511714-V) · 2026*
