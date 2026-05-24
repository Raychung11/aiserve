# eBizMedic — Healthcare Operating Platform

## Overview
B2B2C healthcare platform connecting Organisations, Patients, Doctors, Dispensaries, and Mobile Medic Providers. Built on Next.js 14 (App Router), TypeScript, Tailwind CSS, and PostgreSQL via Prisma.

## Stack
- **Frontend/Backend**: Next.js 14 App Router (full-stack)
- **Language**: TypeScript (strict)
- **Styling**: Tailwind CSS
- **Database**: PostgreSQL via Prisma ORM
- **Auth**: Custom JWT (jose) stored in httpOnly cookie
- **Video**: Zoom Server-to-Server OAuth
- **Queues**: BullMQ + Redis (future)
- **Storage**: AWS S3 / Cloudflare R2 (future — recording backups)

## User Roles
| Role | Path | Description |
|------|------|-------------|
| SUPER_ADMIN | /admin | Full platform access |
| ORG_ADMIN | /organisation | Manages their org, staff, wallet |
| PATIENT | /patient | Books consultations, orders marketplace |
| DOCTOR | /doctor | Manages schedule, conducts consultations |
| DISPENSARY_STAFF | /dispensary | Manages products and fulfills orders |
| MOBILE_MEDIC | /medic | Handles home visit assignments |

## Auth Flow
1. POST `/api/auth/login` → validates credentials, returns JWT in httpOnly `auth_token` cookie
2. Middleware (`src/middleware.ts`) validates JWT on every request, injects `x-user-id`, `x-user-role` headers
3. Server components call `getCurrentUser()` / `requireAuth()` from `src/lib/auth.ts`
4. Client components fetch `/api/auth/me`

## Key Architecture Decisions
- **Wallet**: Immutable ledger with row-level PostgreSQL locking (`FOR UPDATE`) to prevent race conditions. Never edit balance directly — always go through `WalletService`.
- **Booking**: `BookingService.createBooking()` uses a database transaction to atomically check conflicts and create the appointment + Zoom meeting.
- **Zoom**: 1-license MVP. `ZoomHost.currentLoad` tracks active sessions; decremented on `meeting.ended` webhook. Expand to host pool by adding more `ZoomHost` rows.
- **Recording**: Zoom webhook `recording.completed` stores metadata in `ConsultationRecording`. Backup to S3 is a future background job.

## Directory Structure
```
ebizmedic/
├── prisma/
│   ├── schema.prisma          # Full DB schema (20+ models)
│   └── seed.ts                # Demo data seeder
├── src/
│   ├── app/
│   │   ├── (auth)/            # Login, Register, Reset Password
│   │   ├── (dashboard)/       # Role-based dashboards
│   │   │   ├── admin/
│   │   │   ├── organisation/
│   │   │   ├── doctor/
│   │   │   ├── patient/
│   │   │   ├── dispensary/
│   │   │   └── medic/
│   │   └── api/               # REST API routes
│   ├── components/
│   │   ├── ui/                # Button, Badge, Card, Input
│   │   ├── layout/            # Sidebar, Header
│   │   └── dashboard/         # StatsCard
│   ├── lib/
│   │   ├── auth.ts            # getCurrentUser, requireAuth
│   │   ├── db.ts              # Prisma singleton
│   │   ├── jwt.ts             # signToken, verifyToken
│   │   ├── zoom.ts            # Zoom API helpers
│   │   ├── utils.ts           # cn, formatCurrency, ok/fail
│   │   └── validations.ts     # Zod schemas
│   ├── middleware.ts           # JWT validation + route guard
│   ├── services/
│   │   ├── wallet.service.ts  # Ledger-safe wallet operations
│   │   ├── booking.service.ts # Conflict-safe booking engine
│   │   ├── notification.service.ts
│   │   └── audit.service.ts
│   └── types/
│       ├── index.ts           # Shared TypeScript types
│       └── roles.ts           # Permissions matrix, nav items
└── .env.example
```

## Setup
```bash
cp .env.example .env
# Fill in DATABASE_URL, JWT_SECRET, ZOOM_* credentials

npm install
npm run db:migrate
npm run db:generate
npm run db:seed
npm run dev
```

## API Route Map
| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| POST | /api/auth/login | Public | Authenticate user |
| POST | /api/auth/logout | Public | Clear session cookie |
| GET | /api/auth/me | Auth | Get current user |
| POST | /api/auth/register | Public | Create account |
| GET | /api/organisations | SUPER_ADMIN | List orgs |
| POST | /api/organisations | SUPER_ADMIN | Create org + admin |
| GET/PATCH/DELETE | /api/organisations/[id] | Admin/OrgAdmin | Org CRUD |
| GET/POST | /api/wallet/[orgId] | OrgAdmin | Wallet balance + top-up |
| GET | /api/doctors | Auth | List doctors |
| GET/PUT | /api/doctors/[id]/schedule | Doctor | Get/set schedule |
| GET | /api/doctors/[id]/slots | Auth | Available time slots |
| GET/POST | /api/bookings | Auth | List/create bookings |
| GET/PATCH | /api/bookings/[id] | Auth | Booking detail + actions |
| GET/POST | /api/marketplace/products | Auth | Product catalog |
| POST | /api/webhooks/zoom | Zoom | Recording + meeting events |

## Phase 1 Status (MVP)
- [x] Auth & RBAC
- [x] Organisation onboarding
- [x] Organisation wallet (ledger model)
- [x] Doctor directory + schedule
- [x] Booking engine (conflict prevention)
- [x] Zoom integration (1-license model)
- [x] Recording metadata via webhook
- [x] Marketplace product catalog
- [x] Dispensary order management
- [x] Role-based dashboards (all 6 roles)
- [x] Notification service
- [x] Audit logging

## Phase 2 (Next)
- [ ] Mobile medic assignment flow
- [ ] Full prescription → dispensary order pipeline
- [ ] Email/SMS notification delivery (BullMQ)
- [ ] Recording backup to S3/R2
- [ ] Multi-Zoom host pool
- [ ] Organisation staff management UI
- [ ] Rich analytics/reports

## Phase 3 (Future)
- [ ] Mobile app (React Native)
- [ ] AI health assistant
- [ ] Insurance integration
- [ ] Payment gateway
- [ ] Regional scaling
