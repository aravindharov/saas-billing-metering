# Subscription Billing & Usage-Metering System

A multi-tenant SaaS backend that supports merchants, plans, customer
subscriptions, usage-event ingestion, aggregation, and invoice generation.

> **Phase 10 — Final assignment review & submission materials**

## Overview

Multi-tenant SaaS backend: merchants define **plans**, **customers** subscribe with **pricing snapshots**, **usage events** are ingested at volume with idempotency, **daily usage** is aggregated asynchronously, **invoices** are generated from segments (proration + overage), and a **merchant dashboard** reads the daily read model.

**Detailed docs:** [Architecture](docs/architecture.md) · [Assumptions](docs/assumptions.md) · [Assignment audit](docs/assignment-checklist.md) · [Demo script](docs/demo-script.md) · [Final review](docs/final-review.md) · [AI prompts](prompts/)

## Core domain model

```
Plans → Subscription (pricing snapshot) → Plan-change history
Usage events (append-only) → Daily usage (read model)
Daily usage + segments → Billing calculator → Invoices
Daily usage → Dashboard analytics
```

## Features

- Sanctum auth with merchant tenancy (owner / member roles)
- Plans, customers, subscriptions (create, change plan, cancel)
- Usage ingest (`POST /api/v1/usage`), daily usage API and UI
- Billing engine, invoices (API, UI, artisan command, owner generate button)
- Dashboard: top 5 MTD usage, projected overage, >50% MoM drop
- Redis plan cache (merchant-scoped), rate limits, security regression tests

## API endpoints (summary)

All paths prefixed with `/api/v1`. Protected routes require `Authorization: Bearer <token>` and tenant middleware.

| Area | Method | Path |
|------|--------|------|
| Health | GET | `/api/health` |
| Auth | POST | `/auth/login`, `/auth/logout` |
| Auth | GET | `/auth/me` |
| Plans | * | `/plans`, `/plans/{plan}` (REST) |
| Customers | * | `/customers`, `/customers/{customer}` (REST) |
| Subscriptions | GET, POST | `/subscriptions`, `/subscriptions/{subscription}` |
| Subscriptions | POST | `/subscriptions/{subscription}/change-plan`, `/cancel`, `/generate-invoice` |
| Usage | POST | `/usage` |
| Usage | GET | `/usage/daily` |
| Invoices | GET | `/invoices`, `/invoices/{invoice}` |
| Dashboard | GET | `/merchants/{merchant}/dashboard` |

Request/response examples for auth, plans, customers, subscriptions, usage, and invoices are documented below in this README.

## Verification (local)

Last run via `make verify` on branch `phase-10-final-review`:

| Check | Result |
|-------|--------|
| Pint | PASS (176 files) |
| Larastan | No errors |
| vue-tsc | PASS |
| ESLint / Prettier | PASS |
| PHPUnit | **333 passed** (789 assertions) |
| Vitest | **26 passed** (11 files) |
| Vite production build | PASS |

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

# Run database migrations and demo seed data
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed

# Process queued aggregation/invoice jobs (or set QUEUE_CONNECTION=sync in .env)
docker compose up -d queue

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

# Rebuild daily usage for a UTC date range (dispatches queue jobs)
docker compose exec app php artisan usage:aggregate --from=2026-09-01 --to=2026-09-30

# Queue invoice generation for ended billing periods
docker compose exec app php artisan billing:generate-invoices
# Optional: --before=2026-10-01T00:00:00Z
```

**Aggregation:** Required after bulk imports or to repair late events; idempotent per `(merchant, customer, usage_date)`.  
**Invoices:** Skips periods that already have an invoice; jobs are idempotent. Ensure `queue` is running unless using `sync`.

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
# The `queue` Docker service runs `queue:work` in a restart loop (see docker-compose.yml).
# Ensure it is up:
docker compose up -d queue

# To process jobs manually (one-off):
docker compose exec app php artisan queue:work redis --queue=high,default

# Jobs run inline without a worker (simple local dev only):
# QUEUE_CONNECTION=sync in .env

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

## Usage Ingestion API

### `POST /api/v1/usage`

Record a usage event. Requires `Authorization: Bearer <token>`. Any authenticated user (owner or member) may ingest usage. Rate limited to 500 requests/minute per merchant.

**Request:**
```json
{
    "event_id": "evt_10001",
    "customer_id": "01J...",
    "subscription_id": "01J...",
    "quantity": 25,
    "occurred_at": "2026-09-29T14:30:00Z"
}
```

**Response (201 — new event):**
```json
{
    "data": {
        "id": "01J...",
        "event_id": "evt_10001",
        "customer": { "id": "01J...", "name": "John Smith" },
        "subscription": { "id": "01J..." },
        "quantity": 25,
        "occurred_at": "2026-09-29T14:30:00+00:00",
        "created_at": "2026-09-29T15:00:00+00:00"
    }
}
```

**Response (200 — idempotent retry):** Returns the existing event unchanged. No duplicate record is created.

**Validation:**
- `event_id` — required, string, max 255, unique per merchant (idempotency key)
- `customer_id` — required, must belong to merchant, must be active
- `subscription_id` — required, must belong to merchant and customer, must be active
- `quantity` — required, integer, 1–1,000,000
- `occurred_at` — required, ISO-8601, stored in UTC, must not be > 5 minutes in the future

**Errors:** `401` unauthorized, `422` validation, `429` rate limited.

**No update or delete endpoints exist.** Usage events are immutable source-of-truth records.

---

## Usage Event Design

### Event Schema

| Field | Type | Description |
|-------|------|-------------|
| `event_id` | string | Client-generated idempotency key, unique per merchant |
| `customer_id` | FK | Customer who generated the usage |
| `subscription_id` | FK | Active subscription at time of usage |
| `quantity` | unsigned int | Usage units consumed (always positive integer) |
| `occurred_at` | datetime (UTC) | When the usage actually occurred |

### Idempotency

The `UNIQUE(merchant_id, event_id)` database constraint is the **final authority** against duplicates. The flow is:

1. Check if `event_id` already exists for the merchant (fast path).
2. If yes → return existing event with HTTP 200.
3. If no → attempt INSERT.
4. If `UniqueConstraintViolationException` (concurrent race) → return existing event with HTTP 200.

Clients can safely retry after network timeouts, connection resets, or temporary errors. The same `event_id` always resolves to the same event.

### Timestamp Handling

- All timestamps are stored in UTC regardless of the input timezone.
- `occurred_at` is the client-provided occurrence time, not the server receipt time.
- Events with `occurred_at` up to 5 minutes in the future are accepted (clock-skew tolerance).
- Events further in the future are rejected.

### Late Events

Late-arriving usage events are accepted. If `occurred_at` is in the past (e.g., days or weeks ago), the event is still recorded as long as the customer and subscription are currently valid. Future aggregation phases must be capable of recomputing affected daily totals when late events arrive.

### Immutability

Raw usage events are **never edited or deleted** through the API. They are the source of truth for all downstream billing and aggregation. If incorrect usage must be corrected, a compensating event (negative or correction) would be the future approach rather than mutating historical data.

### Rate Limiting

| Scope | Limit | Window |
|-------|-------|--------|
| Per merchant | 500 requests | 1 minute |

Scoped by `merchant_id` so one merchant's traffic cannot consume another's quota. Returns HTTP 429 with `Retry-After` header when exceeded.

### Indexing Strategy

| Index | Purpose | Write Impact |
|-------|---------|-------------|
| `UNIQUE(merchant_id, event_id)` | Idempotency enforcement | Moderate — checked on every insert |
| `(customer_id, occurred_at)` | Per-customer usage lookups & future aggregation | Low — append-oriented |
| `(subscription_id, occurred_at)` | Per-subscription billing queries | Low — append-oriented |
| `(merchant_id, occurred_at)` | Merchant-wide usage queries & dashboard | Low — append-oriented |

All date-composite indexes are append-oriented: new events go to the end of the B-tree leaf chain, minimizing page splits and keeping write amplification low.

### Queue Boundary

After a new usage event is committed, `AggregateDailyUsage` is dispatched **after commit** with the merchant, customer, and UTC usage date derived from `occurred_at`. Idempotent ingestion retries do not dispatch duplicate jobs. The HTTP ingestion path never performs synchronous aggregation.

---

## Daily Usage Aggregation

`usage_events` is the **source of truth**. `daily_usage` is a **derived read model** that can be deleted and rebuilt entirely from raw events.

### Schema

| Field | Type | Description |
|-------|------|-------------|
| `merchant_id` | FK | Tenant boundary |
| `customer_id` | FK | Customer whose usage was aggregated |
| `usage_date` | date (UTC) | Calendar day derived from `occurred_at`, not `created_at` |
| `total_quantity` | unsigned bigint | `SUM(quantity)` for that merchant/customer/day |

**Constraint:** `UNIQUE(merchant_id, customer_id, usage_date)` — one row per customer per UTC day.

**Indexes:** The unique constraint covers customer/day lookups. An additional `(merchant_id, usage_date)` index supports merchant-wide date-range queries.

**No row = no usage** — zero-quantity days are not stored.

### Aggregation Algorithm (Idempotent)

For each `(merchant_id, customer_id, usage_date)`:

1. UTC day start = `usage_date 00:00:00`
2. UTC day end = start + 1 day (exclusive)
3. `total = SUM(quantity)` from `usage_events` in that window
4. If `total > 0`: upsert `daily_usage` with `total_quantity = total` (SET, never `+=`)
5. If `total = 0`: delete any existing `daily_usage` row for that key

Running the same job twice, retrying after failure, or running concurrent jobs for the same key always yields the same total — no double-counting.

### Queue Architecture

```
POST /api/v1/usage (Phase 5)
        ↓
usage_events persisted
        ↓
AggregateDailyUsage dispatched (after commit)
        ↓
Laravel queue worker
        ↓
daily_usage upsert
```

### Historical Rebuild

```bash
php artisan usage:aggregate --from=2026-09-01 --to=2026-09-30
```

Finds distinct `(merchant_id, customer_id, DATE(occurred_at))` combinations in the range using database-side `GROUP BY`, dispatches one job per combination in chunks of 500. Safe to run repeatedly.

### Daily Usage API

`GET /api/v1/usage/daily` — read-only, tenant-scoped, paginated (50/page).

| Query param | Description |
|-------------|-------------|
| `date` | Exact UTC usage date (`Y-m-d`) |
| `from` / `to` | Inclusive range (max 366 days) |
| `customer_id` | Filter by customer public ID |

Default (no filters): last 31 days only — prevents unbounded queries.

### Authorization

Both owners and members may view daily usage (`viewDailyUsage` on `UsageEventPolicy`), consistent with Phase 5 ingest permissions.

---

## Phase 7 — Billing & Invoices

Cycle-end invoices are generated server-side (`GenerateInvoice`, `billing:generate-invoices`). Owners can also trigger generation from the subscription detail UI or via `POST /api/v1/subscriptions/{subscription}/generate-invoice` (idempotent, ended period only).

### Invoice API

`GET /api/v1/invoices` — paginated (15/page), tenant-scoped.

| Query param | Description |
|-------------|-------------|
| `status` | `draft` or `issued` |
| `customer_id` | Customer public ID |
| `subscription_id` | Subscription public ID |
| `period_from` / `period_to` | Filter on billing period dates (`Y-m-d`) |

`GET /api/v1/invoices/{invoice}` — invoice with nested `lines` (types `base`, `overage`).

### Billing engine

1. Determine pricing segments from `subscription_plan_changes` within `[current_period_start, current_period_end)`.
2. Sum `daily_usage` per segment (UTC dates, half-open `[segment_start, segment_end)`).
3. Prorate base charge per segment; compute overage per segment independently.
4. Persist invoice + lines in one transaction; duplicate periods rejected by DB unique index.

See `docs/assumptions.md` (Phase 7) for formulas and a worked ₹1_500 example.

---

## Phase 8 — Merchant Dashboard

`GET /api/v1/merchants/{merchant}/dashboard` — `{merchant}` must be the authenticated tenant’s public id (cross-tenant → 404).

| Section | Source |
|---------|--------|
| Top customers | `SUM(daily_usage)` for UTC month-to-date, limit 5 |
| Projected overage | Active subscriptions, pricing segments, projected usage rate |
| Usage drops | MoM month-to-date compare, threshold strictly >50% |

See `docs/assumptions.md` (Phase 8) for projection and comparison formulas.

---

## Scaling Usage Events Beyond 50L Rows

The architecture separates **write-optimized ingestion** from **read-optimized aggregation**:

```
Raw Usage Events (append-only, indexed)
        ↓
Asynchronous Queue
        ↓
Chunked AggregateDailyUsage jobs
        ↓
daily_usage (rebuildable read model)
        ↓
Dashboard / Billing reads
```

### Current Implementation

- **Append-only writes** — no UPDATE/DELETE, minimizing lock contention.
- **Composite indexes** — optimized for the expected query patterns without over-indexing.
- **Idempotency at the database level** — `UNIQUE(merchant_id, event_id)` prevents duplicates without application-level locks.
- **Lightweight ingestion** — aggregation runs asynchronously via queued jobs.
- **Rebuildable read model** — `daily_usage` is disposable; `usage:aggregate` recomputes from raw events.
- **Database-side grouping** — rebuild command never loads all raw events into PHP memory.

### When to Scale Further

| Threshold | Action |
|-----------|--------|
| ~10L rows | Current indexes + async aggregation sufficient |
| ~50L rows | Consider MySQL `RANGE` partitioning by `occurred_at` month |
| ~100L+ rows | Evaluate time-series storage, archive cold partitions, or shard by merchant |

### Partitioning Strategy (Not Yet Implemented)

MySQL `RANGE` partitioning by `occurred_at` month would:
- Keep hot partitions (current + recent months) small and fast
- Allow efficient partition pruning for date-range queries
- Enable `ALTER TABLE DROP PARTITION` for archival

**Not implemented now** because: (1) the current index set handles the expected scale; (2) partitioning requires all unique constraints to include the partition key, which would change the idempotency constraint from `UNIQUE(merchant_id, event_id)` to `UNIQUE(merchant_id, event_id, occurred_at)` — adding complexity for a scale problem that doesn't yet exist.

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

Subscriptions and invoices use **pricing snapshots** on the subscription row and **plan-change history** — editing a plan does not alter existing subscriptions or past segments.

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

### Usage Permissions

| Action | Owner | Member |
|--------|-------|--------|
| Ingest usage event | ✅ | ✅ |

Both owners and members can ingest usage because this endpoint is intended for machine-to-machine calls from the merchant's backend systems. Implemented via `UsageEventPolicy`.

| Action | Owner | Member |
|--------|-------|--------|
| View daily usage | ✅ | ✅ |

Implemented via `UsageEventPolicy::viewDailyUsage`.

---

## Architecture Principles

- **Thin controllers** — Business logic lives in Actions/Services, not controllers.
- **Server-side validation** — Form Requests validate all input.
- **Money as integers** — All monetary values use integer minor units with explicit currency.
- **Multi-tenant** — Every query is scoped to the authenticated merchant.
- **UTC timestamps** — All dates stored in UTC; display formatting at the edge.
- **Idempotency** — Usage `event_id` and invoice period uniqueness enforced in the database.
- **No secrets in code** — All credentials live in environment variables.

---

## AI-assisted development

- Implementation was done **phase-by-phase** with structured prompts (see [`prompts/`](prompts/)).
- Generated code was **reviewed** against assignment scope; business rules live in Actions/Services with PHPUnit coverage.
- **Static analysis** (Pint, Larastan, vue-tsc, ESLint) runs locally and in CI.
- **Tenant isolation** and authorization were checked explicitly (feature + security tests).
- Architectural trade-offs (50L+ pipeline, partitioning deferral, money as integers) are documented in [`docs/architecture.md`](docs/architecture.md) and [`docs/assumptions.md`](docs/assumptions.md).

Prompt log format: markdown transcripts (`PROMPT.md` / `FOLLOWUPS.md` per phase), not fabricated artifacts.

---

## Assignment checklist

Full audit with evidence links: **[docs/assignment-checklist.md](docs/assignment-checklist.md)**.

Summary:

| Requirement | Status |
|-------------|--------|
| Plans, customers, subscriptions | ✅ |
| Usage + idempotency + rate limits | ✅ |
| Daily aggregation + rebuild | ✅ |
| Billing, invoices, dashboard | ✅ |
| 50L+ / performance strategy | ✅ |
| Tests + CI quality gates | ✅ |
| Documentation + demo | ✅ |
| AI prompt log | ✅ [`prompts/`](prompts/) |

**Out of scope:** payments, taxes, refunds, dunning, partitioning implementation (documented only).

**Demo walkthrough:** [docs/demo-script.md](docs/demo-script.md) · **Submission notes:** [docs/final-review.md](docs/final-review.md)
