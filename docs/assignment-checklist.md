# Assignment requirement audit

Source of truth: Mallow Technologies take-home assignment (Subscription Billing & Usage-Metering).  
Audit date: Phase 10 final review (`phase-10-final-review`).

Legend: **Status** — ✅ verified in repo | ⚠️ partial / documented trade-off | ❌ missing

| Requirement | Implementation | Evidence | Status |
|-------------|----------------|----------|--------|
| **Plans — merchant defines plans** | CRUD via Plans API + Vue UI; tenant-scoped | `routes/api.php`, `PlanController`, `CreatePlan` / `UpdatePlan` / `ArchivePlan` actions | ✅ |
| **Plan name** | Required, unique per merchant | `StorePlanRequest`, migration `plans.name` | ✅ |
| **Base price** | Integer minor units (paise) | `Plan` model, validation, `docs/assumptions.md` | ✅ |
| **Billing cycle** | `monthly` / `yearly` enum | `BillingCycle`, plan forms | ✅ |
| **Included usage units** | Non-negative integer | Plan schema + API | ✅ |
| **Overage rate** | Integer minor units per unit | Plan schema + billing calculator | ✅ |
| **Customers belong to merchants** | `customers.merchant_id`, `MerchantContext` | `Customer` model, middleware | ✅ |
| **Customer ↔ subscription** | Subscription links customer + plan | `Subscription`, `CreateSubscription` | ✅ |
| **Customer tenant isolation** | Route binding + policies + tests | `TenantIsolationTest`, `TenantResourceIsolationTest` | ✅ |
| **Subscribe customer to plan** | `POST /api/v1/subscriptions` | `CreateSubscription`, UI | ✅ |
| **Pricing snapshot on subscription** | Copied at create / change | `subscriptions.base_price`, etc. | ✅ |
| **Billing period** | `current_period_start` / `current_period_end` | `CreateSubscription`, calendar-aware | ✅ |
| **Mid-cycle plan changes** | `change-plan` endpoint + history rows | `ChangeSubscriptionPlan`, `subscription_plan_changes` | ✅ |
| **Historical pricing for billing** | Segments from snapshot + plan changes | `BillingSegmentBuilder`, not live `plans` price | ✅ |
| **`POST /api/v1/usage`** | Ingest endpoint | `UsageController`, `RecordUsageEvent` | ✅ |
| **High-throughput ingestion** | Append-only writes, async aggregation, indexes | `docs/architecture.md`, usage migrations | ✅ |
| **Usage idempotency** | `event_id` + `UNIQUE(merchant_id, event_id)` | `IdempotencyTest`, duplicate → 200 | ✅ |
| **Usage rate limiting** | 500/min per merchant | `AppServiceProvider`, `UsageRateLimitingTest` | ✅ |
| **Immutable usage events** | No update/delete routes | `UsageImmutabilityTest` | ✅ |
| **Usage tenant isolation** | Merchant from auth only | `InputHardeningTest`, ingest tests | ✅ |
| **Daily usage aggregates** | `daily_usage` table + job | `AggregateDailyUsage`, model | ✅ |
| **Queued aggregation** | Job on queue after commit | `RecordUsageEvent`, `docker-compose` queue service | ✅ |
| **Chunked rebuild** | Command chunks 500 groups | `AggregateUsageCommand` | ✅ |
| **Idempotent aggregation** | SET total from SUM, not += | `AggregateDailyUsageTest` | ✅ |
| **Late events** | Re-aggregate affected UTC day | Tests + seeder paths | ✅ |
| **Rebuild capability** | `usage:aggregate --from --to` | `AggregateUsageCommandTest` | ✅ |
| **Base charge** | Invoice line type `base` | `BillingCalculator`, `GenerateInvoice` | ✅ |
| **Included usage** | Per-segment included units | Calculator + assumptions doc | ✅ |
| **Overage** | Per-segment overage lines | `BillingCalculatorTest` | ✅ |
| **Proration** | Time-weighted base per segment | Unit + feature billing tests | ✅ |
| **Mid-cycle plan changes in billing** | Multiple segments in period | `BillingSegmentBuilder` | ✅ |
| **Billing period alignment** | Invoice period = subscription period | `GenerateInvoice` | ✅ |
| **Invoice generation** | Action + queued job + command | `GenerateInvoice`, `GenerateSubscriptionInvoice` | ✅ |
| **Invoice lines** | `invoice_lines` with types | `InvoiceApiTest`, UI detail | ✅ |
| **Duplicate invoice protection** | Unique (subscription, period start, period end) | DB + idempotent generate API | ✅ |
| **Immutable issued invoice** | No update endpoints; issued status | Invoice API read-only | ✅ |
| **Queued invoice generation** | `billing:generate-invoices` dispatches jobs | `GenerateInvoicesCommandTest` | ✅ |
| **Dashboard top 5 usage** | Month-to-date from `daily_usage` | `DashboardService`, `DashboardApiTest` | ✅ |
| **Projected overage revenue** | Segment projection | `ProjectedOverageCalculator` + tests | ✅ |
| **>50% MoM usage drop** | Fair MTD windows | Dashboard tests | ✅ |
| **Dashboard tenant isolation** | Merchant route + policy | `MerchantPolicy`, security tests | ✅ |
| **Dashboard avoids raw event scans** | Reads `daily_usage` only | `DashboardService` implementation | ✅ |
| **Normalized schema** | Relational tables per domain | Migrations under `database/migrations` | ✅ |
| **Indexes for usage** | Ingest + aggregation indexes | Migrations, `docs/architecture.md` | ✅ |
| **50L+ strategy** | Documented pipeline | `docs/architecture.md`, README scaling section | ✅ |
| **Queue + chunking** | Jobs + command chunk sizes | Architecture doc, commands | ✅ |
| **Daily read model** | `daily_usage` | Phase 6 implementation | ✅ |
| **Future partitioning** | Documented, not implemented | `docs/architecture.md` | ⚠️ deferred by design |
| **Plan/pricing cache** | Redis, merchant-scoped keys | `PlanCacheTest`, README cache section | ✅ |
| **Cache invalidation** | On plan write | Plan actions / repository | ✅ |
| **Cache TTL** | 1 hour safety net | Plan cache implementation | ✅ |
| **Billing tests** | Calculator + generation | `tests/Unit/Services/Billing/*`, `tests/Feature/Billing/*` | ✅ |
| **Proration / overage tests** | Unit calculator | `BillingCalculatorTest` | ✅ |
| **Plan-change tests** | Feature tests | `ChangeSubscriptionPlanTest` | ✅ |
| **Idempotency tests** | Usage + invoices | `IdempotencyTest`, billing idempotency tests | ✅ |
| **Tenant isolation tests** | Multiple suites | `Tenancy/*`, `Security/TenantResourceIsolationTest` | ✅ |
| **Dashboard tests** | API + projection unit | `DashboardApiTest`, `ProjectedOverageCalculatorTest` | ✅ |
| **Frontend tests** | Vitest | `resources/js/**/*.spec.ts` | ✅ |
| **README** | Setup, API, domains | `README.md` | ✅ |
| **Assumptions** | Billing/dashboard formulas | `docs/assumptions.md` | ✅ |
| **Architecture** | 50L+, security notes | `docs/architecture.md` | ✅ |
| **AI prompt log** | Markdown transcripts per phase | [`prompts/`](../prompts/) | ✅ |
| **Setup / commands documented** | Docker, artisan commands | README Local Setup, Backend Commands | ✅ |
| **Demo path** | Seeder + docs | `DatabaseSeeder`, `docs/demo-script.md` | ✅ |
| **Authentication** | Sanctum login/logout/me | `routes/api.php`, auth tests | ✅ |
| **Health check** | `GET /api/health` | `HealthEndpointTest` | ✅ |
| **SPA admin UI** | Vue pages for core flows | `resources/js/pages/*` | ✅ |
| **CI quality gate** | GitHub Actions | `.github/workflows/` | ✅ |
| **Prompt screenshots (if PDF requires images)** | Not in repository | Transcripts only — see `docs/final-review.md` | ⚠️ |

## Gap classification (Phase 10)

| ID | Finding | Category | Action |
|----|---------|----------|--------|
| G1 | Assignment PDF may ask for prompt **screenshots**; repo has **markdown transcripts** only | B (evaluator preference) / optional assignment artifact | Documented in `docs/final-review.md`; no fabricated images |
| G2 | Database partitioning not implemented | C | Documented deferral in architecture |
| G3 | Payments, taxes, refunds | C | Listed out of scope in README |
| G4 | Seeder does not pre-create invoices (period still open) | B | Demo script covers manual/API invoice step |

**Category A (required and missing):** none identified — no production code changes required for assignment coverage.
