<!-- Transcript line 472; extracted for assignment prompt log -->

# Phase 4 — user prompt

# Phase 4 — Subscriptions & Plan Changes

Phase 1, Phase 2, and Phase 3 are complete.

Current PRs remain open and must NOT be merged by the coding agent.

Start Phase 4 from the current project state.

Create a new branch:

phase-04-subscriptions

Do NOT merge any PR.

After completing Phase 4:
1. Commit the changes.
2. Push the branch.
3. Create a PR.
4. Do NOT merge it.
5. Do NOT start Phase 5.
6. STOP.

Suggested commit:

feat: implement subscriptions and plan changes

---

# Objective

Implement the Subscription domain required by the assignment.

The assignment requires:

- Customers can subscribe to merchant plans.
- A subscription belongs to a customer and plan.
- Billing must later support mid-cycle upgrades/downgrades.
- Usage before a plan change must use the old plan's pricing.
- Usage after the change must use the new plan's pricing.
- Billing must later support proration across plan-change segments.

This phase establishes the subscription lifecycle and pricing history needed by future Usage and Billing phases.

---

# STRICT SCOPE

Implement ONLY:

- Subscription schema
- Subscription model
- Subscription creation
- Subscription retrieval
- Subscription update where appropriate
- Subscription cancellation
- Subscription plan changes
- Effective pricing snapshots
- Subscription periods
- Plan-change history
- Authorization
- Tenant isolation
- API
- Frontend
- Tests
- Documentation

DO NOT implement:

- Usage ingestion
- Usage aggregation
- Daily usage
- Invoices
- Billing calculation
- Overage calculation
- Proration calculation/payment
- Payments
- Dashboard
- Usage reports
- Payment gateway
- Taxes

Proration will be calculated in the Billing phase.

---

# 1. Core Domain

The relationship is:

Merchant
  ↓
Customer
  ↓
Subscription
  ↓
Plan

A subscription belongs to:

- one merchant
- one customer
- one plan

The customer and plan MUST belong to the same merchant.

Never allow:

Customer from Merchant A
+
Plan from Merchant B

to create a subscription.

---

# 2. Subscription Schema

Create a normalized `subscriptions` table.

At minimum:

- id
- public_id
- merchant_id
- customer_id
- plan_id
- status
- started_at
- current_period_start
- current_period_end
- base_price
- included_usage_units
- overage_rate
- billing_cycle
- cancelled_at
- timestamps

Use existing ULID/public-ID conventions.

IMPORTANT:

The pricing fields on the subscription are a SNAPSHOT of the pricing applicable when the subscription is created.

Do not rely exclusively on the current Plan values for historical billing.

---

# 3. Pricing Snapshot

This is important.

When a customer subscribes to a plan, copy the applicable pricing from the Plan into the Subscription:

- base_price
- included_usage_units
- overage_rate
- billing_cycle

Example:

Plan today:

base_price = 49900
included_usage_units = 10000
overage_rate = 5

Customer subscribes.

Subscription stores:

base_price = 49900
included_usage_units = 10000
overage_rate = 5

Later the merchant edits the Plan:

base_price = 59900

The existing Subscription MUST remain:

base_price = 49900

This guarantees future billing can use historical pricing.

Test this explicitly.

---

# 4. Subscription Status

Create an enum.

Suggested:

- active
- cancelled
- expired

Keep the lifecycle simple.

Do not introduce trialing, paused, past_due, etc. unless required by the assignment.

Document the lifecycle.

---

# 5. Billing Cycle

A subscription inherits its billing cycle from the selected Plan.

Support the existing Phase 2 values:

- monthly
- yearly

Do not duplicate business logic unnecessarily.

Store the billing cycle snapshot on the Subscription so future Plan changes cannot alter the existing subscription's billing cycle unexpectedly.

---

# 6. Subscription Period

When a subscription starts:

Set:

started_at
current_period_start
current_period_end

For monthly subscriptions:

current_period_start → one billing month later

For yearly subscriptions:

current_period_start → one billing year later

Use calendar-aware date handling.

Do NOT approximate a month/year using a fixed number of seconds or days.

Document the period calculation.

---

# 7. Mid-Cycle Plan Changes

This is a major requirement.

A customer must be able to change plans while their subscription is active.

Support:

- upgrade
- downgrade

A plan change must create a historical segment.

Example:

Subscription starts:

Jan 1
Plan A

Customer changes:

Jan 15
Plan B

Historical pricing:

Jan 1 → Jan 15
Plan A pricing

Jan 15 → period end
Plan B pricing

Future Billing will use these segments to calculate the correct charges.

---

# 8. Subscription Plan Changes Table

Create:

`subscription_plan_changes`

At minimum:

- id
- public_id
- subscription_id
- from_plan_id
- to_plan_id
- effective_at
- from_base_price
- from_included_usage_units
- from_overage_rate
- from_billing_cycle
- to_base_price
- to_included_usage_units
- to_overage_rate
- to_billing_cycle
- timestamps

The exact schema may be simplified if the implementation can preserve the same historical information reliably.

IMPORTANT:

Historical pricing must not depend on the current Plan record.

---

# 9. Effective Pricing

When a plan change occurs, snapshot the NEW plan pricing.

The historical segment must contain enough information for future billing to determine:

- applicable plan
- base price
- included usage
- overage rate
- billing cycle
- effective start/end

Do not calculate proration in this phase.

Only preserve the data required for the future billing calculation.

---

# 10. Plan Change Timing

Require an effective timestamp.

For this assignment, plan changes should take effect immediately.

Example:

Current time = 2026-09-29 15:00

Customer changes from Plan A to Plan B.

Plan B becomes effective at that timestamp.

Usage occurring before that timestamp belongs to the old pricing segment.

Usage occurring after that timestamp belongs to the new pricing segment.

This timestamp is critical for Phase 5 Usage ingestion.

---

# 11. Prevent Invalid Plan Changes

Validate:

- subscription must be active
- target plan must belong to the same merchant
- target plan must be active
- target plan must differ from the current plan

Prevent changing a cancelled/expired subscription.

Prevent cross-tenant plan changes.

---

# 12. Cancellation

Implement safe cancellation.

Suggested behavior:

- subscription status → cancelled
- cancelled_at populated
- preserve all historical subscription information

Do not physically delete subscriptions.

Do not implement refunds.

Do not implement billing adjustments.

Those belong to future billing functionality.

---

# 13. Subscription API

Implement:

POST   /api/v1/subscriptions
GET    /api/v1/subscriptions
GET    /api/v1/subscriptions/{subscription}
POST   /api/v1/subscriptions/{subscription}/change-plan
POST   /api/v1/subscriptions/{subscription}/cancel

Follow the project's existing API conventions.

---

# 14. Create Subscription

Example:

POST /api/v1/subscriptions

{
  "customer_id": "customer-public-id",
  "plan_id": "plan-public-id"
}

The API must:

1. Resolve authenticated merchant.
2. Resolve customer within that merchant.
3. Resolve plan within that merchant.
4. Verify customer is active.
5. Verify plan is active.
6. Create subscription.
7. Snapshot plan pricing.
8. Calculate initial billing period dates.
9. Return subscription.

Do NOT accept:

- merchant_id
- base_price
- overage_rate
- included_usage_units
- billing_cycle

from the client.

These values must come from the selected Plan.

---

# 15. Duplicate Active Subscription

Decide and document whether a customer can have multiple active subscriptions.

For this assignment, prefer:

ONE active subscription per customer.

Enforce this at the application/database level as reliably as practical.

Attempting to create another active subscription for the same customer should fail with a clear validation/business error.

Document the assumption.

---

# 16. List Subscriptions

Implement pagination.

Support useful filters:

- status
- customer
- plan

Examples:

GET /api/v1/subscriptions?status=active

GET /api/v1/subscriptions?customer_id=...

GET /api/v1/subscriptions?plan_id=...

All queries MUST remain tenant scoped.

Do not expose subscriptions belonging to another merchant.

---

# 17. Subscription Detail

Return:

- public_id
- customer
- plan
- status
- started_at
- current_period_start
- current_period_end
- billing_cycle
- base_price
- included_usage_units
- overage_rate
- cancelled_at

Also expose relevant plan-change history.

Do not expose internal IDs.

---

# 18. Change Plan API

Implement:

POST /api/v1/subscriptions/{subscription}/change-plan

Request:

{
  "plan_id": "target-plan-public-id"
}

Backend:

1. Resolve subscription within tenant.
2. Verify active.
3. Resolve target plan within same tenant.
4. Verify target plan is active.
5. Verify target plan differs from current plan.
6. Capture current pricing.
7. Capture target pricing.
8. Create plan-change history.
9. Update subscription's current plan.
10. Update current pricing snapshot.
11. Record effective timestamp.

Do NOT calculate monetary proration.

Do NOT create an invoice.

Do NOT charge a payment.

---

# 19. Cancellation API

Implement:

POST /api/v1/subscriptions/{subscription}/cancel

Only active subscriptions can be cancelled.

Cancellation should be idempotent where practical.

Preserve historical records.

---

# 20. Authorization

Add subscription permissions:

subscriptions.view
subscriptions.create
subscriptions.update
subscriptions.change-plan
subscriptions.cancel

Follow the existing authorization architecture.

Suggested:

Owner:
- all subscription permissions

Member:
- subscriptions.view

Do not automatically grant mutation permissions to Member.

Backend authorization is mandatory.

---

# 21. Tenant Isolation

Use the existing MerchantContext.

Never create a second tenancy mechanism.

Test:

- Merchant A cannot access Merchant B subscription.
- Merchant A cannot change Merchant B subscription.
- Merchant A cannot cancel Merchant B subscription.
- Merchant A cannot use Merchant B customer.
- Merchant A cannot use Merchant B plan.
- merchant_id injection fails.

Cross-tenant resources should follow the established 404 behavior.

---

# 22. Route Model Binding

Follow the secure route-binding approach established in Phase 2/3.

A subscription public ID must resolve only within the authenticated merchant.

Avoid:

Subscription::find($id)

for tenant-sensitive endpoints.

Use tenant-scoped resolution.

---

# 23. Frontend

Create:

## Subscriptions List

Display:

- Customer
- Plan
- Status
- Billing cycle
- Base price
- Included usage
- Overage rate
- Current period
- Actions

Support:

- pagination
- filters

## Create Subscription

Allow:

- select customer
- select active plan
- create subscription

Do not allow the frontend to submit pricing fields.

## Subscription Detail

Show:

- customer
- current plan
- pricing snapshot
- billing period
- status
- plan-change history

## Change Plan

Allow authorized users to select a new active plan.

Display the effective date/time.

Do not show a fake proration amount.

## Cancel

Require confirmation.

Use the existing Vue/TypeScript architecture.

---

# 24. Tests

This phase requires extensive tests because future billing depends on historical correctness.

## Subscription creation

Test:

- valid subscription
- inactive customer rejected
- inactive plan rejected
- missing customer
- missing plan
- invalid customer
- invalid plan
- cross-tenant customer
- cross-tenant plan
- duplicate active subscription

## Pricing snapshot

Critical tests:

1. Create subscription using Plan A.
2. Change Plan A's price.
3. Verify subscription pricing remains unchanged.

Test all snapshot fields:

- base_price
- included_usage_units
- overage_rate
- billing_cycle

## Billing period

Test:

- monthly period
- yearly period
- month boundaries
- year boundaries
- leap-year behavior where applicable

Do not use fixed-day approximations.

## Plan changes

Test:

- upgrade
- downgrade
- target plan inactive
- target plan belongs to another merchant
- same plan rejected
- cancelled subscription rejected
- expired subscription rejected

Verify:

- effective_at
- old pricing
- new pricing
- current subscription plan
- current subscription pricing

## Historical segments

Example test:

Plan A:
base = 10000
overage = 5

Plan B:
base = 20000
overage = 10

Subscription starts on Jan 1.

Change to Plan B on Jan 15.

Verify historical data contains:

Jan 1 → Jan 15 = Plan A pricing

Jan 15 → period end = Plan B pricing

Do NOT calculate proration yet.

## Cancellation

Test:

- active → cancelled
- cancelled_at populated
- cannot cancel invalid subscription
- historical data remains

## Authorization

Test Owner vs Member.

## Tenant isolation

Explicitly test all cross-tenant combinations.

---

# 25. Concurrency / Data Integrity

Think about simultaneous requests.

Protect against:

- two concurrent subscriptions for the same customer
- two simultaneous plan changes
- plan change against a cancelled subscription

Use database transactions and appropriate locking/constraints where justified.

Do not over-engineer distributed locking.

Document important concurrency decisions.

---

# 26. Database Indexes

Add indexes based on actual query patterns.

Consider:

subscriptions:
- merchant_id
- merchant_id + status
- merchant_id + customer_id
- merchant_id + plan_id

subscription_plan_changes:
- subscription_id
- subscription_id + effective_at

Avoid unnecessary indexes.

---

# 27. Transactions

Subscription creation should be transactional.

Plan change should be transactional.

Cancellation should be transactional where multiple records are updated.

A plan change must not leave the system in a state where:

- subscription points to Plan B
- but plan-change history was not created

or vice versa.

---

# 28. Documentation

Update README / docs/assumptions.md.

Document:

## Subscription lifecycle

active → cancelled / expired

## Pricing snapshots

Explain why subscription pricing is copied from the plan.

## Plan changes

Explain effective timestamps and historical pricing.

## Proration

Explicitly state:

Proration calculation is NOT implemented in Phase 4.

Phase 4 only preserves the information required for future billing.

## One active subscription

Document the assumption that a customer can have one active subscription.

---

# 29. Quality Checks

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

Verify all existing Phase 1–3 tests continue passing.

---

# 30. Assignment Alignment

Before creating the PR verify:

- Customers can subscribe to Plans.
- Subscription belongs to correct merchant.
- Pricing is snapshotted.
- Billing cycle is snapshotted.
- Mid-cycle plan changes are recorded.
- Old and new pricing are preserved.
- Effective timestamps are recorded.
- Cancellation is supported.
- Tenant isolation is enforced.
- Authorization is enforced.
- Future billing has enough information to calculate proration.
- No usage ingestion was implemented.
- No invoice generation was implemented.
- No payment processing was implemented.

---

# 31. Git / PR

Branch:

phase-04-subscriptions

Commit:

feat: implement subscriptions and plan changes

Push branch.

Create PR.

Do NOT merge.

Do NOT start Phase 5.

Final response must report:

1. Files created/updated
2. Database schema
3. Subscription lifecycle
4. Pricing snapshot design
5. Plan-change design
6. Effective timestamp behavior
7. API endpoints
8. Authorization
9. Tenant isolation
10. Transaction/concurrency handling
11. Frontend implementation
12. Tests added
13. Full test results
14. Static analysis results
15. Frontend build results
16. Commit hash
17. PR URL
18. Assumptions/trade-offs

STOP after creating the PR.
