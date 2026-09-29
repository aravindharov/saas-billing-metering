# Subscription Billing & Usage-Metering System

A multi-tenant SaaS backend that supports merchants, plans, customer
subscriptions, usage-event ingestion, aggregation, and invoice generation.

> **Current phase: 4 — Subscriptions & Plan Changes**

---

## Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | 13.x |
| Language | PHP | 8.3+ |
| Database | MySQL | 8.4 |
| Cache / Queue | Redis | 7.4 |
| Auth | Laravel Sanctum | 4.x |
| Frontend | Vue 3 + TypeScript | 3.5.x |
| Build | Vite | 8.x |
| CSS | Tailwind CSS | 4.x |
| Backend tests | PHPUnit | 12.x |
| Frontend tests | Vitest | 5.x |
| Static analysis | Larastan | 3.x |
| Style | Pint, ESLint, Prettier | — |
| Infrastructure | Docker Compose | — |
| CI | GitHub Actions | — |

---

## Local Setup

### Prerequisites

- Docker & Docker Compose
- Git

### Quick Start

```bash
# Clone the repository
git clone <repo-url> && cd saas-billing-metering

# Copy environment file
cp .env.example .env

# Start all services
docker compose up -d

# Generate application key
docker compose exec app php artisan key:generate

# Run database migrations
docker compose exec app php artisan migrate

# (Optional) Start the Vite dev server
docker compose --profile frontend up -d vite
```

The application is available at **http://localhost:8000**.
The Vite dev server (if started) runs at **http://localhost:5173**.

---

## Docker Services

| Service | Port | Purpose |
|---------|------|---------|
| `app` | 8000 | Laravel HTTP server |
| `queue` | — | Redis queue worker |
| `scheduler` | — | Laravel task scheduler |
| `vite` | 5173 | Vite dev server (frontend profile) |
| `mysql` | 3306 | MySQL 8.4 database |
| `redis` | 6379 | Redis 7.4 cache/queue |

### Commands

```bash
docker compose up -d          # Start services
docker compose down           # Stop services
docker compose logs -f        # Tail logs
docker compose build          # Rebuild images
```

---

## Development Credentials

After running `docker compose exec app php artisan db:seed`, the following
test accounts are available:

| Merchant Slug | Email | Password | Role |
|---------------|-------|----------|------|
| `acme` | `owner@acme.test` | `password` | owner |
| `acme` | `member@acme.test` | `password` | member |

> ⚠️ These are **development-only** credentials. Never use them in production.

---

## Environment Configuration

Copy `.env.example` to `.env` and adjust as needed. Key variables:

| Variable | Default | Purpose |
|----------|---------|---------|
| `DB_CONNECTION` | `mysql` | Database driver |
| `DB_HOST` | `mysql` | Database host (Docker service name) |
| `DB_DATABASE` | `saas_billing` | Database name |
| `CACHE_STORE` | `redis` | Cache backend |
| `QUEUE_CONNECTION` | `redis` | Queue backend |
| `REDIS_HOST` | `redis` | Redis host (Docker service name) |

---

## Backend Commands

```bash
# Run all tests
docker compose exec app php artisan test

# Run unit tests only
docker compose exec app php artisan test --testsuite=Unit

# Run feature tests only
docker compose exec app php artisan test --testsuite=Feature

# PHP style check
docker compose exec app ./vendor/bin/pint --test

# PHP style fix
docker compose exec app ./vendor/bin/pint

# Static analysis
docker compose exec app composer analyse

# Run migrations
docker compose exec app php artisan migrate

# Tinker (REPL)
docker compose exec app php artisan tinker
```

---

## Frontend Commands

```bash
# Dev server
docker compose exec app npm run dev

# Production build
docker compose exec app npm run build

# Type check
docker compose exec app npm run typecheck

# Lint
docker compose exec app npm run lint

# Format check
docker compose exec app npm run format:check

# Run tests
docker compose exec app npm run test

# Run tests with coverage
docker compose exec app npm run test:coverage
```

---

## Queue Commands

```bash
# The queue worker runs automatically as the `queue` Docker service.
# To process jobs manually:
docker compose exec app php artisan queue:work redis --queue=high,default

# Monitor failed jobs
docker compose exec app php artisan queue:failed
```

---

## Testing

### Backend (PHPUnit)

Tests run against a dedicated `saas_billing_test` MySQL database, created
automatically by the Docker init script.

### Frontend (Vitest)

Tests run in a jsdom environment with Vue Test Utils.

### Makefile shortcuts

```bash
make test            # Run all tests (backend + frontend)
make test-backend    # Backend only
make test-frontend   # Frontend only
make check           # Quality checks (lint, analyse, typecheck)
make verify          # Full verification (checks + tests + build)
```

---

## CI Overview

GitHub Actions runs two workflows:

1. **CI** — Lint (Pint, ESLint, Prettier), static analysis (Larastan), type
   check (vue-tsc), and production build.
2. **Tests** — Backend tests (PHPUnit with MySQL + Redis) and frontend tests
   (Vitest).

---

## Authentication API

### `POST /api/v1/auth/login`

Authenticate a user within a merchant context.

**Request:**
```json
{
    "merchant": "acme",
    "email": "owner@acme.test",
    "password": "password"
}
```

**Response (200):**
```json
{
    "user": { "id": "01J...", "name": "Acme Owner", "email": "owner@acme.test", "role": "owner" },
    "merchant": { "id": "01J...", "name": "Acme Corporation", "slug": "acme" },
    "token": "1|abc..."
}
```

**Errors:** `401` invalid credentials, `422` validation errors, `429` rate limited.

### `POST /api/v1/auth/logout`

Revoke the current authentication token. Requires `Authorization: Bearer <token>`.

**Response (200):** `{ "message": "Logged out." }`

### `GET /api/v1/auth/me`

Return the current authenticated user and merchant. Requires `Authorization: Bearer <token>`.

**Response (200):**
```json
{
    "user": { "id": "01J...", "name": "Acme Owner", "email": "owner@acme.test", "role": "owner" },
    "merchant": { "id": "01J...", "name": "Acme Corporation", "slug": "acme" }
}
```

---

## Plans API

All plan endpoints require `Authorization: Bearer <token>` and operate within the authenticated merchant's tenant scope.

### `POST /api/v1/plans`

Create a new plan. **Owner only.**

**Request:**
```json
{
    "name": "Professional",
    "base_price": 49900,
    "billing_cycle": "monthly",
    "included_usage_units": 10000,
    "overage_rate": 5
}
```

**Response (201):**
```json
{
    "data": {
        "id": "01J...",
        "name": "Professional",
        "base_price": 49900,
        "billing_cycle": "monthly",
        "included_usage_units": 10000,
        "overage_rate": 5,
        "status": "active",
        "created_at": "2026-09-29T00:00:00+00:00",
        "updated_at": "2026-09-29T00:00:00+00:00"
    }
}
```

**Validation:** name required/unique per merchant/max 255, base_price integer ≥ 0, billing_cycle `monthly`|`yearly`, included_usage_units integer ≥ 0, overage_rate integer ≥ 0. Client-supplied `merchant_id` and `status` are ignored.

### `GET /api/v1/plans`

List paginated plans for the authenticated merchant. Supports `?status=active|archived` filter.

### `GET /api/v1/plans/{plan}`

Show a single plan. Returns 404 for plans belonging to other merchants (no information leakage).

### `PUT /api/v1/plans/{plan}`

Update a plan. **Owner only.** Supports partial updates.

### `DELETE /api/v1/plans/{plan}`

Archive a plan (soft-delete). **Owner only.** The plan is set to `archived` status and remains in the database for historical reference.

---

## Customers API

All customer endpoints require `Authorization: Bearer <token>` and operate within the authenticated merchant's tenant scope.

### `POST /api/v1/customers`

Create a new customer. **Owner only.**

**Request:**
```json
{
    "name": "John Smith",
    "email": "john@example.com",
    "external_reference": "CRM-10001"
}
```

**Response (201):**
```json
{
    "data": {
        "id": "01J...",
        "name": "John Smith",
        "email": "john@example.com",
        "external_reference": "CRM-10001",
        "status": "active",
        "created_at": "2026-09-29T00:00:00+00:00",
        "updated_at": "2026-09-29T00:00:00+00:00"
    }
}
```

**Validation:** name required/max 255, email required/valid/max 255, external_reference optional/max 255/unique per merchant. Client-supplied `merchant_id` and `status` are ignored.

### `GET /api/v1/customers`

List paginated customers for the authenticated merchant. Supports `?status=active|inactive` filter and `?search=` for name/email/external reference.

### `GET /api/v1/customers/{customer}`

Show a single customer. Returns 404 for customers belonging to other merchants (no information leakage).

### `PUT /api/v1/customers/{customer}`

Update a customer. **Owner only.** Supports partial updates.

### `DELETE /api/v1/customers/{customer}`

Deactivate a customer (soft-delete). **Owner only.** The customer is set to `inactive` status and remains in the database for historical reference by subscriptions/invoices.

---

## Subscriptions API

All subscription endpoints require `Authorization: Bearer <token>` and operate within the authenticated merchant's tenant scope.

### `POST /api/v1/subscriptions`

Create a new subscription. **Owner only.** Snapshots the plan's current pricing into the subscription at creation time.

**Request:**
```json
{
    "customer_id": "01J...",
    "plan_id": "01J..."
}
```

**Response (201):**
```json
{
    "data": {
        "id": "01J...",
        "customer": { "id": "01J...", "name": "John Smith" },
        "plan": { "id": "01J...", "name": "Starter" },
        "status": "active",
        "billing_cycle": "monthly",
        "base_price": 9900,
        "included_usage_units": 1000,
        "overage_rate": 5,
        "started_at": "2026-09-29T00:00:00+00:00",
        "current_period_start": "2026-09-29T00:00:00+00:00",
        "current_period_end": "2026-10-29T00:00:00+00:00",
        "cancelled_at": null,
        "plan_changes": [],
        "created_at": "2026-09-29T00:00:00+00:00",
        "updated_at": "2026-09-29T00:00:00+00:00"
    }
}
```

**Validation:** customer_id required/must belong to merchant/must be active, plan_id required/must belong to merchant/must be active. One active subscription per customer enforced.

### `GET /api/v1/subscriptions`

List paginated subscriptions. Supports `?status=active|cancelled|expired`, `?customer_id=<public_id>`, `?plan_id=<public_id>` filters.

### `GET /api/v1/subscriptions/{subscription}`

Show a subscription with plan change history. Returns 404 for subscriptions belonging to other merchants.

### `POST /api/v1/subscriptions/{subscription}/change-plan`

Change the subscription's plan. **Owner only.** Creates a plan-change history record preserving from/to pricing snapshots, then updates the subscription's pricing snapshot.

**Request:**
```json
{
    "plan_id": "01J..."
}
```

**Response (200):**
```json
{
    "data": {
        "id": "01J...",
        "from_plan": { "id": "01J...", "name": "Starter" },
        "to_plan": { "id": "01J...", "name": "Professional" },
        "effective_at": "2026-09-29T12:00:00+00:00",
        "from_base_price": 9900,
        "from_included_usage_units": 1000,
        "from_overage_rate": 5,
        "from_billing_cycle": "monthly",
        "to_base_price": 49900,
        "to_included_usage_units": 10000,
        "to_overage_rate": 3,
        "to_billing_cycle": "monthly"
    }
}
```

### `POST /api/v1/subscriptions/{subscription}/cancel`

Cancel an active subscription. **Owner only.** Sets status to `cancelled` and records the cancellation timestamp.

**Response (200):** Returns the updated subscription resource with `status: "cancelled"` and `cancelled_at` timestamp.

---

## Subscription Design

### Pricing Snapshots

When a subscription is created, the plan's current pricing is **copied** into the subscription row:

| Subscription Field | Source |
|-------------------|--------|
| `base_price` | `plan.base_price` at subscription time |
| `included_usage_units` | `plan.included_usage_units` at subscription time |
| `overage_rate` | `plan.overage_rate` at subscription time |
| `billing_cycle` | `plan.billing_cycle` at subscription time |

Editing the plan's price later does **not** retroactively change existing subscriptions.

### Plan Change History

Each plan change creates a `subscription_plan_changes` record with full from/to pricing:

```
subscription_plan_changes
├── from_plan_id, from_base_price, from_included_usage_units, from_overage_rate, from_billing_cycle
├── to_plan_id, to_base_price, to_included_usage_units, to_overage_rate, to_billing_cycle
└── effective_at (timestamp of the change)
```

This allows future proration calculations to reconstruct exact pricing at any point in a billing period.

### Billing Periods

- **Monthly:** `current_period_end = current_period_start + 1 month` (calendar-aware via `addMonth()`)
- **Yearly:** `current_period_end = current_period_start + 1 year` (calendar-aware via `addYear()`)

### Concurrency

- Subscription creation uses `lockForUpdate()` on the customer row to prevent duplicate active subscriptions
- Plan changes use `lockForUpdate()` on the subscription row to prevent simultaneous modifications
- Cancellation uses `lockForUpdate()` on the subscription row

### Lifecycle

```
active → cancelled (via cancel endpoint)
active → expired (future: when period ends without renewal)
```

One active subscription per customer. After cancellation, a new subscription can be created.

---

## Customer Schema

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Customer display name |
| `email` | string | Customer email address |
| `external_reference` | string (nullable) | Merchant's external CRM/account ID. Unique per merchant. |
| `status` | enum | `active` or `inactive` |

### Customer Lifecycle

```
active → inactive (via DELETE endpoint)
```

Inactive customers remain in the database. They will be referenced by future subscriptions, usage records, and invoices. Physical deletion is never performed.

---

## Plan Schema & Money Representation

### Money as Integer Minor Units

All monetary values are stored as **integer minor units** (paise for INR):

| Display Value | Stored Value | Field |
|---------------|-------------|-------|
| ₹499.00 | `49900` | `base_price` |
| ₹0.05 | `5` | `overage_rate` |

This avoids floating-point precision errors. No float arithmetic is used for monetary calculations.

### Plan Fields

| Field | Type | Description |
|-------|------|-------------|
| `base_price` | integer | Monthly/yearly price in minor units (paise) |
| `billing_cycle` | enum | `monthly` or `yearly` |
| `included_usage_units` | integer | Usage units included in the base price |
| `overage_rate` | integer | Cost per additional usage unit in minor units |
| `status` | enum | `active` (available for subscriptions) or `archived` (historical only) |

### Plan Lifecycle

```
active → archived (via DELETE endpoint)
```

Archived plans remain in the database for historical references. They are not available for creating new subscriptions (enforced in future phases).

### Pricing History Note

When subscriptions are implemented (future phase), the subscription/invoice must preserve the pricing applicable at the time of subscription or plan change. The current plan price may be edited freely; a future subscription/pricing snapshot mechanism will ensure historical billing integrity.

---

## Cache Strategy

### Plan Caching

Plan lookups are cached in Redis to reduce database queries.

**Cache key structure:**
- Single plan: `plans:{merchant_id}:{plan_public_id}`
- Plan list: `plans:{merchant_id}:list`

**Invalidation triggers:**
| Event | Invalidated Keys |
|-------|-----------------|
| Plan created | `plans:{merchant_id}:list` |
| Plan updated | `plans:{merchant_id}:{plan_public_id}` + `plans:{merchant_id}:list` |
| Plan archived | `plans:{merchant_id}:{plan_public_id}` + `plans:{merchant_id}:list` |

**Merchant isolation:** Cache keys include `merchant_id`, ensuring Merchant A never receives cached data from Merchant B.

**TTL:** 1 hour (3600 seconds). Explicit invalidation on writes ensures consistency; the TTL is a safety net.

---

## Authorization

### Plan Permissions

| Action | Owner | Member |
|--------|-------|--------|
| List plans | ✅ | ✅ |
| View plan | ✅ | ✅ |
| Create plan | ✅ | ❌ |
| Update plan | ✅ | ❌ |
| Archive plan | ✅ | ❌ |

Implemented via Laravel Policy (`PlanPolicy`). The backend is the security authority; frontend permission checks are for UX only.

### Customer Permissions

| Action | Owner | Member |
|--------|-------|--------|
| List customers | ✅ | ✅ |
| View customer | ✅ | ✅ |
| Create customer | ✅ | ❌ |
| Update customer | ✅ | ❌ |
| Deactivate customer | ✅ | ❌ |

Implemented via Laravel Policy (`CustomerPolicy`).

### Subscription Permissions

| Action | Owner | Member |
|--------|-------|--------|
| List subscriptions | ✅ | ✅ |
| View subscription | ✅ | ✅ |
| Create subscription | ✅ | ❌ |
| Change plan | ✅ | ❌ |
| Cancel subscription | ✅ | ❌ |

Implemented via Laravel Policy (`SubscriptionPolicy`).

---

## Architecture Principles

- **Thin controllers** — Business logic lives in Actions/Services, not controllers.
- **Server-side validation** — Form Requests validate all input.
- **Money as integers** — All monetary values use integer minor units with explicit currency.
- **Multi-tenant** — Every query is scoped to the authenticated merchant.
- **UTC timestamps** — All dates stored in UTC; display formatting at the edge.
- **Idempotency** — Critical write operations will use idempotency keys.
- **No secrets in code** — All credentials live in environment variables.

---

## Implementation Status

### ✅ Implemented (Phase 0 — Foundation)

- [x] Fresh Laravel 13 project
- [x] Docker Compose (app, queue, scheduler, MySQL, Redis, Vite)
- [x] MySQL 8.4 with test database
- [x] Redis 7.4 for cache and queues
- [x] Health check endpoint (`GET /api/health`)
- [x] SPA shell (Vue 3 + TypeScript + Tailwind)
- [x] PHPUnit + Vitest test infrastructure
- [x] Larastan, Pint, ESLint, Prettier
- [x] GitHub Actions CI
- [x] Makefile shortcuts

### ✅ Implemented (Phase 1 — Authentication & Tenancy)

- [x] Merchant model with ULID public IDs, slug, status
- [x] User model with merchant relationship, role (owner/member)
- [x] Per-merchant email uniqueness (tenant-local identity)
- [x] Laravel Sanctum token authentication
- [x] `POST /api/v1/auth/login` — authenticate by merchant slug + email + password
- [x] `POST /api/v1/auth/logout` — revoke current token
- [x] `GET /api/v1/auth/me` — current user + merchant context
- [x] `MerchantContext` — tenant resolution from authenticated identity
- [x] `ResolveMerchant` middleware — enforces tenant context on protected routes
- [x] Opaque auth error responses (no credential leakage)
- [x] Suspended merchant blocking
- [x] Login rate limiting (5 attempts/minute)
- [x] Vue login page with merchant/email/password form
- [x] Auth composable with reactive state management
- [x] Vue Router guards (protected routes + guest routes)
- [x] Authenticated layout with merchant name, user, role, logout
- [x] Database factories (Merchant, User with owner/member states)
- [x] Development seeder (Acme Corporation with owner + member)
- [x] 49 backend tests (121 assertions)
- [x] 9 frontend tests

### ✅ Implemented (Phase 2 — Plans & Pricing)

- [x] Plan model with ULID public IDs, merchant relationship
- [x] BillingCycle enum (monthly, yearly)
- [x] PlanStatus enum (active, archived)
- [x] `POST /api/v1/plans` — create plan (owner only)
- [x] `GET /api/v1/plans` — list plans with pagination and status filter
- [x] `GET /api/v1/plans/{plan}` — show plan
- [x] `PUT /api/v1/plans/{plan}` — update plan (owner only)
- [x] `DELETE /api/v1/plans/{plan}` — archive plan (owner only, soft-delete)
- [x] Money as integer minor units (base_price, overage_rate)
- [x] Plan name uniqueness per merchant
- [x] PlanPolicy for role-based authorization (owner=full, member=read-only)
- [x] Tenant-scoped route model binding (cross-tenant → 404, no info leakage)
- [x] Plan caching with Redis (merchant-scoped keys, invalidation on write)
- [x] Vue Plans list page with pagination, status filter, archive action
- [x] Vue Plan create/edit form page
- [x] Nav link in authenticated layout
- [x] Database factory with active/archived/monthly/yearly states
- [x] Development seeder (Starter, Professional, Enterprise plans)
- [x] 57 new backend tests
- [x] 5 new frontend tests

### ✅ Implemented (Phase 3 — Customers)

- [x] Customer model with ULID public IDs, merchant relationship
- [x] CustomerStatus enum (active, inactive)
- [x] `POST /api/v1/customers` — create customer (owner only)
- [x] `GET /api/v1/customers` — list with pagination, status filter, search
- [x] `GET /api/v1/customers/{customer}` — show customer
- [x] `PUT /api/v1/customers/{customer}` — update customer (owner only)
- [x] `DELETE /api/v1/customers/{customer}` — deactivate customer (owner only, soft-delete)
- [x] External reference (optional, unique per merchant)
- [x] Search by name, email, external reference
- [x] CustomerPolicy for role-based authorization (owner=full, member=read-only)
- [x] Tenant-scoped route model binding (cross-tenant → 404)
- [x] Vue Customers list page with pagination, search, status filter
- [x] Vue Customer create/edit form page
- [x] Nav link in authenticated layout
- [x] Database factory with active/inactive/withExternalReference states
- [x] Development seeder (John Smith, Jane Doe, Bob Wilson)
- [x] 49 new backend tests
- [x] 5 new frontend tests

### ✅ Implemented (Phase 4 — Subscriptions & Plan Changes)

- [x] Subscription model with ULID public IDs, tenant scoping
- [x] SubscriptionStatus enum (active, cancelled, expired)
- [x] Pricing snapshot — plan pricing copied into subscription at creation time
- [x] `POST /api/v1/subscriptions` — create subscription (owner only)
- [x] `GET /api/v1/subscriptions` — list with pagination, status/customer/plan filters
- [x] `GET /api/v1/subscriptions/{subscription}` — show with plan change history
- [x] `POST /api/v1/subscriptions/{subscription}/change-plan` — mid-cycle plan change (owner only)
- [x] `POST /api/v1/subscriptions/{subscription}/cancel` — cancel subscription (owner only)
- [x] Plan change history with from/to pricing snapshots (SubscriptionPlanChange model)
- [x] One active subscription per customer constraint
- [x] DB transactions with row-level locks for concurrency protection
- [x] Calendar-aware billing periods (addMonth/addYear)
- [x] SubscriptionPolicy for role-based authorization
- [x] Tenant-scoped route model binding (cross-tenant → 404)
- [x] Vue Subscriptions list page with pagination and status filter
- [x] Vue Subscription detail page with plan change history
- [x] Vue Create Subscription page with customer/plan selectors
- [x] Nav link in authenticated layout
- [x] Database factories with cancelled/expired/yearly states
- [x] Development seeder (John Smith subscribed to Starter)
- [x] Backend tests: create, list, show, change plan, cancel, model, tenant isolation
- [x] Frontend tests

### 🔲 Planned

- [ ] Usage event ingestion (high-volume, idempotent)
- [ ] Usage aggregation (queued, chunked)
- [ ] Billing & invoice generation
- [ ] Proration for plan changes
- [ ] Rate limiting on ingestion endpoints
- [ ] Merchant dashboard
- [ ] Caching layer for dashboard
