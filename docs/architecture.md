# System Architecture

Multi-tenant SaaS billing and usage metering. All business data is scoped to a **merchant**; the authenticated user's `merchant_id` is the sole source of tenant context (`MerchantContext`).

## Data flow

```
Merchant
  ↓
Users (Sanctum tokens)
  ↓
Customers
  ↓
Plans (catalog) ──pricing snapshot──→ Subscriptions
  ↓                                      ↓
                              subscription_plan_changes (historical segments)
Usage POST /api/v1/usage
  ↓
usage_events (append-only, idempotent)
  ↓
AggregateDailyUsage (queued, after commit)
  ↓
daily_usage (derived read model)
  ↓
BillingCalculator → Invoices / invoice_lines
  ↓
DashboardService (analytics)
```

Money is always **integer paise** (₹1.00 = 100). Timestamps are stored in **UTC**.

## Tenancy & security

| Mechanism | Behavior |
|-----------|----------|
| Login | Requires merchant slug + email + password |
| API auth | Sanctum bearer token on protected routes |
| Tenant middleware | Resolves `MerchantContext` from authenticated user only |
| Route model binding | `Plan`, `Customer`, `Subscription`, `Invoice`, `Merchant` scope to resolved merchant → cross-tenant **404** |
| Policies | Owner vs member (write vs read-only per domain) |
| Mass assignment | `merchant_id` is not accepted from client payloads on create/update flows |

Production: set `APP_DEBUG=false` so validation errors never expose stack traces or SQL.

## Usage ingestion (high volume)

**Path:** `POST /api/v1/usage` → `RecordUsageEvent`

| Concern | Implementation |
|---------|----------------|
| Idempotency | Client `event_id`; fast path SELECT; **UNIQUE(merchant_id, event_id)** is authoritative |
| Concurrency | `UniqueConstraintViolationException` → return existing row |
| Rate limit | `usage-ingest`: **500/minute per merchant** (`merchant_id` in limiter key) |
| Immutability | No update/delete API for usage events |
| Aggregation | `AggregateDailyUsage` dispatched **after commit** on new inserts only |

### 50L+ usage_events strategy

The system never loads the full `usage_events` table into PHP memory.

| Layer | Approach |
|-------|----------|
| Writes | Append-only rows; indexes support idempotency + time-range scans |
| Aggregation job | `SUM(quantity)` for one merchant/customer/UTC day via indexed range query |
| Rebuild | `usage:aggregate --from --to` uses SQL `GROUP BY` + **chunk(500)** on distinct tuples; dispatches one job per tuple |
| Billing / dashboard | Read **`daily_usage`**, not raw events |
| Future scale | **Partitioning not implemented** — deferred until operational need (see below) |

### usage_events indexes

| Index | Query |
|-------|--------|
| `UNIQUE(merchant_id, event_id)` | Idempotent ingest |
| `(merchant_id, customer_id, occurred_at)` | Daily aggregation SUM per day (Phase 9) |
| `(customer_id, occurred_at)` | Customer-scoped history / rebuild |
| `(subscription_id, occurred_at)` | Subscription-scoped analytics |
| `(merchant_id, occurred_at)` | Merchant-wide date scans |

**Trade-off:** Each secondary index adds write amplification on insert; justified by aggregation and rebuild paths at high row counts.

### daily_usage indexes

| Index | Query |
|-------|--------|
| `UNIQUE(merchant_id, customer_id, usage_date)` | Upsert + one row per day |
| `(merchant_id, usage_date)` | Dashboard month ranges, merchant-wide daily API |

## Daily aggregation

- **Idempotent:** always `SUM` from source, then upsert SET (never `+=`).
- **Retries:** safe; same result on duplicate job execution.
- **Zero usage:** delete aggregate row (no zero-row explosion).

## Billing

- **Command:** `billing:generate-invoices` — `chunkById(100)` on subscriptions, queue `GenerateSubscriptionInvoice`.
- **Idempotency:** `UNIQUE(subscription_id, billing_period_start, billing_period_end)` + transaction + `lockForUpdate`.
- **Pricing:** Subscription snapshot + `subscription_plan_changes`; not live plan catalog.
- **Usage input:** `daily_usage` per billing segment.

## Dashboard (Phase 8)

- Computed from `daily_usage` SQL aggregates; **no** `usage_events` scans.
- **No response cache** (documented in assumptions); acceptable for assignment scale.

## Caching (plans only)

`PlanCacheService` — Redis/file per `CACHE_STORE`:

| Key | TTL | Invalidation |
|-----|-----|--------------|
| `plans:{merchant_id}:{public_id}` | 3600s | Plan create/update/archive |
| `plans:{merchant_id}:list` | 3600s | Same |

Keys always include `merchant_id` — no cross-tenant collision.

## Queues & idempotency summary

| Operation | Guarantee |
|-----------|-----------|
| Usage ingest | DB unique on `(merchant_id, event_id)` |
| Daily aggregate | Recalculated SUM + upsert |
| Invoice generate | DB unique on billing period + transactional insert |
| Plan change | `lockForUpdate` on subscription |

Jobs: `AggregateDailyUsage` and `GenerateSubscriptionInvoice` use `$tries = 3` with backoff; `failed()` logs context (no secrets).

## Future partitioning (not implemented)

When `usage_events` exceeds comfortable single-table size:

1. **Range partitions** on `occurred_at` (e.g. monthly).
2. **Partition-aware aggregation** — restrict rebuild queries to relevant partitions.
3. **Retention/archival** — cold storage for old raw events; `daily_usage` remains billing/dashboard read model.

Deferred because: async aggregation + `daily_usage` + indexes meet assignment requirements without operational complexity of partition management.

## Observability (lightweight)

Structured log contexts (no tokens/passwords):

- `usage.duplicate_event_*` — idempotent ingest
- `aggregation.daily_usage_failed` — job failure
- `billing.invoice_job_failed` — invoice job failure

Use application log channel; no separate APM in this phase.
