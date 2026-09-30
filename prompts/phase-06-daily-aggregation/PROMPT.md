<!-- Transcript line 627; extracted for assignment prompt log -->

# Phase 6 — user prompt

# Phase 6 — Daily Usage Aggregation

Phase 1, Phase 2, Phase 3, Phase 4, and Phase 5 are complete.

Existing PRs remain open. Do NOT merge any PR.

Start Phase 6 from the current project state.

Create a new branch:

phase-06-daily-usage

Do NOT merge the PR.

After completing Phase 6:
1. Commit the changes.
2. Push the branch.
3. Create a PR.
4. Do NOT merge it.
5. Do NOT start Phase 7.
6. STOP.

Suggested commit:

feat: implement daily usage aggregation

==================================================
OBJECTIVE
==================================================

Implement asynchronous daily usage aggregation.

Phase 5 stores immutable raw usage events.

Phase 6 converts those raw events into a derived daily usage read model.

Architecture:

usage_events
      ↓
Queue
      ↓
Chunked / idempotent aggregation
      ↓
daily_usage
      ↓
Future Billing / Dashboard

IMPORTANT:

`usage_events` is the SOURCE OF TRUTH.

`daily_usage` is a DERIVED READ MODEL.

`daily_usage` must be completely rebuildable from `usage_events`.

Do not modify the raw usage events during aggregation.

==================================================
STRICT SCOPE
==================================================

Implement ONLY:

- daily_usage database table
- DailyUsage model
- aggregation jobs
- queued processing
- idempotent aggregation
- late-event handling
- chunked/batched processing
- rebuild mechanism
- daily usage read API
- authorization
- tenant isolation
- tests
- documentation

DO NOT implement:

- invoices
- billing calculations
- overage calculations
- proration
- payments
- projected revenue
- dashboard
- month-over-month usage analysis
- billing reports

Those belong to later phases.

==================================================
1. DATABASE SCHEMA
==================================================

Create:

daily_usage

Suggested fields:

- id
- merchant_id
- customer_id
- usage_date
- total_quantity
- timestamps

Use existing project conventions.

Use integer quantity.

Do not use floating-point values.

If `daily_usage` is an internal read model, do not expose unnecessary public IDs.

==================================================
2. UNIQUE CONSTRAINT
==================================================

A customer must have only one aggregate for a specific day.

Create:

UNIQUE(
    merchant_id,
    customer_id,
    usage_date
)

This must be enforced by the database.

The aggregation process must be safe even if multiple jobs target the same customer/date.

==================================================
3. INDEXING
==================================================

Design indexes based on actual queries.

At minimum consider:

- merchant_id + usage_date
- merchant_id + customer_id + usage_date

The unique constraint may already cover one of these query patterns.

Do not add redundant indexes.

Document the indexing decision.

Remember that every additional index increases write/update cost.

==================================================
4. SOURCE OF AGGREGATION
==================================================

Aggregate ONLY from:

usage_events

The usage date must be derived from:

usage_events.occurred_at

NOT:

created_at

Example:

occurred_at:
2026-09-29 23:50 UTC

created_at:
2026-09-30 01:00 UTC

The usage belongs to:

2026-09-29

Use UTC consistently.

==================================================
5. BASIC AGGREGATION
==================================================

Example raw events:

Customer A
Sep 29 → 100
Sep 29 → 200
Sep 29 → 300

Result:

Customer A
Sep 29 → 600

Multiple customers must remain independent.

Multiple dates must remain independent.

Multiple merchants must remain independent.

==================================================
6. QUEUE ARCHITECTURE
==================================================

Implement real asynchronous aggregation.

Phase 5 intentionally left a queue boundary.

Use Laravel queues.

Recommended conceptual flow:

Usage Event Created
       ↓
Aggregation Job
       ↓
Aggregate affected merchant/customer/date
       ↓
Upsert daily_usage

The HTTP request from Phase 5 must NOT perform daily aggregation synchronously.

Keep the ingestion endpoint lightweight.

==================================================
7. AGGREGATION JOB
==================================================

Create a dedicated job.

Example:

AggregateDailyUsage

The job should receive only the minimum required information, such as:

- merchant_id
- customer_id
- usage_date

Do not serialize the entire UsageEvent model unless there is a strong reason.

The job must be retry-safe.

==================================================
8. CRITICAL: IDEMPOTENT AGGREGATION
==================================================

The aggregation MUST be idempotent.

Do NOT implement:

daily_usage.total_quantity += event.quantity

for every queue retry.

That can double-count usage.

BAD:

Job #1 → +100
Job retry → +100
Final → 200

Correct approach:

Query raw events and calculate the authoritative total:

SUM(quantity)

Then set:

daily_usage.total_quantity = calculated_total

Therefore:

First execution → 100
Retry           → 100
Retry again     → 100

The result must remain correct.

==================================================
9. RECOMMENDED ALGORITHM
==================================================

For a specific:

merchant + customer + usage_date

perform:

1. Calculate UTC start of day.
2. Calculate UTC end of day.
3. Query usage_events for that merchant/customer/date.
4. SUM(quantity).
5. Upsert daily_usage.
6. If no usage exists, remove the derived row if appropriate.

Conceptually:

usage_events
    ↓
WHERE merchant_id = X
AND customer_id = Y
AND occurred_at >= day_start
AND occurred_at < day_end
    ↓
SUM(quantity)
    ↓
daily_usage

The raw events remain untouched.

==================================================
10. LATE EVENTS
==================================================

Phase 5 accepts late usage events.

Example:

Current date:

2026-09-29

A new event arrives with:

occurred_at = 2026-09-27

The system must aggregate:

2026-09-27

NOT:

2026-09-29

The affected usage date must come from `occurred_at`.

Test this explicitly.

==================================================
11. NEW EVENT → AGGREGATION JOB
==================================================

After a usage event is successfully persisted, dispatch the aggregation job.

The job should target the event's:

- merchant
- customer
- UTC usage date

Do not dispatch aggregation before the raw event is durably persisted.

If dispatching after commit is necessary, use Laravel's appropriate after-commit mechanism.

Avoid jobs seeing an event that has not committed yet.

==================================================
12. DUPLICATE USAGE EVENTS
==================================================

Phase 5 already guarantees:

UNIQUE(merchant_id, event_id)

Continue relying on that source-of-truth behavior.

Do not create a second usage event during retry.

Do not create duplicate quantity in daily_usage.

Aggregation must always calculate from actual raw events.

==================================================
13. CHUNKED / LARGE-SCALE PROCESSING
==================================================

The assignment requires handling 50L+ usage-event rows.

Do NOT load the entire usage_events table into memory.

Never use:

UsageEvent::all()

for large aggregation.

Use appropriate techniques such as:

- database-side GROUP BY
- chunkById
- cursor
- lazy collections
- batch processing

Prefer database-side aggregation where possible.

For example, aggregate:

merchant + customer + date

using SQL grouping rather than loading every event into PHP.

Document the approach.

==================================================
14. HISTORICAL REBUILD
==================================================

Implement a mechanism to rebuild daily usage for a date range.

For example:

php artisan usage:aggregate
    --from=2026-09-01
    --to=2026-09-30

Follow the project's command conventions.

The exact command name may differ if a better architecture exists.

Requirements:

- date range required/validated
- tenant scope
- chunked processing
- queued processing for large ranges
- no loading entire dataset into memory
- safe to run repeatedly

A rebuild must produce deterministic results.

==================================================
15. REBUILD IDEMPOTENCY
==================================================

Run:

rebuild #1

Then:

rebuild #2

The final daily_usage values must be identical.

Running the rebuild multiple times must never multiply usage.

Job order must not affect the final result.

==================================================
16. CONCURRENCY
==================================================

Multiple aggregation jobs may target the same:

merchant + customer + usage_date

simultaneously.

The final result must remain correct.

Use:

- database unique constraint
- upsert
- transaction/locking where justified

Do not rely only on:

if exists → update
else → create

because concurrent jobs can race.

Prefer a database-supported upsert.

==================================================
17. UPSERT
==================================================

Use database-supported upsert where appropriate.

Conceptually:

INSERT daily_usage
ON DUPLICATE KEY UPDATE
total_quantity = calculated_total

The important behavior is:

SET the total to the calculated source-of-truth value.

NOT:

increment existing total blindly.

==================================================
18. NO-USAGE DAYS
==================================================

Prefer:

No daily_usage row = no usage.

Do not generate millions of zero rows unnecessarily.

If a day has no raw usage events, a daily_usage row does not need to exist.

Document this decision.

==================================================
19. DAILY USAGE API
==================================================

Implement a read-only endpoint if consistent with the existing architecture:

GET /api/v1/usage/daily

Support:

- date
- customer
- date range

Examples:

GET /api/v1/usage/daily?date=2026-09-29

GET /api/v1/usage/daily?from=2026-09-01&to=2026-09-29

GET /api/v1/usage/daily?customer_id=01K...

Requirements:

- tenant scoped
- paginated
- bounded date range
- no unbounded queries

Do not expose internal database IDs.

==================================================
20. AUTHORIZATION
==================================================

Add:

usage.view

if consistent with the existing authorization architecture.

Keep the existing Phase 5:

usage.ingest

behavior unchanged unless there is a strong documented reason to change it.

Suggested:

Owner:
- usage.view
- usage.ingest

Member:
- usage.view
- usage.ingest

If Phase 5 intentionally documented different behavior, preserve that decision.

==================================================
21. TENANT ISOLATION
==================================================

Every daily usage query must be scoped through MerchantContext.

Never accept:

merchant_id

from the client.

Test:

Merchant A cannot read Merchant B daily usage.

Cross-tenant access should follow the established 404/authorization behavior.

==================================================
22. FRONTEND
==================================================

Keep the frontend minimal.

If a Usage page is implemented, show:

- Customer
- Date
- Total usage

Support:

- date filter
- customer filter
- pagination

Do NOT implement:

- charts
- revenue
- overage
- invoices
- dashboard analytics

Those are later phases.

==================================================
23. TESTS — BASIC AGGREGATION
==================================================

Add comprehensive tests.

Test:

### Single customer

100 + 200 + 300 = 600

### Multiple customers

Customer A:
100 + 200 = 300

Customer B:
500

### Multiple dates

Sep 28:
100

Sep 29:
200

### Multiple merchants

Merchant A and Merchant B remain isolated.

==================================================
24. IDEMPOTENCY TESTS
==================================================

Test:

- Run same aggregation job twice.
- Run same job multiple times.
- Run rebuild twice.
- Run jobs in different order.

Expected:

Exactly the same daily_usage result.

No double-counting.

==================================================
25. LATE EVENT TESTS
==================================================

Example:

Initial:

Sep 28 = 500

Insert late event:

Sep 28 = 200

Re-run aggregation.

Expected:

Sep 28 = 700

Also verify other dates are unchanged.

==================================================
26. PLAN CHANGE TEST
==================================================

Subscription:

Plan A:
Jan 1 → Jan 15

Plan B:
Jan 15 → Jan 31

Usage:

Jan 10 = 100
Jan 20 = 200

Daily usage:

Jan 10 = 100
Jan 20 = 200

Do NOT calculate pricing.

This phase aggregates quantity only.

==================================================
27. LARGE DATA TEST
==================================================

Do NOT create a 50L-row test in CI.

Create a reasonable large dataset test using thousands of records.

Verify:

- aggregation correctness
- memory remains reasonable
- no N+1 queries
- batch processing works

Do not make CI unnecessarily slow.

==================================================
28. PERFORMANCE
==================================================

Avoid:

- one query per event
- one job per event if that creates excessive queue overhead
- loading millions of rows into PHP
- N+1 queries

Prefer:

raw events
    ↓
database-side aggregation
    ↓
daily buckets
    ↓
bulk upsert

Choose the simplest implementation that is correct and explain the trade-off.

==================================================
29. 50L+ ARCHITECTURE DOCUMENTATION
==================================================

Update README with:

## Usage Scaling Beyond 50L Rows

Document:

Raw usage_events
        ↓
Indexed append-oriented storage
        ↓
Queue
        ↓
Chunked aggregation
        ↓
daily_usage
        ↓
Billing / Dashboard

Explain:

- raw events are immutable source of truth
- daily_usage is disposable/rebuildable
- aggregation is asynchronous
- late events trigger recomputation
- historical rebuild is possible
- large processing is chunked
- partitioning can be introduced later if required

Do NOT claim the system has been benchmarked at 50L rows unless an actual benchmark was performed.

==================================================
30. PARTITIONING
==================================================

Do not introduce MySQL partitioning automatically.

Evaluate it.

If not implemented, document:

- why it is deferred
- current indexes
- expected query patterns
- when partitioning could become useful
- operational trade-offs

==================================================
31. CACHE
==================================================

Do NOT add dashboard caching.

Do NOT modify the existing Plan cache.

Daily usage caching is not required at this stage.

Future dashboard caching will be handled separately.

==================================================
32. DOCUMENTATION
==================================================

Update:

README.md
docs/assumptions.md

Document:

- daily_usage purpose
- source-of-truth model
- aggregation algorithm
- queue architecture
- idempotency
- late-event behavior
- rebuild mechanism
- chunking strategy
- concurrency handling
- indexes
- UTC date handling
- no-usage behavior
- 50L+ scaling strategy

Clearly state:

`daily_usage` can be deleted and rebuilt from `usage_events`.

==================================================
33. QUALITY CHECKS
==================================================

Run:

Backend:

- migrations
- full test suite
- Pint
- Larastan

Frontend:

- tests
- vue-tsc
- ESLint
- Prettier
- Vite build

Verify:

php artisan migrate:fresh

works correctly.

Verify ALL Phase 1–5 tests still pass.

Do not break:

- authentication
- plans
- customers
- subscriptions
- usage ingestion
- idempotency
- rate limiting

==================================================
34. ASSIGNMENT ALIGNMENT
==================================================

Before creating the PR verify:

- daily_usage exists
- raw usage_events remain source of truth
- aggregation is asynchronous
- aggregation is idempotent
- queue retry cannot double-count
- late events recompute the affected date
- aggregation is chunked/batched
- large datasets are not loaded into memory
- tenant isolation is enforced
- unique daily aggregate constraint exists
- historical rebuild exists
- 50L+ strategy is documented
- no billing logic exists
- no invoice logic exists
- no dashboard logic exists

==================================================
35. GIT / PR
==================================================

Branch:

phase-06-daily-usage

Commit:

feat: implement daily usage aggregation

Push the branch.

Create a PR.

Do NOT merge.

Do NOT start Phase 7.

Final response must report:

1. Files created/updated
2. daily_usage schema
3. Aggregation algorithm
4. Queue architecture
5. Idempotency strategy
6. Late-event handling
7. Chunking/batching strategy
8. Historical rebuild
9. Concurrency handling
10. Indexing
11. API endpoints
12. Authorization
13. Tenant isolation
14. Frontend implementation
15. Tests added
16. Full test results
17. Static analysis results
18. Frontend/build results
19. 50L+ scaling strategy
20. Commit hash
21. PR URL
22. Assumptions/trade-offs

STOP after creating the PR.
