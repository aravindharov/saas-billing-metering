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

## Phase 2 — Plans & Pricing

| # | Decision | Reasoning |
|---|----------|-----------|
| 1 | **Money as integer minor units** | `base_price` and `overage_rate` are stored as `UNSIGNED BIGINT` representing paise (₹1.00 = 100 paise). No floats anywhere in the money path. |
| 2 | **Soft-delete via status** | `DELETE /plans/{id}` sets status to `archived` instead of physically deleting. Plans may be referenced by future subscriptions/invoices. |
| 3 | **Plan name uniqueness per merchant** | `UNIQUE(merchant_id, name)` — different merchants can have plans with the same name. |
| 4 | **Owner-only mutations** | Only owners can create, update, or archive plans. Members have read-only access. Implemented via Laravel Policy. |
| 5 | **Tenant-scoped route model binding** | `Plan::resolveRouteBinding` scopes queries to the authenticated merchant. Cross-tenant requests receive 404 (not 403), preventing information leakage. |
| 6 | **Middleware priority** | `ResolveMerchant` middleware runs before `SubstituteBindings` so that route model binding can scope to the resolved tenant. |
| 7 | **Pricing snapshots deferred** | Editing a plan's price changes it immediately. When subscriptions are introduced, a pricing snapshot mechanism will preserve the price applicable at subscription time. |
| 8 | **Cache scoped by merchant_id** | Cache keys include `merchant_id` to guarantee tenant isolation. Keys are invalidated on every write (create, update, archive). |
| 9 | **No billing calculations** | This phase establishes pricing data only. Billing, invoicing, and overage calculations belong to future phases. |

## Phase 3 — Customers

| # | Decision | Reasoning |
|---|----------|-----------|
| 1 | **Soft-delete via status** | `DELETE /customers/{id}` sets status to `inactive` instead of physically deleting. Customers will be referenced by future subscriptions, usage, and invoices. |
| 2 | **External reference optional, unique per merchant** | `UNIQUE(merchant_id, external_reference)` with nullable — allows merchants to link customers to external CRM/account systems. Different merchants can use the same reference. |
| 3 | **Email not unique** | Multiple customers under the same merchant can share an email address. Uniqueness is enforced on `external_reference` instead. |
| 4 | **Search via LIKE queries** | Simple `LIKE %search%` on name/email/external_reference. Sufficient for the current scale. A full-text search engine can be added later if needed. |
| 5 | **No subscriptions or billing** | Customers are standalone entities in this phase. Subscription assignment belongs to Phase 4. |
| 6 | **Tenant-scoped route model binding** | Same pattern as Plan — `Customer::resolveRouteBinding` scopes to the authenticated merchant. Cross-tenant → 404. |
| 7 | **Indexes for query patterns** | `(merchant_id, status)` for filtered listing, `(merchant_id, email)` for email lookups, `UNIQUE(merchant_id, external_reference)` for reference lookups. |

## Phase 4 — Subscriptions & Plan Changes

| # | Decision | Reasoning |
|---|----------|-----------|
| 1 | **Pricing snapshot at subscription time** | Plan pricing is copied into the subscription row at creation. Editing the plan later does not affect existing subscriptions. This ensures billing integrity. |
| 2 | **Plan change history with full from/to pricing** | `subscription_plan_changes` records preserve the exact pricing before and after each change. This supports future proration calculations without relying on plan edit history. |
| 3 | **One active subscription per customer** | Enforced via DB query + row lock. A customer must cancel before subscribing to a new plan. Simplifies billing and avoids conflicting active subscriptions. |
| 4 | **Row-level locks for concurrency** | `lockForUpdate()` on customer (for creation) and subscription (for changes/cancellation) prevents race conditions in concurrent requests. |
| 5 | **Calendar-aware periods** | `addMonth()` / `addYear()` rather than fixed 30/365 days. January 31 → February 28 is handled by Carbon. |
| 6 | **No proration calculation** | Plan changes update the pricing snapshot immediately. Actual proration (billing calculation) is deferred to a future phase. The historical pricing segments support it. |
| 7 | **No period renewal** | Period renewal (advancing `current_period_start`/`current_period_end`) will be implemented with billing in a future phase. |
| 8 | **Cancelled subscriptions keep their data** | Cancellation sets status and timestamp but preserves all pricing and period data for historical reference. |
| 9 | **Effective timestamp on plan changes** | `effective_at` records the exact moment of each plan change, enabling precise proration windows. |
| 10 | **Subscription belongs to plan (FK)** | The `plan_id` on a subscription tracks the current plan. Historical plan references are preserved in `subscription_plan_changes`. |
| 11 | **No usage/billing/invoicing** | This phase creates the subscription lifecycle only. Usage ingestion, aggregation, overage calculation, and invoicing belong to future phases. |

## Phase 5 — Usage Event Ingestion

| # | Decision | Reasoning |
|---|----------|-----------|
| 1 | **Client-generated `event_id` as idempotency key** | The client owns the uniqueness domain. `UNIQUE(merchant_id, event_id)` at the database level is the final protection, even under concurrent requests. Application-level existence checks are a fast path only. |
| 2 | **Quantity as positive integer** | The assignment describes usage in "units." Integer representation avoids floating-point errors. Minimum 1, maximum 1,000,000 as a safety bound. |
| 3 | **`occurred_at` is client-provided, stored in UTC** | The occurrence timestamp belongs to the client's domain. The server normalizes to UTC. This timestamp determines billing period and aggregation date, not `created_at`. |
| 4 | **Late events accepted** | Usage may arrive after the fact (network delays, batch uploads). Rejecting valid historical events would lose data. Future aggregation must recompute affected periods when late events arrive. |
| 5 | **Future events rejected beyond 5-minute tolerance** | Prevents clearly erroneous data (year 2030 timestamps). The 5-minute window accommodates clock skew between client and server. |
| 6 | **No synchronous aggregation** | The ingestion endpoint must be fast. Aggregation, totaling, billing, and overage calculations are deferred to Phase 6 (async queue). The raw event is the durable fact. |
| 7 | **Immutable events** | Raw usage events are the source of truth. No PUT/PATCH/DELETE endpoints. If corrections are needed, compensating events are the future approach. This preserves audit integrity. |
| 8 | **Both owners and members can ingest** | Usage ingestion is expected to be machine-to-machine. Restricting to owners would prevent automated systems using member tokens from recording usage. |
| 9 | **500 requests/minute per merchant** | High enough for production bursts, low enough to protect the database. Scoped by `merchant_id` so one merchant's traffic cannot starve another. |
| 10 | **No partitioning yet** | Current composite indexes handle expected scale. Partitioning by `occurred_at` month is documented as the scaling path but not implemented because it would require changing the idempotency constraint to include the partition key. |
| 11 | **Subscription validation at ingest time** | The subscription must be active and belong to the customer. However, `occurred_at` may predate the current subscription period (late events). Billing-period matching is deferred to the aggregation/billing phase. |
| 12 | **No event type/category dimension** | The assignment describes generic "usage units." Adding event types would complicate the schema without clear requirements. Can be added later if needed. |
| 13 | **`UniqueConstraintViolationException` catch for race conditions** | Two concurrent requests with the same `event_id` can both pass the existence check. The INSERT's unique constraint violation is caught and the existing record returned, guaranteeing exactly-once semantics. |

## Phase 6 — Daily Usage Aggregation

| # | Decision | Reasoning |
|---|----------|-----------|
| 1 | **`daily_usage` is a derived read model** | Raw `usage_events` remain the sole source of truth. Aggregates can be dropped and rebuilt via `usage:aggregate` without data loss. |
| 2 | **Recalculate with SUM, never increment** | Queue retries must not use `+=`. Each job run queries `SUM(quantity)` and SETs `total_quantity`, making aggregation idempotent. |
| 3 | **UTC calendar day from `occurred_at`** | Usage date uses `[day_start, day_start + 1 day)` in UTC. `created_at` is ignored for bucketing. |
| 4 | **Upsert via database** | `DB::table()->upsert()` with unique key `(merchant_id, customer_id, usage_date)` handles concurrent jobs safely. |
| 5 | **No row for zero usage** | Avoids millions of zero rows. When SUM is 0, delete any stale aggregate row. |
| 6 | **Job dispatched after commit** | Ensures the worker never aggregates an event that rolled back. Only new inserts dispatch; idempotent retries do not. |
| 7 | **Rebuild uses SQL GROUP BY + chunk** | `usage:aggregate` never loads all events into PHP. Distinct merchant/customer/date tuples are chunked (500) and one job dispatched per tuple. |
| 8 | **No public_id on daily_usage** | Internal read model; API exposes customer public_id and usage_date only. |
| 9 | **API date range capped at 366 days** | Prevents unbounded read queries. Default listing window is 31 days when no filter is provided. |
| 10 | **No billing in aggregation** | This phase sums quantities only. Pricing, overage, and invoices belong to later phases. |
| 11 | **Partitioning still deferred** | Same rationale as Phase 5 — indexes + async aggregation suffice until operational metrics justify partition complexity. |

## Phase 7 — Billing & Invoices

| # | Decision | Reasoning |
|---|----------|-----------|
| 1 | **Integer paise (minor units)** | `base_price`, `overage_rate`, invoice `subtotal`/`total`, and line amounts are unsigned integers in paise (₹1.00 = 100). Overage is `billable_units × overage_rate` with no floating point. |
| 2 | **Billing read model** | Invoice generation sums `daily_usage.total_quantity` per customer and UTC date range. Raw `usage_events` are not scanned per invoice; rebuild aggregation if totals are wrong. |
| 3 | **Pricing segments** | `BillingSegmentBuilder` splits `[current_period_start, current_period_end)` at each in-period `subscription_plan_changes.effective_at`. Pricing at segment start comes from plan-change history (`to_*` after a change, `from_*` before the first in-period change, else subscription snapshot). Live `plans` prices are never used for historical invoices. |
| 4 | **Per-segment overage** | `billable = max(0, segment_usage − included_usage_units)`; segment usage is independent (no pooling across plan segments). |
| 5 | **Time-based proration (base only)** | `prorated_base = intdiv(base × segment_seconds + intdiv(period_seconds, 2), period_seconds)` — round half up at the minor-unit step. Full-period segments charge the full snapshot base. |
| 6 | **UTC boundaries** | Billing period timestamps and daily usage dates are interpreted in UTC. |
| 7 | **Single currency** | No multi-currency model; one merchant currency is assumed (INR / paise in examples). |
| 8 | **Invoice lifecycle** | Invoices are created and immediately **issued** (`status = issued`, `issued_at` set). No payment status. Issued invoices are immutable (no update/delete API). |
| 9 | **Duplicate protection** | `UNIQUE(subscription_id, billing_period_start, billing_period_end)` plus transactional create and idempotent `GenerateInvoice` (returns existing row on retry). |
| 10 | **Chunked cycle-end billing** | `billing:generate-invoices` uses `chunkById(100)` and dispatches `GenerateSubscriptionInvoice` per subscription; skips rows that already have an invoice for the current period. |
| 11 | **Authorization** | `InvoicePolicy`: owners and members may list/show invoices (read-only). Generation is server-side only (action/job/command). |
| 12 | **No payments** | Payment gateway, refunds, credit notes, taxes, and dunning are out of scope. |

### Worked example (assignment)

Plan: base ₹500 (50_000 paise), included 1_000 units, overage ₹2/unit (200 paise). Usage 1_500 in one period, no plan change:

- Base line: 50_000  
- Billable: 500 → overage 500 × 200 = 100_000  
- **Total: 150_000 paise (₹1_500)**
