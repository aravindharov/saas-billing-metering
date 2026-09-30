# Subscription Billing & Usage-Metering System

A multi-tenant SaaS backend that supports merchants, plans, customer
subscriptions, usage-event ingestion, aggregation, and invoice generation.

> **Current phase: 6 — Daily Usage Aggregation**

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

Cycle-end invoices are generated server-side (`GenerateInvoice`, `billing:generate-invoices`). The API is read-only.

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

### ✅ Implemented (Phase 5 — Usage Event Ingestion)

- [x] UsageEvent model with ULID public IDs, tenant scoping
- [x] `POST /api/v1/usage` — ingest usage event (any authenticated user)
- [x] Client-generated `event_id` as idempotency key
- [x] `UNIQUE(merchant_id, event_id)` database constraint — final authority against duplicates
- [x] Idempotent retry: duplicate `event_id` returns existing event (HTTP 200), no duplicate record
- [x] Concurrent duplicate protection via `UniqueConstraintViolationException` catch
- [x] Customer validation: must exist, belong to merchant, be active
- [x] Subscription validation: must exist, belong to merchant and customer, be active
- [x] UTC timestamp storage, ISO-8601 input, timezone normalization
- [x] Late event support: historical `occurred_at` accepted
- [x] Future event rejection: > 5 minutes clock-skew tolerance
- [x] Quantity: positive integer, 1–1,000,000
- [x] Merchant-scoped rate limiting: 500 requests/minute per merchant (HTTP 429)
- [x] Immutable events: no PUT/PATCH/DELETE endpoints
- [x] Lightweight write path: no synchronous aggregation or billing
- [x] Indexes optimized for high write volume and future aggregation queries
- [x] UsageEventPolicy for authorization
- [x] Database factory with merchant/customer/subscription/historical states
- [x] Development seeder (5 sample usage events for John Smith)
- [x] 38 backend tests across 5 test files
- [x] 50L+ scaling strategy documented

### ✅ Implemented (Phase 6 — Daily Usage Aggregation)

- [x] `daily_usage` table with `UNIQUE(merchant_id, customer_id, usage_date)`
- [x] DailyUsage model (internal read model, no public API id)
- [x] `AggregateDailyUsage` queued job — SUM from raw events, upsert SET total
- [x] Idempotent aggregation — retries and concurrent jobs cannot double-count
- [x] UTC usage date from `occurred_at` (half-open day window)
- [x] Late events recompute the affected UTC date only
- [x] Job dispatched after commit on new usage events only
- [x] `php artisan usage:aggregate --from --to` rebuild with chunked GROUP BY
- [x] `GET /api/v1/usage/daily` — date, range (max 366 days), customer filter, pagination
- [x] Tenant isolation via MerchantContext
- [x] Vue Daily Usage page (date filter, table, pagination)
- [x] 30+ backend tests (aggregation, idempotency, late events, API, command)
- [x] 50L+ architecture documentation updated

### ✅ Implemented (Phase 7 — Billing & Invoices)

- [x] `invoices` and `invoice_lines` tables (integer minor units, unique subscription + billing period)
- [x] `BillingSegmentBuilder` + `BillingCalculator` (segments, proration, per-segment overage from `daily_usage`)
- [x] `GenerateInvoice` action (transactional, idempotent, issued immediately)
- [x] `GenerateSubscriptionInvoice` queued job + `billing:generate-invoices` command (`chunkById`)
- [x] Historical pricing via subscription snapshot and `subscription_plan_changes` (not live plan prices)
- [x] `GET /api/v1/invoices`, `GET /api/v1/invoices/{invoice}` — filters, pagination, tenant scope
- [x] `InvoicePolicy` (read-only for owners and members)
- [x] Vue invoice list and detail pages
- [x] 17+ backend tests (calculator, generation, API, command, idempotency, tenant isolation)
- [x] Billing formula, proration, and rounding documented in `docs/assumptions.md`

**Billing formula (per segment):**  
`total = Σ prorated_base + Σ max(0, usage − included) × overage_rate`  
**Proration:** `intdiv(base × segment_seconds + period_seconds/2, period_seconds)` (round half up).

### 🔲 Planned

- [ ] Rate limiting on ingestion endpoints
- [ ] Merchant dashboard
- [ ] Caching layer for dashboard
- [ ] Payment processing & credit notes (out of assignment scope)
