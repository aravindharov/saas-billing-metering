<!-- Transcript line 796; extracted for assignment prompt log -->

# Phase 9 — user prompt

We are continuing the SaaS Billing & Usage-Metering System assignment.

PHASE 8 IS COMPLETE.

Phase 8 branch:
phase-08-dashboard-analytics

Commit:
0418a51

PR:
#9
https://github.com/aravindharov/saas-billing-metering/pull/9

PR #9 is NOT merged.

Phase 7:
PR #8 is also NOT merged.

IMPORTANT:
- Do NOT merge any PR.
- Start Phase 9 from the current Phase 8 state.
- Create a NEW branch:
  phase-09-performance-security-hardening
- Preserve all existing functionality.
- Do not rewrite completed phases unnecessarily.
- Do not introduce unrelated product features.
- After completing Phase 9, create a PR only and STOP.

==================================================
PHASE 9 — PERFORMANCE, SECURITY & PRODUCTION HARDENING
==================================================

Goal:

Perform a focused production-readiness pass against the assignment requirements.

The system already implements:

- authentication and tenancy
- plans
- customers
- subscriptions
- plan changes
- high-throughput usage ingestion
- daily aggregation
- billing/invoices
- merchant dashboard

This phase should validate and harden those implementations.

DO NOT build:
- payments
- refunds
- tax engine
- coupons
- dunning
- credit notes
- customer login/portal
- unrelated SaaS functionality

==================================================
1. 50L+ USAGE EVENT SCALE REVIEW
==================================================

The assignment explicitly requires explaining how the system handles 50L+ usage-event rows.

Review the complete usage pipeline:

POST /api/v1/usage
        ↓
usage_events
        ↓
queued aggregation
        ↓
daily_usage
        ↓
billing/dashboard

Verify that the architecture does NOT require loading the complete
usage_events table into application memory.

Review:

- indexes
- query patterns
- chunking
- queue boundaries
- aggregation
- daily_usage
- dashboard queries
- billing queries

Verify the existing indexes are appropriate for actual query patterns.

Do not blindly add indexes.

For every new index, explain:
- which query uses it
- why it is necessary
- write/storage trade-off

==================================================
2. USAGE INGESTION PERFORMANCE
==================================================

Review:

POST /api/v1/usage

Verify:

- no unnecessary queries
- idempotency lookup is efficient
- unique database constraint remains authoritative
- concurrent duplicate requests are safe
- tenant resolution is efficient
- customer/subscription validation does not create N+1 queries
- rate limiting is merchant scoped
- raw usage events remain append-only

Preserve the existing duplicate-event behavior.

Do not weaken the database uniqueness constraint.

==================================================
3. DAILY AGGREGATION PERFORMANCE
==================================================

Review AggregateDailyUsage and:

php artisan usage:aggregate

Verify:

- aggregation is idempotent
- jobs can safely retry
- jobs do not double-count
- SUM is recalculated/set rather than incremented
- late events can rebuild the correct day
- rebuild command uses chunking
- no unbounded result sets are loaded into memory
- database upsert is used appropriately

Check whether the current indexes support:

merchant_id
customer_id
occurred_at

and the daily aggregation query.

If improvements are required, implement them.

==================================================
4. BILLING PERFORMANCE
==================================================

Review:

GenerateSubscriptionInvoice
billing:generate-invoices
BillingSegmentBuilder
BillingCalculator

Verify:

- subscriptions are processed in chunks
- one subscription does not cause unnecessary queries
- invoice generation remains transactional
- lockForUpdate is retained where required
- duplicate invoice protection remains database enforced
- retries remain safe
- historical pricing remains correct
- billing uses daily_usage rather than scanning raw usage_events

Do not change the billing formula unless an actual correctness bug is discovered.

==================================================
5. DASHBOARD PERFORMANCE
==================================================

Review the Phase 8 dashboard.

Verify:

- dashboard never scans usage_events
- daily_usage is used
- top 5 is calculated in SQL
- month-over-month calculations are efficient
- projected overage does not load unnecessary data
- no N+1 customer/subscription queries

Do not introduce dashboard caching unless there is a clear reason.

Phase 8 intentionally documented:

"no cache"

Keep that decision unless performance evidence requires changing it.

==================================================
6. DATABASE INDEX REVIEW
==================================================

Audit every major table:

merchants
users
customers
plans
subscriptions
subscription_plan_changes
usage_events
daily_usage
invoices
invoice_lines

For each important query path verify indexes.

Pay special attention to:

usage_events:
- merchant_id + event_id
- customer_id + occurred_at
- subscription_id + occurred_at
- merchant_id + occurred_at

daily_usage:
- merchant_id + usage_date
- merchant_id + customer_id + usage_date

invoices:
- merchant_id + billing period
- customer
- subscription
- status

Only add indexes when justified.

Document the index strategy in:

docs/architecture.md

If this document does not exist, create it.

==================================================
7. PARTITIONING STRATEGY
==================================================

The assignment asks how the system would handle 50L+ rows.

Do NOT implement database partitioning unless it is genuinely required by the current architecture.

Instead document a future strategy.

For example:

Current:
- append-only usage_events
- indexed occurred_at
- async aggregation
- daily_usage read model

Future at significantly larger scale:
- time-based partitioning by occurred_at
- monthly partitions
- archival/retention strategy
- partition-aware aggregation

Explain why partitioning is deferred.

Do not claim that partitioning has been implemented if it has not.

==================================================
8. CACHE REVIEW
==================================================

Review existing Phase 2 plan/pricing cache.

Verify:

- merchant is part of cache key
- plan identity is included
- cache invalidation occurs after writes
- archived/updated plans do not leave stale pricing indefinitely
- no cross-tenant cache collision

Do not replace the existing cache architecture without a concrete reason.

Document:

- cache key
- TTL
- invalidation behavior

==================================================
9. API RATE LIMITING REVIEW
==================================================

Review usage endpoint rate limiting.

Requirement:

500 requests/minute/merchant.

Verify:

- correct scope
- authenticated tenant
- predictable HTTP 429 response
- no ability for a user to bypass merchant limits by changing identifiers
- rate limit does not accidentally become global across all merchants

Add tests if any gap exists.

==================================================
10. TENANT ISOLATION SECURITY AUDIT
==================================================

Perform a complete tenant isolation audit.

Check every major resource:

- Merchant
- User
- Plan
- Customer
- Subscription
- SubscriptionPlanChange
- UsageEvent
- DailyUsage
- Invoice
- InvoiceLine
- Dashboard

Verify that authenticated Merchant A cannot access Merchant B data through:

- URL public IDs
- query parameters
- request payloads
- filters
- pagination
- nested relationships
- invoice IDs
- subscription IDs
- customer IDs
- plan IDs

Cross-tenant access must follow the application's established 404/authorization convention.

Add missing security tests.

==================================================
11. MASS ASSIGNMENT / INPUT SECURITY
==================================================

Review all Form Requests and models.

Verify:

- merchant_id is never accepted from untrusted request payloads
- owner/member role cannot be escalated through request data
- public IDs cannot be overwritten
- pricing snapshot fields cannot be modified through normal subscription update requests
- invoice status cannot be changed through public API
- usage event identity cannot be altered
- immutable resources remain immutable

Do not expose internal IDs unnecessarily.

==================================================
12. AUTHENTICATION / AUTHORIZATION
==================================================

Review Sanctum authentication.

Verify:

- login
- logout
- /me
- revoked token behavior
- suspended merchant behavior
- protected routes
- owner/member authorization

Verify existing semantics:

Owner:
- write access where intended

Member:
- read-only where intended

Do not introduce a large permission/RBAC system unless the current implementation actually requires it.

==================================================
13. IDEMPOTENCY / CONCURRENCY REVIEW
==================================================

Review all operations that can be retried.

Usage ingestion:
- duplicate event_id

Invoice generation:
- duplicate billing period

Plan changes:
- concurrent subscription modification

Daily aggregation:
- repeated job execution

Verify database constraints + transactions + locks provide the final correctness guarantee.

Tests should cover concurrent/race-sensitive paths where practical.

Do not rely only on application-level "check then insert".

==================================================
14. IMMUTABILITY REVIEW
==================================================

Verify:

usage_events:
- append-only

issued invoices:
- immutable

invoice lines:
- immutable once issued

subscription pricing snapshots:
- historical values preserved

plan changes:
- historical pricing preserved

Changing the current Plan catalog must not rewrite historical subscription/invoice data.

Add tests if any gap is discovered.

==================================================
15. ERROR HANDLING / API QUALITY
==================================================

Review API error responses.

Ensure validation errors are consistent.

Check:

- 401 unauthenticated
- 403 unauthorized where appropriate
- 404 cross-tenant/nonexistent resource
- 422 validation
- 429 rate limit
- 500 unexpected failures

Do not expose:
- SQL errors
- stack traces
- secrets
- internal file paths
- sensitive infrastructure information

Ensure production APP_DEBUG expectations are documented.

==================================================
16. OBSERVABILITY
==================================================

Review logging around:

- usage ingestion failures
- duplicate usage events
- aggregation failures
- billing failures
- queue failures

Logs must contain useful context such as:

- merchant
- customer
- subscription
- event/invoice identifier where appropriate

Do NOT log:
- authentication tokens
- passwords
- secrets
- unnecessary customer-sensitive information

Do not introduce a complete observability platform.

Keep this lightweight.

==================================================
17. QUEUE FAILURE / RETRY REVIEW
==================================================

Review queued jobs.

Verify:

- retry behavior
- idempotency
- failed jobs do not corrupt billing/usage
- aggregation can be retried
- invoice generation can be retried
- no duplicate invoice lines
- transactions roll back correctly

Document the retry/idempotency strategy.

==================================================
18. API QUERY / N+1 AUDIT
==================================================

Review all APIs introduced so far.

Look specifically for:

- loops executing database queries
- unnecessary relationship loading
- missing eager loading
- pagination problems
- unbounded queries

Focus on:

- customers
- subscriptions
- usage
- daily usage
- invoices
- dashboard

Fix only real issues.

Do not perform cosmetic refactoring.

==================================================
19. FRONTEND SECURITY REVIEW
==================================================

Review Vue frontend.

Verify:

- protected routes
- authentication state
- logout
- API error handling
- no secrets in frontend
- no backend credentials exposed
- tenant IDs are not trusted as authorization
- unauthorized API responses handled properly

Do not store sensitive credentials unnecessarily in localStorage.

==================================================
20. TEST COVERAGE
==================================================

Add tests for every real bug or security gap discovered.

Important regression areas:

- tenant isolation
- rate limiting
- usage idempotency
- aggregation retry
- invoice idempotency
- plan-change history
- billing historical pricing
- dashboard calculations
- authorization
- immutable resources

Run the complete existing suite.

Target:

Backend:
ALL existing tests PASS

Frontend:
ALL existing tests PASS

Do not delete or weaken existing tests.

==================================================
21. STATIC ANALYSIS
==================================================

Run:

- Pint
- PHPStan/Larastan
- PHPUnit/Pest
- vue-tsc
- ESLint
- Prettier
- Vite build

Fix all issues.

Do not suppress errors unless there is a documented and justified reason.

==================================================
22. DOCUMENTATION
==================================================

Create/update:

README.md
docs/architecture.md
docs/assumptions.md

README should clearly explain:

- system architecture
- setup
- authentication
- tenant model
- usage ingestion
- daily aggregation
- billing
- invoices
- dashboard
- testing
- queue commands
- billing commands

Architecture documentation should explain:

Merchant
  ↓
Users
  ↓
Customers
  ↓
Subscriptions
  ↓
Plans / Pricing Snapshots
  ↓
Usage Events
  ↓
Daily Usage
  ↓
Billing / Invoices
  ↓
Dashboard

Also document:

50L+ usage-event strategy
- indexes
- chunking
- queues
- daily read model
- future partitioning

Caching strategy

Idempotency strategy

Tenant isolation strategy

==================================================
23. ASSIGNMENT REQUIREMENT CHECKLIST
==================================================

Before finishing, compare the implementation against the original assignment.

Explicitly verify:

[ ] Plans
[ ] Customers
[ ] Subscriptions
[ ] Usage events
[ ] POST /usage
[ ] Idempotency
[ ] Rate limiting
[ ] High-volume strategy
[ ] Daily aggregation
[ ] Billing
[ ] Overage
[ ] Proration
[ ] Mid-cycle plan changes
[ ] Historical pricing
[ ] Invoice generation
[ ] Invoice idempotency
[ ] Dashboard
[ ] Top 5 customers
[ ] Projected overage revenue
[ ] >50% usage drop
[ ] Tenant isolation
[ ] Caching
[ ] Tests
[ ] Documentation
[ ] Prompt log
[ ] 50L+ explanation

If any requirement is genuinely missing, implement it only if it belongs to the assignment.

Do not expand scope.

==================================================
24. FINAL QUALITY CHECK
==================================================

Before committing:

1. Review git diff.
2. Search for TODO/FIXME left by the implementation.
3. Search for debug statements.
4. Search for accidental secrets.
5. Verify migrations are reversible where practical.
6. Verify no generated files are unnecessarily committed.
7. Verify no unrelated refactoring.
8. Verify all tests pass.
9. Verify documentation matches actual implementation.
10. Verify assignment requirements are covered.

==================================================
25. GIT WORKFLOW
==================================================

Create:

phase-09-performance-security-hardening

Commit:

chore: harden performance and security

Push branch.

Create a PR.

PR description:

## Summary
- Performance audit
- Security/tenant isolation audit
- Concurrency/idempotency hardening
- 50L+ scalability documentation
- Database/index review
- Error handling improvements
- Regression tests

## Performance
Explain:
- usage_events
- daily_usage
- chunking
- queue processing
- indexes
- future partitioning

## Security
Explain:
- tenant isolation
- authorization
- immutable resources
- input validation
- rate limiting

## Tests
List:
- backend tests
- frontend tests
- static analysis
- build

IMPORTANT:

Do NOT merge the PR.

STOP after PR creation.
