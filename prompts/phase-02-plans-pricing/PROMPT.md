<!-- Transcript line 301; extracted for assignment prompt log -->

# Phase 2 — user prompt

# Phase 2 — Plans & Pricing

Phase 1 is complete and its PR is open. Do NOT modify or redo Phase 1 unless a small compatibility change is strictly required.

Start Phase 2 from the current Phase 1 branch/state.

## Git

Create a new branch:

phase-02-plans

Do NOT merge any PR.

After completing Phase 2:
1. Commit the changes.
2. Push the branch.
3. Create a PR.
4. STOP.
5. Do NOT start Phase 3.

Suggested commit:

feat: implement plans and pricing

---

# Objective

Implement the Merchant Plan management required by the assignment.

The assignment requires merchants to define Plans containing:

- Plan name
- Base price
- Billing cycle
- Included usage units
- Overage rate per usage unit

This phase must establish the pricing foundation that will later be consumed by Subscriptions, Usage Metering, and Billing.

Keep the implementation simple, normalized, testable, and extensible.

---

# IMPORTANT SCOPE

Implement ONLY:

- Plans
- Plan pricing data
- Plan validation
- Plan CRUD API
- Plan frontend management
- Plan authorization foundation
- Plan tests
- Documentation

DO NOT implement:

- Customers
- Subscriptions
- Usage Events
- Daily Usage
- Invoices
- Billing calculations
- Overage calculations
- Proration
- Dashboard
- Usage aggregation
- Payment processing
- Coupons/promo codes
- Taxes
- Multiple currencies unless already required by the existing architecture

Do not prematurely implement future-phase functionality.

---

# 1. Database Design

Create a normalized `plans` table.

At minimum:

- id
- public_id
- merchant_id
- name
- base_price
- billing_cycle
- included_usage_units
- overage_rate
- status
- timestamps

Use the project's existing ID/public-ID conventions.

## Pricing

Do NOT use floating-point database columns for money.

Use integer minor units.

Example:

₹499.00 → 49900 paise

Therefore:

base_price = integer

overage_rate = integer minor-unit amount per usage unit

Document this decision.

Do not use PHP floating-point arithmetic for monetary calculations.

Billing calculations themselves belong to a later phase.

---

# 2. Billing Cycle

Create a proper enum/value representation for billing cycle.

At minimum support:

- monthly
- yearly

Use the project's existing enum conventions.

Do not hard-code billing-cycle strings throughout controllers/services.

Make validation reject unsupported values.

---

# 3. Plan Status

Introduce a plan status representation.

Suggested:

- active
- archived

An archived plan should remain in the database for historical references but should not be available for creating new subscriptions later.

Do not delete plans unnecessarily.

Document the lifecycle.

---

# 4. Merchant Ownership / Tenant Isolation

Every plan belongs to exactly one merchant.

Relationship:

Merchant
  ↓
Plans

A user must only be able to access plans belonging to their authenticated merchant.

NEVER trust a client-supplied merchant_id.

Always derive the merchant from the authenticated user's tenant context.

Test:

- Merchant A cannot read Merchant B's plan.
- Merchant A cannot update Merchant B's plan.
- Merchant A cannot delete/archive Merchant B's plan.
- Merchant A cannot manipulate merchant_id to access another tenant.

Use the existing `MerchantContext` / tenant-resolution architecture from Phase 1.

Do not create a second tenant-resolution mechanism.

---

# 5. Plan API

Follow the existing API versioning and response conventions.

Implement:

POST   /api/v1/plans
GET    /api/v1/plans
GET    /api/v1/plans/{plan}
PUT    /api/v1/plans/{plan}
DELETE /api/v1/plans/{plan}

If the existing architecture prefers PATCH instead of PUT, follow the existing convention consistently.

## Create

Request example:

{
  "name": "Professional",
  "base_price": 49900,
  "billing_cycle": "monthly",
  "included_usage_units": 10000,
  "overage_rate": 5
}

Validate:

- name required
- name appropriate length
- name unique within merchant
- base_price integer and >= 0
- billing_cycle valid enum
- included_usage_units integer and >= 0
- overage_rate integer and >= 0

Do not accept:

- merchant_id
- internal IDs
- status changes that bypass authorization

from untrusted client input unless explicitly required by the API design.

---

# 6. Plan Listing

Implement pagination.

Example:

GET /api/v1/plans

Return only plans belonging to the authenticated merchant.

Support reasonable filtering if useful, but do not over-engineer the endpoint.

At minimum allow filtering by status if the architecture supports it.

Return:

- public_id
- name
- base_price
- billing_cycle
- included_usage_units
- overage_rate
- status
- timestamps

Do not expose internal sequential database IDs.

---

# 7. Plan Detail

GET /api/v1/plans/{plan}

Return the plan only if it belongs to the authenticated merchant.

Cross-tenant access must not expose whether another merchant's plan exists.

Use the project's established authorization/not-found convention.

---

# 8. Plan Update

Allow updating editable plan fields according to the design.

Important:

Think about pricing history.

Do NOT introduce a complicated versioning system unless required at this stage.

However, document the important assumption:

Once subscriptions exist, historical billing must not accidentally change because a plan's current price was edited.

If necessary, design the plan model so a future subscription/pricing snapshot can preserve the price applicable at subscription/change time.

Do not implement subscription pricing snapshots yet.

---

# 9. Plan Deletion / Archiving

Avoid destructive deletion when a plan could later be referenced by subscriptions or invoices.

Prefer archiving/deactivation semantics.

If DELETE is retained for the API contract, implement it as a safe archive operation or clearly document the behavior.

Do not physically delete a plan if doing so would compromise future historical billing integrity.

---

# 10. Business Logic

Keep responsibilities separated.

Recommended structure:

Controller
    ↓
Form Request
    ↓
Action / Service
    ↓
Model / Repository if already established
    ↓
Database

Do not put substantial business logic directly inside controllers.

Database relationships/query concerns should remain close to the models according to the project's existing architecture.

Keep the implementation consistent with Phase 1.

---

# 11. Authorization

Add plan-specific authorization.

Introduce permissions/authorization only as needed for Plans.

Suggested permissions:

plans.view
plans.create
plans.update
plans.archive

Follow the existing authorization architecture if Phase 1 already established one.

If Phase 1 intentionally deferred the full permission system, use Laravel Policies/Gates or the existing role mechanism rather than introducing an unrelated authorization framework.

Owner should have plan management access.

Member should not automatically receive plan mutation privileges.

Document the decision.

---

# 12. Caching

The assignment explicitly requires:

"Cache plan/pricing lookups and document invalidation."

Start the caching foundation in this phase.

Cache appropriate read operations, especially plan/pricing lookups.

Requirements:

- Cache plan lookup where beneficial.
- Cache must be scoped by merchant.
- Never allow cached data from Merchant A to be returned to Merchant B.
- Invalidate/update cache when a plan is created, updated, archived, or otherwise changes.
- Document cache key structure and invalidation strategy.

Example concept:

plans:{merchant_public_id}:{plan_public_id}

Do not cache everything blindly.

Do not introduce unnecessary distributed-cache complexity.

Use the project's Redis/cache configuration.

Add tests where practical to verify invalidation behavior.

---

# 13. API Resources

Use the existing Resource pattern.

Create/update:

PlanResource

Do not expose:

- internal database IDs
- sensitive/internal fields

Expose public identifiers consistently with Phase 1.

---

# 14. Frontend

Create a clean but simple Plans management UI.

Do NOT spend excessive time on visual polish.

The assignment explicitly prioritizes backend architecture, schema, aggregation, caching, and system design.

Implement:

## Plans List

Show:

- Plan name
- Base price
- Billing cycle
- Included usage
- Overage rate
- Status
- Actions

## Create Plan

Form fields:

- Name
- Base price
- Billing cycle
- Included usage units
- Overage rate

## Edit Plan

Allow editing supported fields.

## Archive

Provide a safe archive action with confirmation.

Use the existing frontend:

- Vue 3
- TypeScript
- Vite
- existing API client
- existing authentication state
- existing layout

Do not introduce another UI framework.

---

# 15. Frontend Authorization

Use the backend as the security authority.

Frontend permission checks are only for UX.

For example:

- Hide Create Plan if user cannot create plans.
- Hide Edit if user cannot update plans.
- Hide Archive if user cannot archive plans.

A malicious client must still be rejected by the backend.

---

# 16. Tests

Every important behavior must have tests.

## Model tests

Test:

- Merchant relationship
- Plan relationships
- billing cycle enum
- status enum
- casts

## API tests

Test:

### Create
- valid plan
- missing name
- invalid name
- negative base price
- invalid billing cycle
- negative included usage
- negative overage rate
- duplicate plan name within merchant

### List
- returns merchant's plans
- pagination
- does not return another merchant's plans

### Show
- own merchant plan accessible
- cross-tenant plan inaccessible

### Update
- valid update
- validation failures
- cross-tenant update blocked

### Archive
- plan becomes archived
- cross-tenant archive blocked
- archived plan behavior verified

### Authorization
- owner allowed
- unauthorized member denied where applicable

## Cache tests

Test:

- plan lookup can be cached
- update invalidates old cached data
- archive invalidates cache
- merchant cache isolation

## Security

Test:

- client cannot override merchant_id
- mass assignment cannot alter merchant ownership
- internal IDs are not exposed
- cross-tenant access is blocked

---

# 17. Factories

Create/update:

PlanFactory

Factories should support useful states, for example:

- active
- archived
- monthly
- yearly

Use factories in tests instead of manually constructing database records wherever practical.

---

# 18. Documentation

Update README and/or architecture documentation with:

## Plan schema

Explain:

- base price
- included units
- overage rate
- billing cycle
- status

## Money representation

Explain integer minor units.

Example:

₹499.00 = 49900 paise

## Plan lifecycle

Explain active → archived.

## Tenant isolation

Explain how plans are scoped to merchants.

## Cache strategy

Document:

- cache key
- what is cached
- invalidation triggers
- merchant isolation

## Future billing consideration

Document that future subscriptions/invoices must preserve the pricing applicable at the time of subscription/plan change.

---

# 19. Performance / Indexing

Add appropriate indexes.

At minimum consider:

- merchant_id
- merchant_id + status
- merchant_id + name
- unique merchant_id + name

Choose indexes based on actual query patterns.

Do not blindly add excessive indexes.

Document important indexing decisions.

---

# 20. Quality Checks

Before finishing, run:

Backend:

- migrations
- tests
- Pest/PHPUnit
- Laravel Pint
- Larastan

Frontend:

- tests
- vue-tsc
- ESLint
- Prettier
- Vite build

Verify the complete application works from a clean database.

Run:

php artisan migrate:fresh

and the appropriate seed/bootstrap command already established by Phase 1.

Do not modify Phase 1 behavior unnecessarily.

---

# 21. Final Review Against Assignment

Before creating the PR, explicitly verify:

- Merchant can define plans.
- Plan has base price.
- Plan has billing cycle.
- Plan has included usage units.
- Plan has overage rate.
- Money uses integer minor units.
- Plans are tenant-isolated.
- Plan reads are cacheable.
- Cache invalidation is implemented/documented.
- Plan APIs are tested.
- Authorization is enforced.
- Frontend plan management works.
- No Customers/Subscriptions/Usage/Billing functionality was introduced.

---

# 22. Git / PR

Commit only Phase 2 changes.

Suggested commit:

feat: implement plans and pricing

Push:

phase-02-plans

Create a PR.

Do NOT merge it.

Do NOT start Phase 3.

Final response must report:

1. Files created/updated
2. Database schema
3. API endpoints
4. Authorization
5. Cache strategy
6. Cache invalidation
7. Frontend implementation
8. Tests added
9. Test results
10. Static analysis results
11. Build results
12. Git commit hash
13. PR URL
14. Any assumptions/trade-offs

STOP after creating the PR.
