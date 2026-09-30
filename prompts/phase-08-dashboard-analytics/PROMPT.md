<!-- Transcript line 761; extracted for assignment prompt log -->

# Phase 8 — user prompt

We are continuing the SaaS Billing & Usage-Metering System assignment.

Phase 7 is COMPLETE.

Branch:
phase-07-billing-invoices

PR:
#8 — https://github.com/aravindharov/saas-billing-metering/pull/8

Commit:
340f4634e5e531ea517978b149b83253ed3fe979

IMPORTANT:
- Do NOT merge PR #8.
- Start Phase 8 from the current Phase 7 branch state.
- Create a NEW branch:
  phase-08-dashboard-analytics
- Follow the existing architecture, conventions, tests, tenant isolation, and coding standards.
- Do not rewrite or refactor completed phases unless required for Phase 8.
- Continue module-by-module autonomously.
- After completing Phase 8, run the complete test/static-analysis/build suite.
- Commit the changes.
- Create a PR only.
- Do NOT merge the PR.
- Stop after PR creation.

==================================================
PHASE 8 — MERCHANT DASHBOARD & ANALYTICS
==================================================

Goal:

Implement the merchant dashboard required by the assignment.

The dashboard must provide:

1. Top 5 customers by usage this month.
2. Projected overage revenue for the current billing cycle.
3. Customers whose usage has dropped by more than 50% month-over-month.

The implementation must use the existing:
- MerchantContext
- Customers
- Subscriptions
- Subscription plan-change history
- DailyUsage
- Billing/pricing logic where appropriate

Do NOT calculate dashboard usage by scanning the entire raw usage_events table unless absolutely necessary.

Prefer daily_usage for analytics.

==================================================
1. DASHBOARD API
==================================================

Implement:

GET /api/v1/merchants/{merchant}/dashboard

Use the existing merchant public ID / route-binding conventions.

Important security requirements:

- Never trust a merchant ID supplied by the client to bypass tenancy.
- The requested merchant must belong to the authenticated user's merchant context.
- Cross-tenant access must return 404/403 according to the existing application convention.
- All customer, subscription and usage queries must remain tenant scoped.

Create appropriate:
- DashboardController
- DashboardService / query services where appropriate
- DTOs/resources if consistent with the existing architecture
- validation/request objects if required
- policy/authorization where required

Do not put large analytical queries directly inside the controller.

==================================================
2. TOP 5 CUSTOMERS BY USAGE
==================================================

Return the top 5 customers by usage for the current calendar month.

Use:

daily_usage

Calculate:

current_month_start = first day of current UTC month
current_time = current UTC time

Aggregate:

SUM(daily_usage.total_units)

GROUP BY customer.

Return the top 5 ordered by total usage descending.

Include at minimum:

- customer public_id
- customer name
- customer email
- total usage units

Example response structure:

{
  "top_customers": [
    {
      "customer_id": "...",
      "name": "Acme Corp",
      "email": "billing@acme.com",
      "usage_units": 12500
    }
  ]
}

Only include customers belonging to the authenticated merchant.

Handle customers with no usage correctly.

==================================================
3. CURRENT BILLING CYCLE
==================================================

Determine the current billing cycle from the customer's active subscription.

Use the existing subscription fields:

- current_period_start
- current_period_end

Do not invent a second billing-cycle implementation.

Respect existing calendar-aware monthly/yearly period logic.

Subscriptions with cancelled/expired status must follow the existing subscription semantics.

==================================================
4. PROJECTED OVERAGE REVENUE
==================================================

Implement projected overage revenue for the current billing cycle.

This must be based on actual usage recorded so far in the current cycle.

Use daily_usage as the primary read model.

Do NOT use the current Plan catalog price.

Use the subscription pricing snapshot and plan-change pricing segments already implemented in Phase 4/7.

For each applicable pricing segment:

- Determine segment start/end within the current billing cycle.
- Determine usage accumulated so far for that segment.
- Determine included usage units for that pricing segment.
- Determine overage rate for that pricing segment.

Current overage:

max(0, usage_so_far - included_usage_units)
× overage_rate

Projection:

Use a documented, deterministic usage projection based on usage accumulated during the elapsed portion of the current billing cycle.

Recommended approach:

projected_usage =
    usage_so_far / elapsed_fraction_of_cycle

Then:

projected_overage_units =
    max(0, projected_usage - included_usage_units)

projected_overage_revenue =
    projected_overage_units × overage_rate

IMPORTANT:

- Avoid floating-point money calculations.
- Use integer-safe arithmetic.
- Follow the same rounding principles already established by Phase 7.
- Never produce negative projected overage.
- Do not project based on future events.
- Do not use live Plan pricing.
- Respect plan changes inside the current billing cycle.

Document the exact projection assumption in:

docs/assumptions.md

For example:

"Projected overage revenue estimates the remaining-cycle usage using the average usage rate observed during the elapsed portion of the current billing cycle. It is an estimate only and is not an invoice amount."

If there are no active subscriptions/current-cycle usage, return zero rather than failing.

==================================================
5. MONTH-OVER-MONTH USAGE DROP
==================================================

Return customers whose usage dropped by more than 50%.

Use daily_usage.

Avoid comparing a complete previous month against an incomplete current month.

Use a fair month-to-date comparison.

For example:

Current period:
first day of current UTC month → today

Previous comparison period:
first day of previous UTC month → equivalent elapsed day,
clamped to the previous month's final day.

Calculate:

current_usage
previous_usage

A customer qualifies when:

current_usage < previous_usage * 0.5

Only customers with meaningful previous usage should be considered.

Do not flag a customer whose previous usage was zero.

Return:

- customer public_id
- customer name
- current usage
- previous usage
- percentage change

Example:

{
  "usage_drops": [
    {
      "customer_id": "...",
      "name": "Example Corp",
      "current_usage_units": 400,
      "previous_usage_units": 1000,
      "percentage_change": -60
    }
  ]
}

Use integer-safe calculations where possible.

Document the exact comparison window and threshold.

==================================================
6. DASHBOARD RESPONSE
==================================================

Create a clean response structure such as:

{
  "period": {
    "month_start": "...",
    "month_end": "..."
  },

  "top_customers": [],

  "projected_overage_revenue": {
    "amount": 125000,
    "currency": "INR",
    "unit": "paise"
  },

  "usage_drops": []
}

You may add useful metadata such as:

- generated_at
- current cycle information

but do not over-engineer the response.

==================================================
7. DATABASE PERFORMANCE
==================================================

The assignment expects the system to handle 50L+ usage-event rows.

Dashboard queries MUST NOT repeatedly aggregate the entire raw usage_events table.

Use:

daily_usage

where possible.

Review existing indexes and add only indexes that materially improve dashboard queries.

Potential query patterns:

- merchant + usage_date
- merchant + customer + usage_date

Avoid unnecessary indexes.

Use efficient SQL aggregation.

Avoid N+1 queries.

Do not load thousands of customers/subscriptions into PHP just to calculate dashboard values.

Prefer database-side aggregation.

Document why the dashboard uses daily_usage instead of raw usage_events.

==================================================
8. CACHING
==================================================

Do not introduce unnecessary caching complexity.

Existing Phase 2 plan/pricing cache behavior must remain unchanged.

If you add dashboard caching:

- use a short TTL
- make the cache merchant scoped
- document the TTL
- ensure stale dashboard values are acceptable
- avoid returning data from another merchant's cache key

A reasonable implementation is:

dashboard:{merchant_id}:{period}

with a short TTL.

However, only introduce this if it provides a clear benefit and fits the existing architecture.

Do NOT build a complex cache invalidation system in this phase.

==================================================
9. FRONTEND DASHBOARD
==================================================

Update the existing merchant dashboard/home page.

Create a clean SaaS dashboard UI containing:

--------------------------------------------------
Usage & Billing Dashboard

[ Current Month Usage ]
[ Projected Overage Revenue ]
[ Active Customers / useful summary ]

Top Customers
--------------------------------
Customer       Usage
Acme Corp      12,500
Beta Ltd        8,200
...

Usage Drops >50% MoM
--------------------------------
Customer       Previous   Current   Change
...

Current Billing Cycle
--------------------------------
Cycle Start
Cycle End
Projected Overage
--------------------------------------------------

Keep the UI professional and consistent with the existing Vue application.

Do not spend excessive time on visual polish.

The assignment explicitly evaluates backend/schema/aggregation/caching more heavily than UI polish.

Handle:

- loading state
- empty state
- API error
- no usage
- no usage drops
- no active subscriptions

Use existing frontend patterns/components where possible.

==================================================
10. TESTS — REQUIRED
==================================================

Add comprehensive backend tests.

Minimum cases:

Dashboard:

1. authenticated merchant can access dashboard
2. unauthenticated user rejected
3. cross-tenant merchant access rejected
4. top 5 customers ordered by usage
5. only top 5 returned
6. current calendar month filtering
7. customers with no usage handled correctly
8. projected overage with usage below included units
9. projected overage when usage exceeds included units
10. projected revenue uses subscription pricing snapshot
11. changing Plan catalog price does not affect dashboard historical pricing
12. plan change inside current cycle handled correctly
13. no active subscription returns zero projected overage
14. usage drop >50% detected
15. usage drop exactly 50% is NOT included
16. usage drop below 50% is NOT included
17. previous zero usage is ignored
18. month boundary handled correctly
19. previous month shorter than current month handled correctly
20. multiple customers isolated correctly
21. no N+1 behavior if practical to test
22. dashboard uses daily_usage rather than raw event aggregation where practical

Add unit tests for calculation services where appropriate.

Frontend:

- dashboard renders
- loading state
- top customers
- projected revenue
- usage drops
- empty state
- API failure state

All tests must use mocks/factories and MUST NOT depend on real external services.

==================================================
11. STATIC ANALYSIS / QUALITY
==================================================

Before completion run:

- Pest/PHPUnit complete backend suite
- frontend Vitest suite
- Laravel Pint
- PHPStan/Larastan
- vue-tsc
- ESLint
- Prettier
- Vite production build

Fix all failures.

Do not weaken static-analysis configuration merely to make the phase pass.

Do not reduce test coverage.

==================================================
12. DOCUMENTATION
==================================================

Update:

README.md
docs/assumptions.md

Document:

- dashboard endpoint
- top-5 calculation
- projected overage methodology
- month-over-month comparison methodology
- daily_usage as dashboard read model
- tenant isolation
- caching decision
- performance considerations
- any assumptions/trade-offs

Also update the phase/status section if the repository maintains one.

==================================================
13. GIT WORKFLOW
==================================================

Create branch:

phase-08-dashboard-analytics

Do not modify or merge previous PRs.

After implementation:

1. Run all backend tests.
2. Run all frontend tests.
3. Run static analysis.
4. Run frontend build.
5. Review git diff.
6. Confirm no secrets/debug code.
7. Commit:

feat: implement merchant dashboard analytics

8. Push branch.
9. Create PR targeting the existing main branch.
10. PR description must contain:
   - Summary
   - Dashboard features
   - Calculation methodology
   - Performance considerations
   - Tests executed
   - Assumptions/trade-offs

Do NOT merge the PR.

STOP after PR creation.

==================================================
IMPORTANT ARCHITECTURE
==================================================

The final architecture should remain:

Plans
  ↓
Subscription pricing snapshot
  ↓
Subscription plan-change history
  ↓
Usage Events
  ↓
Daily Usage
  ↓
Billing Calculator → Invoices

And now:

Daily Usage
  ↓
Merchant Dashboard / Analytics

Keep raw usage_events immutable.

Keep daily_usage rebuildable.

Keep billing calculations separate from dashboard presentation logic.

Do not introduce payments, taxes, refunds, coupons, dunning, or unrelated SaaS functionality.

Focus strictly on the assignment requirements.
