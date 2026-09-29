# Subscription Billing & Usage-Metering System

A multi-tenant SaaS backend that supports merchants, plans, customer
subscriptions, usage-event ingestion, aggregation, and invoice generation.

> **Current phase: 1 — Authentication & Tenancy**

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

### 🔲 Planned

- [ ] Plans & pricing
- [ ] Customer subscriptions
- [ ] Usage event ingestion (high-volume, idempotent)
- [ ] Usage aggregation (queued, chunked)
- [ ] Billing & invoice generation
- [ ] Proration for plan changes
- [ ] Rate limiting on ingestion endpoints
- [ ] Merchant dashboard
- [ ] Caching layer for dashboard
