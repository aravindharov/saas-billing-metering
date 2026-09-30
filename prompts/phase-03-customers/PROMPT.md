<!-- Transcript line 403; extracted for assignment prompt log -->

# Phase 3 — user prompt

# Phase 3 — Customers

Phase 1 and Phase 2 are complete and their PRs are open.

Start Phase 3 from the current project state.

Create a new branch:

phase-03-customers

Do NOT merge any PR.

After completing Phase 3:
1. Commit the changes.
2. Push the branch.
3. Create a PR.
4. Do NOT merge it.
5. Do NOT start Phase 4.
6. STOP.

Suggested commit:

feat: implement customer management

---

# Objective

Implement the Customer domain required by the Subscription Billing & Usage-Metering assignment.

The assignment requires:

Merchant
  ↓
Customers
  ↓
Subscriptions

Customers are the entities that will later subscribe to merchant plans and generate usage.

This phase must establish a clean Customer foundation without implementing subscriptions or billing.

---

# STRICT SCOPE

Implement ONLY:

- Customer database model
- Customer CRUD
- Customer validation
- Customer API
- Customer authorization
- Customer tenant isolation
- Customer search/listing
- Customer frontend
- Customer factories
- Customer tests
- Customer indexing
- Customer documentation

DO NOT implement:

- Subscriptions
- Usage Events
- Daily Usage
- Usage aggregation
- Invoices
- Billing calculations
- Overage calculations
- Proration
- Plan assignment to customers
- Payment processing
- Dashboard
- Customer usage statistics
- Customer billing history

Do not start Phase 4 functionality.

---

# 1. Database Schema

Create a normalized `customers` table.

At minimum consider:

- id
- public_id
- merchant_id
- name
- email
- external_reference or customer_reference if useful
- status
- timestamps

Use the existing project's public ULID conventions.

Do not expose internal sequential IDs through the API.

---

# 2. Customer Identity

A Customer belongs to exactly one Merchant.

Relationship:

Merchant
  ↓
Customers

A customer record must never be accessible outside its merchant.

Do not trust a client-supplied:

merchant_id

Always derive the merchant from the authenticated user's MerchantContext.

---

# 3. Customer Fields

Use appropriate validation.

Suggested fields:

### name
Required.

Reasonable maximum length.

### email
Required if the chosen customer model requires it.

Validate as a valid email address.

### external_reference
Optional.

This can allow the merchant to associate the customer with an external CRM/account/customer identifier.

If implemented, make uniqueness scoped to the merchant.

### status

Use an enum.

Suggested values:

- active
- inactive

Document the lifecycle.

Do not physically delete customers unnecessarily because future subscriptions and billing records may reference them.

---

# 4. Customer API

Follow the existing API conventions from Phase 1 and Phase 2.

Implement:

POST   /api/v1/customers
GET    /api/v1/customers
GET    /api/v1/customers/{customer}
PUT    /api/v1/customers/{customer}
DELETE /api/v1/customers/{customer}

If DELETE is implemented as an archive/deactivation operation, document it clearly.

---

# 5. Create Customer

Example:

POST /api/v1/customers

{
  "name": "John Smith",
  "email": "john@example.com",
  "external_reference": "CRM-10001"
}

Validate:

- name required
- name length
- email valid
- external_reference length if provided
- merchant ownership must never come from request data

Do not allow the client to assign:

- merchant_id
- internal id
- unauthorized status changes

unless explicitly allowed by the authorization design.

---

# 6. List Customers

Implement:

GET /api/v1/customers

Requirements:

- tenant scoped
- pagination
- stable ordering
- optional status filtering
- search by name/email/external reference where appropriate

Example:

GET /api/v1/customers?search=john
GET /api/v1/customers?status=active

Do not load all customers into memory.

Use database pagination.

Ensure queries use appropriate indexes.

---

# 7. Customer Detail

Implement:

GET /api/v1/customers/{customer}

A customer belonging to another merchant must not be exposed.

Follow the same cross-tenant behavior established in Phase 2.

Do not reveal whether the foreign customer exists.

---

# 8. Update Customer

Implement:

PUT /api/v1/customers/{customer}

Allow updating appropriate customer fields.

Do not allow:

- merchant reassignment
- changing internal IDs
- arbitrary authorization fields

Ensure tenant ownership remains immutable.

---

# 9. Delete / Deactivate

Prefer a safe lifecycle rather than destructive deletion.

If using:

DELETE /api/v1/customers/{customer}

make it perform a safe deactivation/archive operation consistent with the project's conventions.

Do not physically delete records if doing so could later break:

Customer
  ↓
Subscription
  ↓
Usage
  ↓
Invoice

relationships.

Document the decision.

---

# 10. Authorization

Follow the authorization architecture established in Phase 1/2.

Suggested permissions:

customers.view
customers.create
customers.update
customers.archive

Owner:

- view
- create
- update
- archive

Member:

- view

Do not give Member mutation permissions unless there is a documented reason.

Backend authorization is mandatory.

Frontend permission checks are only for UX.

---

# 11. Tenant Isolation

Use the existing:

MerchantContext

and existing tenant-resolution middleware.

Do NOT create another tenant system.

Verify:

- Merchant A can see its own customers.
- Merchant A cannot list Merchant B customers.
- Merchant A cannot view Merchant B customer.
- Merchant A cannot update Merchant B customer.
- Merchant A cannot archive Merchant B customer.
- merchant_id cannot be overridden by request input.

Add explicit security tests for all of these.

---

# 12. Customer Model

Implement appropriate relationships.

Merchant:

hasMany(Customer)

Customer:

belongsTo(Merchant)

Prepare the model cleanly for the next phase where:

Customer
  ↓
Subscriptions

will be introduced.

Do not create subscription relationships yet unless they are necessary and empty relationships are consistent with the project's architecture.

---

# 13. Factory

Create:

CustomerFactory

Support useful states such as:

- active
- inactive

Generate realistic test data.

Ensure factory-created customers belong to a merchant.

---

# 14. API Resource

Create:

CustomerResource

Expose:

- public_id
- name
- email
- external_reference
- status
- timestamps

Do not expose:

- internal database id
- merchant_id unless the existing API convention explicitly requires it

Keep API responses consistent with PlanResource.

---

# 15. Frontend

Create a simple Customer management UI.

Do not over-invest in visual polish.

Create:

## Customers List

Show:

- Name
- Email
- External reference
- Status
- Created date
- Actions

Support:

- pagination
- search
- status filter

## Create Customer

Fields:

- Name
- Email
- External reference

## Edit Customer

Allow editing supported fields.

## Deactivate

Provide confirmation before deactivation.

Use:

- Vue 3
- TypeScript
- Vite
- existing API client
- existing authentication
- existing layout
- existing UI conventions

Do not introduce a new framework.

---

# 16. Frontend Authorization

Use the same authorization approach as Phase 2.

Examples:

- Hide Create Customer for users without customers.create.
- Hide Edit for users without customers.update.
- Hide Archive for users without customers.archive.

Never rely on frontend checks for security.

---

# 17. Search & Indexing

Think about the assignment's high-volume architecture even though usage events are not being implemented yet.

For Customers, add only indexes justified by actual queries.

Consider:

- merchant_id
- merchant_id + status
- merchant_id + email
- merchant_id + external_reference

If external_reference is unique within a merchant, enforce:

UNIQUE(merchant_id, external_reference)

Do not add unnecessary indexes.

Document important indexing decisions.

---

# 18. Tests

Every important behavior must be tested.

## Model Tests

Test:

- Merchant relationship
- Customer relationship
- status enum
- casts
- factory states

## API Tests

### Create

Test:

- valid customer
- missing name
- invalid email
- invalid field lengths
- duplicate external reference if applicable
- unauthorized creation

### List

Test:

- returns current merchant customers
- pagination
- search
- status filtering
- customers from another merchant are excluded

### Show

Test:

- own customer accessible
- foreign customer returns expected response

### Update

Test:

- valid update
- validation failures
- merchant ownership cannot change
- foreign customer cannot be updated

### Archive

Test:

- customer becomes inactive/archived
- foreign customer cannot be archived

## Security

Explicitly test:

- merchant_id injection
- mass assignment
- cross-tenant access
- invalid authentication
- revoked token
- role authorization

---

# 19. Performance

Make sure customer list/search queries are database-driven.

Do NOT:

- retrieve all customers and filter in PHP
- retrieve all customers and paginate in PHP
- perform unnecessary N+1 queries

Use pagination.

Verify generated queries where useful.

---

# 20. Documentation

Update README / docs with:

- Customer schema
- Customer lifecycle
- Customer API
- Authorization
- Tenant isolation
- Indexing decisions
- Assumptions

Document that customers do not have plans/subscriptions in this phase.

Subscriptions will be introduced in Phase 4.

---

# 21. Quality Checks

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

Verify existing Phase 1 and Phase 2 tests still pass.

Do not break existing authentication or plan functionality.

---

# 22. Assignment Alignment Review

Before creating the PR, verify:

- Merchant has Customers.
- Customer schema is normalized.
- Customer data is tenant isolated.
- Customer CRUD works.
- Customer API is paginated.
- Customer search works.
- Customer authorization works.
- Customer lifecycle is safe for future billing.
- No subscription functionality was implemented.
- No usage functionality was implemented.
- No billing functionality was implemented.

---

# 23. Git / PR

Create:

phase-03-customers

Commit:

feat: implement customer management

Push the branch.

Create a PR.

Do NOT merge.

Do NOT start Phase 4.

Final response must include:

1. Files created/updated
2. Database schema
3. API endpoints
4. Authorization
5. Tenant isolation
6. Search/pagination
7. Indexing
8. Frontend implementation
9. Tests added
10. Full test results
11. Static analysis results
12. Frontend build results
13. Commit hash
14. PR URL
15. Assumptions/trade-offs

STOP after creating the PR.
