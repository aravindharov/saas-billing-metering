<!-- Transcript line 683; extracted for assignment prompt log -->

# Phase 7 — user prompt

# Phase 7 — Billing & Invoices

Phase 1 through Phase 6 are complete.

Existing PRs remain open. Do NOT merge any PR.

Start Phase 7 from the current project state.

Create a new branch:

phase-07-billing-invoices

Do NOT merge any PR.

After completing Phase 7:
1. Commit the changes.
2. Push the branch.
3. Create a PR.
4. Do NOT merge it.
5. Do NOT start Phase 8.
6. STOP.

Suggested commit:

feat: implement billing and invoices

==================================================
OBJECTIVE
==================================================

Implement the billing and invoice system required by the assignment.

The assignment requires:

- Cycle-end invoice generation
- Base subscription charge
- Included usage units
- Overage calculation
- Overage rate per unit
- Mid-cycle plan changes
- Proration across plan segments
- Correct historical pricing
- Queued/chunked billing processing
- Invoice persistence
- Billing tests

Existing architecture:

Plans
  ↓
Customers
  ↓
Subscriptions
  ↓
Subscription pricing snapshots
  ↓
Subscription plan-change history
  ↓
Usage Events
  ↓
Daily Usage
  ↓
Invoices

IMPORTANT:

Do not modify historical raw usage events.

Do not rely on the current Plan price when calculating historical invoices.

Use subscription pricing snapshots and plan-change history.

==================================================
STRICT SCOPE
==================================================

Implement ONLY:

- invoice schema
- invoice lines
- billing calculation
- base charges
- usage/overage calculation
- mid-cycle pricing segments
- proration
- invoice generation
- queued billing jobs
- invoice API
- invoice frontend
- authorization
- tenant isolation
- tests
- documentation

DO NOT implement:

- payment gateway
- payment processing
- refunds
- credit notes
- dunning
- taxes
- coupons
- payment methods
- dashboard analytics
- projected revenue dashboard
- payment reconciliation

Those are outside the current assignment scope.

==================================================
1. CORE BILLING FORMULA
==================================================

For a normal billing cycle:

Invoice total:

base charge + overage charge

Where:

base charge:

subscription base price

and:

billable usage =
max(0, total usage - included usage)

overage charge:

billable usage × overage rate

Example:

Plan:

Base price = ₹499
Included units = 10,000
Overage rate = ₹5

Usage:

12,000

Calculation:

Included = 10,000
Billable = 2,000

Overage:

2,000 × ₹5 = ₹10,000

Invoice:

₹499 + ₹10,000
= ₹10,499

Use integer minor units throughout.

Do NOT use floating-point arithmetic.

==================================================
2. IMPORTANT: PRICING SEGMENTS
==================================================

Phase 4 created:

subscription_plan_changes

and pricing snapshots.

Use these records to determine pricing for each period segment.

Example:

Subscription period:

Jan 1 → Jan 31

Plan A:

Jan 1 → Jan 15

Plan B:

Jan 15 → Jan 31

Billing must treat these as separate segments.

Do NOT simply use the subscription's current plan price for the entire period.

==================================================
3. SEGMENT BILLING
==================================================

For each billing segment:

Determine:

- segment start
- segment end
- applicable base price
- included usage units
- overage rate
- billing cycle

Then determine:

- segment usage
- billable usage
- overage

The final invoice is the sum of all applicable segment charges.

==================================================
4. PRORATION
==================================================

Implement time-based proration for base charges.

Use the actual segment duration relative to the billing period.

Do NOT use fixed:

30 days

or:

365 days

unless that is actually the billing period.

Example:

Monthly subscription:

Jan 1 → Jan 31

Plan A applies:

Jan 1 → Jan 15

Plan B applies:

Jan 15 → Jan 31

Each plan's base charge must be prorated according to the applicable time segment.

Use precise timestamp-based duration where appropriate.

Document the exact formula.

==================================================
5. PRORATION FORMULA
==================================================

For a billing segment:

prorated_base =
plan_base_price ×
(segment_seconds / billing_period_seconds)

Because prices are stored as integer minor units:

Do not use floating-point arithmetic.

Use integer-safe arithmetic.

Use appropriate rounding rules.

Document:

- rounding direction
- fractional minor-unit behavior
- how final invoice rounding is handled

The implementation must be deterministic.

==================================================
6. USAGE SEGMENTATION
==================================================

Usage must also respect plan-change segments.

Example:

Plan A:

Jan 1 → Jan 15
Included = 10,000
Overage = ₹5

Plan B:

Jan 15 → Jan 31
Included = 20,000
Overage = ₹3

Usage:

Jan 10 → 8,000
Jan 20 → 25,000

The usage must be attributed to the applicable segment.

Do NOT combine all usage and apply the current plan's pricing.

==================================================
7. DAILY USAGE AS BILLING INPUT
==================================================

Use:

daily_usage

for billing aggregation where appropriate.

Do not scan the entire raw `usage_events` table for every invoice.

Raw events remain the source of truth.

Daily usage is the efficient billing read model.

For historical correction/rebuild, daily_usage can be regenerated from raw events.

==================================================
8. USAGE DATE BOUNDARIES
==================================================

Billing must use UTC consistently.

Ensure the billing period boundaries and daily usage selection are deterministic.

Do not mix:

- application timezone
- database timezone
- merchant timezone

unless explicitly supported/documented.

For this assignment, use UTC.

==================================================
9. INCLUDED USAGE

For each applicable pricing segment:

billable usage:

max(
    0,
    segment_usage - included_usage_units
)

Never generate negative overage.

If:

usage <= included

then:

overage = 0

Test:

- zero usage
- usage exactly equal to included
- usage below included
- usage above included

==================================================
10. MULTIPLE PLAN SEGMENTS

Test:

Plan A:

base = 10,000
included = 100
overage = 10

Plan B:

base = 20,000
included = 200
overage = 5

Period:

Jan 1 → Jan 31

Change:

Jan 15

Usage:

Jan 10 = 150
Jan 20 = 250

Calculate each segment independently.

Do not apply Plan B's pricing to Jan 10 usage.

==================================================
11. INVOICE SCHEMA

Create:

invoices

At minimum:

- id
- public_id
- merchant_id
- customer_id
- subscription_id
- billing_period_start
- billing_period_end
- subtotal
- total
- status
- issued_at
- timestamps

Use integer minor units.

Suggested status:

- draft
- issued

Keep the lifecycle simple.

Do not implement payment status.

==================================================
12. INVOICE LINES

Create:

invoice_lines

At minimum:

- id
- invoice_id
- type
- description
- quantity
- unit_price
- amount
- metadata if useful
- timestamps

Possible types:

- base
- overage

Do not introduce unnecessary line types.

Every amount must use integer minor units.

==================================================
13. INVOICE IMMUTABILITY

Once an invoice is issued:

DO NOT modify its monetary values.

Invoices are historical financial records.

Do not provide public update/delete endpoints for issued invoices.

If corrections are required in the future, a credit-note mechanism can be added later.

Do NOT implement credit notes now.

==================================================
14. INVOICE CALCULATION SERVICE

Keep billing calculations outside controllers.

Recommended structure:

InvoiceController
      ↓
Billing / Invoice Action
      ↓
Billing Calculator
      ↓
Subscription pricing segments
      ↓
Daily usage
      ↓
Invoice + invoice lines

Create a dedicated billing calculation service/value object where appropriate.

The calculation logic must be independently unit-testable.

==================================================
15. MONEY CALCULATION

All money calculations must use integer minor units.

Example:

₹499.00
→ 49900

₹5.00
→ 500

If the project interprets overage rate as ₹5 per unit, ensure the stored representation and calculation are consistent.

Do NOT introduce floating point.

Document the currency/minor-unit assumption.

==================================================
16. CURRENCY

The assignment does not require multi-currency.

Do NOT implement a full currency system.

If needed, document the assumption that the merchant operates in one configured/default currency.

Do not complicate the billing model unnecessarily.

==================================================
17. INVOICE GENERATION

Create a billing action/job that can generate an invoice for a completed billing period.

Conceptually:

GenerateInvoice
    ↓
Validate subscription
    ↓
Determine billing period
    ↓
Determine pricing segments
    ↓
Read daily usage
    ↓
Calculate base charges
    ↓
Calculate overage
    ↓
Create invoice
    ↓
Create invoice lines
    ↓
Issue invoice

All invoice creation must be transactional.

==================================================
18. DUPLICATE INVOICE PROTECTION

Generating the same invoice twice must NOT create duplicate invoices.

Create a database uniqueness strategy based on:

- merchant
- subscription
- billing period start
- billing period end

For example:

UNIQUE(
    subscription_id,
    billing_period_start,
    billing_period_end
)

Use the database as the final protection.

Test concurrent invoice generation.

==================================================
19. BILLING PERIOD

For a monthly subscription:

current_period_start
→ current_period_end

For yearly:

current_period_start
→ current_period_end

Do not calculate billing periods using fixed seconds.

Use the existing calendar-aware subscription period logic.

==================================================
20. BILLING AT CYCLE END

Implement a mechanism to generate invoices for subscriptions whose billing period has ended.

This should be queue-friendly.

Do NOT process thousands of subscriptions in one synchronous HTTP request.

Create a queued job/command where appropriate.

For example:

php artisan billing:generate-invoices

The exact command naming may follow project conventions.

Use chunked processing.

==================================================
21. CHUNKED BILLING

The assignment explicitly asks for queued chunked processing.

Do NOT:

Subscription::all()

for a large merchant population.

Use:

- chunkById
- cursor
- lazy collections
- queued batches

Process subscriptions in manageable batches.

Document the approach.

==================================================
22. BILLING RETRIES

Invoice generation must be retry-safe.

If a queue job runs twice:

Only one invoice should exist.

Use:

- database unique constraint
- transaction
- deterministic calculation

Do not depend solely on application-level existence checks.

==================================================
23. INVOICE CALCULATION EXAMPLE

Test this exact scenario:

Plan:

Base price = ₹500
Included = 1,000 units
Overage = ₹2/unit

Usage:

1,500

Expected:

Base = ₹500

Billable:
1,500 - 1,000 = 500

Overage:
500 × ₹2 = ₹1,000

Total:
₹1,500

Verify stored values use minor units.

==================================================
24. PRORATION EXAMPLE

Create a deterministic test.

Monthly period:

Jan 1 00:00
→ Feb 1 00:00

Plan A:

₹1,000/month

Plan B:

₹2,000/month

Plan B becomes effective:

Jan 16 00:00

Calculate:

Plan A:

₹1,000 × duration(A) / duration(period)

Plan B:

₹2,000 × duration(B) / duration(period)

Invoice base total:

A prorated amount + B prorated amount

Document rounding.

==================================================
25. OVERAGE + PLAN CHANGE TEST

Test:

Plan A:

Included = 100
Overage = ₹10

Plan B:

Included = 200
Overage = ₹5

Usage before change:

150

Usage after change:

300

Calculate independently:

Plan A:

150 - 100 = 50 billable

Plan B:

300 - 200 = 100 billable

Do NOT combine:

450 total usage

and apply one plan.

==================================================
26. ZERO USAGE

A subscription with zero usage must still produce the appropriate base charge.

Expected:

base charge > 0 where applicable

overage = 0

==================================================
27. EXACT INCLUDED USAGE

If:

included = 1000

usage = 1000

Expected:

overage = 0

==================================================
28. BELOW INCLUDED USAGE

If:

included = 1000

usage = 800

Expected:

overage = 0

==================================================
29. ABOVE INCLUDED USAGE

If:

included = 1000

usage = 1200

Expected:

overage = 200 × overage_rate

==================================================
30. API

Implement:

GET /api/v1/invoices
GET /api/v1/invoices/{invoice}

Optional generation endpoint only if justified.

Do NOT expose an endpoint that allows arbitrary users to generate duplicate invoices without authorization.

Invoice generation should primarily be an internal queued operation.

Support:

- pagination
- customer filter
- subscription filter
- status filter
- date range if useful

All queries tenant scoped.

==================================================
31. INVOICE RESOURCE

Expose:

- public_id
- customer
- subscription
- billing period
- subtotal
- total
- status
- issued_at
- invoice lines

Do not expose internal IDs.

==================================================
32. AUTHORIZATION

Add:

invoices.view

Owner:

- view invoices

Member:

- view invoices

Invoice generation should be restricted to the appropriate server-side billing operation.

Do not allow normal Members to manipulate invoice monetary values.

==================================================
33. TENANT ISOLATION

Every invoice query must be scoped to MerchantContext.

Test:

Merchant A cannot:

- list Merchant B invoices
- view Merchant B invoice
- generate an invoice for Merchant B subscription

Cross-tenant access must follow existing 404 behavior.

==================================================
34. FRONTEND

Create a simple invoice UI.

## Invoice List

Show:

- invoice number/public ID
- customer
- billing period
- subtotal
- total
- status
- issued date

Support:

- pagination
- filters

## Invoice Detail

Show:

- customer
- subscription
- billing period
- base charge lines
- overage lines
- subtotal
- total

Do NOT build:

- payment UI
- payment status
- refund UI
- tax UI

==================================================
35. TESTING — INVOICE CREATION

Test:

- valid invoice
- zero usage
- below included usage
- exact included usage
- above included usage
- base charge
- overage charge
- total calculation
- invoice lines
- invoice status
- billing period

==================================================
36. TESTING — PRORATION

Test:

- no plan change
- upgrade mid-cycle
- downgrade mid-cycle
- change near period start
- change near period end
- multiple plan changes
- old pricing preserved
- new pricing preserved
- deterministic rounding

==================================================
37. TESTING — HISTORICAL PRICING

Critical test:

1. Create subscription with Plan A.
2. Plan A price = 10000.
3. Change Plan A price to 20000.
4. Generate invoice.
5. Verify applicable subscription pricing remains 10000.

Also test plan changes:

Plan A → Plan B.

Invoice must use historical pricing snapshots.

==================================================
38. TESTING — IDEMPOTENCY

Test:

Generate same invoice twice.

Expected:

Exactly one invoice.

Also test concurrent generation where practical.

==================================================
39. TESTING — TENANT ISOLATION

Test:

- Merchant A cannot access Merchant B invoices.
- Merchant A cannot generate Merchant B invoice.
- Merchant A cannot manipulate invoice IDs.
- merchant_id cannot be injected.

==================================================
40. TESTING — BILLING PERIOD EDGE CASES

Test:

- month boundaries
- year boundaries
- leap year
- subscription starting at period boundary
- plan change at exact period boundary
- plan change immediately before period end

Use actual calendar-aware dates.

==================================================
41. BILLING CALCULATION TESTS

Keep calculation tests independent from HTTP tests.

Create unit tests for:

- base charge
- overage
- proration
- segment splitting
- integer arithmetic
- rounding

This should make the billing engine easy to reason about.

==================================================
42. PERFORMANCE

Do not scan all raw usage events for every invoice.

Use:

daily_usage

for billing reads.

Only use raw usage events for rebuild/correction workflows.

Avoid N+1 queries when generating invoices.

Where possible:

- preload required records
- aggregate efficiently
- process subscriptions in chunks

==================================================
43. DATABASE INDEXING

Consider indexes:

invoices:
- merchant_id + billing_period_start
- merchant_id + customer_id
- merchant_id + status
- unique subscription + billing period

invoice_lines:
- invoice_id

Do not add redundant indexes.

Document important decisions.

==================================================
44. TRANSACTIONS

Invoice creation must be transactional.

Within the transaction:

- create invoice
- create invoice lines
- mark invoice issued

If any step fails, the transaction must roll back.

Do not leave partially-created invoices.

==================================================
45. FINANCIAL INTEGRITY

Once issued:

- invoice totals immutable
- invoice lines immutable
- pricing history immutable
- usage source immutable

Do not allow normal CRUD operations to alter historical financial data.

==================================================
46. DOCUMENTATION

Update:

README.md
docs/assumptions.md

Document:

- billing formula
- overage formula
- pricing segment logic
- proration formula
- rounding
- integer money representation
- invoice lifecycle
- invoice immutability
- duplicate invoice protection
- billing queue
- chunked processing
- historical pricing
- usage source
- UTC handling
- currency assumption

Include at least one worked billing example.

==================================================
47. ASSIGNMENT ALIGNMENT
==================================================

Before creating the PR verify:

- cycle-end invoice generation exists
- base price is charged
- included usage is respected
- overage is calculated
- overage rate is respected
- pricing snapshots are used
- mid-cycle plan changes are handled
- proration is implemented
- billing uses daily_usage
- billing is queued/chunked
- duplicate invoices are prevented
- invoices are immutable
- calculations use integer money
- tenant isolation works
- tests cover billing edge cases
- no payment gateway was implemented
- no dashboard was implemented

==================================================
48. QUALITY CHECKS
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

works.

Verify ALL Phase 1–6 tests still pass.

Do not break:

- authentication
- plans
- customers
- subscriptions
- usage ingestion
- daily aggregation

==================================================
49. GIT / PR
==================================================

Branch:

phase-07-billing-invoices

Commit:

feat: implement billing and invoices

Push branch.

Create PR.

Do NOT merge.

Do NOT start Phase 8.

Final response must report:

1. Files created/updated
2. Invoice schema
3. Invoice line schema
4. Billing formula
5. Overage calculation
6. Pricing segment handling
7. Proration formula
8. Rounding strategy
9. Historical pricing behavior
10. Billing queue
11. Chunking strategy
12. Duplicate invoice protection
13. Transaction handling
14. API endpoints
15. Authorization
16. Tenant isolation
17. Frontend implementation
18. Tests added
19. Full test results
20. Static analysis
21. Frontend/build results
22. Commit hash
23. PR URL
24. Assumptions/trade-offs

STOP after creating the PR.
