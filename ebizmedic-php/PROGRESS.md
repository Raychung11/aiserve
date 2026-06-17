# eBizMedic — Development Progress

Native PHP 8+ · Tailwind CSS CDN · MySQL · Hostinger Shared Hosting

---

## Phase 5 — Online Consultation + Bug Fixes (2026-05-17)

### Online Consultation Module
- **Pre-join Lobby** (`consultation/lobby`) — participant cards, live countdown timer, system checklist (camera/mic/browser), role badge (Doctor / Patient), Join button
- **Video Room** (`consultation/room`) — Jitsi Meet via IFrame API (`external_api.js`), custom display name injected via `userInfo`, no "Fellow Jitster" ghost window
- **Live Chat sidebar** — collapsible panel, 4-second message polling, optimistic send for snappy UX
- **Screen Recording** — `MediaRecorder` + `getDisplayMedia()`, Record button in room toolbar, auto-downloads `.webm` on stop
- **Call Timer** — live HH:MM:SS counter shown in top bar
- **End Call flow** — confirm dialog; doctor redirected to create medical record; patient redirected to appointments
- **New DB table** — `consultation_messages (id, appointment_id, sender_id, message, created_at)`
- **New routes** — `GET consultation/lobby`, `GET consultation/room`, `GET consultation/messages`, `POST consultation/chat`, `POST consultation/end`
- **Dashboard banners** — green "Online Patient Ready" (medic) / "Online Consultation Ready" (patient) cards appear on dashboard when a today-online-confirmed appointment exists
- **Join Now buttons** — pulsing green button on both medic and patient appointments lists for today's online confirmed appointments

### Bug Fixes
- **Jitsi 3rd window** — switched from raw `<iframe src>` to Jitsi IFrame API; display name now set reliably via JavaScript API
- **CSRF mismatch** — `csrf_verify()` updated to accept both `_csrf` and `_token` field names
- **Lobby unreachable** — seed updated with 2 today-online confirmed appointments (Ali/Dr.Sarah +30 min, Siti/Dr.Ahmad +90 min)
- **Medic avatar undefined** — MedicController constructor query fixed to include `u.phone, u.avatar`

---

## Phase 5 — Profile Photo Fix (2026-05-17)

### Problem
Profile photos uploaded by doctors and patients were not displaying anywhere — the platform always showed the initial-letter fallback.

### Root Causes & Fixes

| # | Root Cause | Fix |
|---|-----------|-----|
| 1 | `Auth::login()` only stored `id/name/role` in session — `avatar` was never saved | Now stores `$_SESSION['user_avatar']` |
| 2 | `Auth::user()` did not return `avatar` | Now returns `'avatar' => $_SESSION['user_avatar'] ?? null` |
| 3 | `updatePhoto()` saved to DB but didn't update session | Added `Auth::refreshAvatar($path)` static method; all 3 `updatePhoto()` controllers now call it |
| 4 | All views hard-coded initial-letter divs even when `$doc['avatar']` was available | Updated all views to show `<img>` when avatar is set, fallback to initials |

### Files Changed
- `src/Auth.php` — `login()`, `user()`, new `refreshAvatar()`
- `controllers/UserController.php` — `updatePhoto()` calls `Auth::refreshAvatar()`
- `controllers/MedicController.php` — `updatePhoto()` calls `Auth::refreshAvatar()`
- `controllers/OrganisationController.php` — `updatePhoto()` calls `Auth::refreshAvatar()`
- `views/layouts/app.php` — sidebar shows photo or initials
- `views/home/doctors.php` — doctor cards show photo or initials
- `views/home/doctor_detail.php` — doctor profile header shows photo or initials
- `views/home/index.php` — featured doctors carousel shows photo or initials

---

## Phase 4 — Trust & Engagement (prior session)

- **Patient Health Profiles** — blood type, allergies, chronic conditions, current medications, emergency contact; visible to doctor when creating medical record
- **Doctor Ratings & Reviews** — 5-star picker on doctor detail page; Rate button on completed appointments; avg rating + count on doctor listing; UNIQUE per appointment
- **In-app Notifications** — bell icon with unread badge in header, dropdown preview of last 5, full notifications page; `notify()` helper fires on appointment changes, record creation, dispensing, and account approval
- **Printable Invoices** — role-gated invoice view per dispensing with `@media print` CSS
- New DB: `health_profiles`, `ratings`, `notifications`
- New helpers: `notify()`, `stars()`

---

## Phase 3 — Dispensary Module (prior session)

- **Pharmacist role** — dashboard, dispense form (JS line-item builder), medicine inventory, dispensing history
- **Organisation dispensary** — add/edit medicines, stock-in, stock adjustments, movement log, pharmacist staff management
- **Cross-role views** — admin platform overview + low-stock alerts; medic sees patient dispensings; patient sees own medicine history
- New DB: `medicines`, `pharmacists`, `dispensings`, `dispensing_items`, `stock_movements`

---

## Phase 2 — Medical Records & Doctor Tools (prior session)

- Medical records CRUD (doctor creates, patient views)
- Photo upload for all roles
- Time-slot picker on booking form
- Password change on profile pages
- Admin approvals for medic/organisation accounts

---

## Phase 1 — Core MVP (prior session)

Native PHP rewrite for Hostinger shared hosting (no framework, no Composer).

- **Auth** — session-based, role guards (`Auth::requireRole()`), CSRF protection
- **5 roles** — admin, medic, organisation, user (patient), pharmacist
- **Front controller** — `index.php` flat route table, PDO MySQL singleton
- **Booking flow** — find doctor → select slot → confirm → appointment management
- **Dashboards** — role-specific dashboards for all 5 roles
- **Layouts** — `layouts/app.php` (portal), `layouts/minimal.php` (full-screen consultation room)
- **Landing page** — interactive with typewriter hero, service tabs, doctor carousel, step animations, FAQ accordion, scroll-reveal (IntersectionObserver)

---

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ebizmedic.com | Admin@123456 |
| Doctor | sarah.lee@doctor.com | Doctor@123 |
| Doctor | ahmad.razif@doctor.com | Doctor@123 |
| Patient | ali@patient.com | User@123 |
| Patient | lim@patient.com | User@123 |
| Organisation | org@kliniksehat.com | Org@123456 |
| Pharmacist | pharma@kliniksehat.com | Pharma@123 |

## Setup

```bash
# 1. Import schema
mysql -u root -p ebizmedic < database/schema.sql

# 2. Run migrations
mysql -u root -p ebizmedic < database/migrate_v3.sql
mysql -u root -p ebizmedic < database/migrate_v4.sql
mysql -u root -p ebizmedic < database/migrate_v5.sql

# 3. Seed demo data
php database/seed.php
```

## Stack

- PHP 8.2+ (native, no framework)
- MySQL 8 (PDO, singleton)
- Tailwind CSS via CDN
- Font Awesome 6 via CDN
- Jitsi Meet IFrame API (free, no API key)
- Hostinger shared hosting compatible
