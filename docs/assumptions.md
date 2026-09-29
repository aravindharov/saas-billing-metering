# Assumptions & Trade-offs

This document records architectural decisions and assumptions that a future
reader might question.

## Phase 0 — Foundation

| # | Decision | Reasoning |
|---|----------|-----------|
| 1 | **MySQL 8.4** as the primary datastore | The assignment specifies MySQL. Version 8.4 is the current LTS. |
| 2 | **Redis 7.4** for cache, queues, and rate-limiting | Redis is the standard Laravel companion for these concerns. A single Redis instance is sufficient for local dev; production would use separate instances or clusters. |
| 3 | **Sanctum** for API authentication | Token-based auth fits the multi-tenant SaaS model. Sanctum is first-party and well-integrated with Laravel. |
| 4 | **Single Docker image** for all PHP responsibilities | Keeps the dev environment simple. Production would use separate images for web, queue, and scheduler. |
| 5 | **SQLite not used** for testing | MySQL-specific features (strict mode, transactions, charset) are part of the contract. Tests run against MySQL to catch incompatibilities early. |
| 6 | **Money as integer minor units** | Per the assignment. All monetary values will be stored and transmitted as integers representing the smallest currency unit (e.g., cents). |
| 7 | **UTC everywhere** | All timestamps are stored in UTC. Display formatting happens at the frontend edge. |
| 8 | **Vue 3 SPA** with server-side API | The frontend is a Vue 3 SPA served by a catch-all blade template. API-first design keeps the frontend decoupled from the backend. |

## Phase 1 — Authentication & Tenancy

| # | Decision | Reasoning |
|---|----------|-----------|
| 1 | **Merchant is the tenant boundary** | Every business entity belongs to a merchant. All queries are scoped to the authenticated merchant. |
| 2 | **User belongs to exactly one merchant** | A user is always part of one merchant. The same email can exist in different merchants (tenant-local identity). |
| 3 | **Email uniqueness is per-merchant** | `UNIQUE(merchant_id, email)` — the same person can work for multiple organizations. This is standard for multi-tenant SaaS. |
| 4 | **Tenant context from authenticated identity only** | The `MerchantContext` is always derived from the authenticated user's `merchant_id`. Client-supplied `merchant_id` is never trusted for authorization. |
| 5 | **Owner/Member roles** | Simple two-role model. Owner has full access; member is a standard user. Avoids premature RBAC complexity. |
| 6 | **ULIDs for public IDs** | Sequential database IDs are never exposed in the API. ULID public IDs are sortable, unique, and do not leak record counts. |
| 7 | **Login requires merchant slug** | Authentication is scoped to a merchant — the login form requires organization + email + password. This prevents cross-tenant credential leakage. |
| 8 | **Opaque auth failures** | All credential failures return the same message. The API never reveals whether the merchant, email, or password was wrong. |
| 9 | **Token abilities deferred** | Initial tokens carry no specific abilities. Future phases may introduce scoped abilities (e.g., read-only tokens, machine tokens). |
| 10 | **Frontend auth is UX only** | Vue Router guards protect routes for user experience. The API independently authenticates and authorizes every request. |
