<!-- Transcript line 184; extracted for assignment prompt log -->

# Phase 1 — user prompt

You are the lead software architect and senior Laravel engineer for this project.

The repository has already been completely reset and Phase 0 — Foundation is COMPLETE.

Now implement:

============================================================
PHASE 1 — AUTHENTICATION & MERCHANT MULTI-TENANCY
============================================================

Branch:

phase-01-auth-tenancy

IMPORTANT:

This phase is ONLY about:

- Merchants
- Users
- Authentication
- Authorization foundation
- Tenant resolution
- Tenant isolation foundation
- API authentication
- Frontend authentication foundation

DO NOT implement any business modules from later phases.

The assignment PDF is the PRIMARY source of truth.

============================================================
STRICT SCOPE
============================================================

Implement ONLY:

1. Merchant/tenant
2. Merchant users
3. User roles
4. Authentication
5. Sanctum/token authentication
6. Tenant context
7. Tenant isolation foundation
8. API authorization foundation
9. Frontend login/authentication
10. Authentication tests
11. Tenant-isolation tests
12. Documentation for Phase 1

DO NOT IMPLEMENT:

- Plans
- Customers
- Subscriptions
- Usage events
- Usage aggregation
- Billing
- Invoices
- Proration
- Dashboard
- Churn analysis
- Payment processing
- Tax
- Credit notes
- Refunds
- Dunning
- Metering
- Pricing
- API usage ingestion

Those belong to later phases.

============================================================
1. DOMAIN MODEL
============================================================

Create the following core entities:

Merchant
User

Relationship:

Merchant
  └── Users

A merchant is the tenant boundary.

Every future tenant-owned business entity will belong to a merchant.

Do NOT create future business tables just because they will eventually need merchant_id.

Only create the tables required for this phase.

============================================================
2. MERCHANT TABLE
============================================================

Create a normalized merchants table.

Recommended fields:

- id
- public_id if the project convention uses public identifiers
- name
- slug
- status
- created_at
- updated_at

Use appropriate database constraints.

The slug should be unique.

Do not expose sequential internal database IDs unnecessarily through the public API.

Use the project's established ID/public-ID convention from Phase 0.

============================================================
3. USERS TABLE
============================================================

Create the users table with the fields required for authentication and merchant membership.

At minimum:

- id
- merchant_id
- name
- email
- password
- role
- created_at
- updated_at

Relationship:

Merchant
  hasMany Users

User
  belongsTo Merchant

The same email may belong to different merchants if the architecture allows tenant-local identity.

Document the decision.

Do not assume global email uniqueness unless the assignment or existing Phase 0 architecture requires it.

============================================================
4. ROLES
============================================================

For this project use two roles initially:

owner
member

Keep the role model simple.

Do NOT introduce a full permissions/RBAC package unless there is a clear requirement.

Role responsibilities:

OWNER:
- full merchant access
- manage merchant users
- manage merchant configuration
- access all current/future merchant resources

MEMBER:
- authenticated merchant user
- permissions can be expanded in later phases
- must still remain inside the merchant tenant

Do not implement detailed module permissions yet.

============================================================
5. AUTHENTICATION
============================================================

Use Laravel Sanctum for API authentication if it is compatible with the Phase 0 setup.

Implement:

POST /api/v1/auth/login

POST /api/v1/auth/logout

GET /api/v1/auth/me

The login endpoint should authenticate:

- merchant identification
- email
- password

Use the project's chosen authentication contract consistently.

Do not leak whether a specific email exists.

Invalid credentials should return an appropriate authentication response.

Successful authentication should issue a Sanctum token.

Do not store plaintext passwords.

Use Laravel's password hashing.

============================================================
6. TOKEN ABILITIES
============================================================

Establish a minimal token ability strategy.

Do not over-engineer this.

At this phase, authentication only needs the ability required to access the authenticated application API.

Future phases may introduce more specific abilities.

Document how token abilities will evolve.

Do not create a complex token-management UI yet.

============================================================
7. TENANT CONTEXT
============================================================

Create a clean tenant-resolution mechanism.

After authentication:

Authenticated User
        ↓
User.merchant_id
        ↓
Current Merchant / Tenant Context

Create a reusable mechanism such as:

MerchantContext

or an equivalent simple service.

The important requirement is:

Controllers and services should not repeatedly implement:

$user->merchant_id

in arbitrary places.

There should be one clear source for the current tenant context.

The tenant context must come from the authenticated user/token.

DO NOT trust a merchant_id supplied by the client body as the authority.

============================================================
8. TENANT ISOLATION FOUNDATION
============================================================

Establish the foundation for strict tenant isolation.

Future tenant-owned models will contain:

merchant_id

and queries will always be scoped to the authenticated merchant.

For this phase, demonstrate the mechanism with the Merchant/User relationship itself.

The architecture must make it difficult to accidentally query another merchant's data.

Do not build a fake abstraction that has no current purpose.

Keep the implementation simple and extensible.

============================================================
9. AUTHORIZATION
============================================================

Implement authorization for the current phase.

Rules:

OWNER:
- can access merchant management operations

MEMBER:
- can authenticate
- can access resources allowed to authenticated merchant users

A user must NEVER access another merchant's resources.

If a future resource belongs to another merchant, the API should not expose that resource.

Prefer consistent resource-not-found behavior where appropriate to avoid leaking tenant information.

Document the authorization strategy.

============================================================
10. AUTHENTICATION API
============================================================

Implement:

POST /api/v1/auth/login

Request should contain the appropriate merchant/login credentials.

Example conceptual request:

{
    "merchant": "acme",
    "email": "owner@acme.test",
    "password": "password"
}

Response should provide:

- authenticated user information
- merchant information
- token/authentication information as appropriate

Example conceptual response:

{
    "user": {
        "id": "...",
        "name": "Acme Owner",
        "email": "owner@acme.test",
        "role": "owner"
    },
    "merchant": {
        "id": "...",
        "name": "Acme",
        "slug": "acme"
    },
    "token": "..."
}

Do not blindly copy this JSON structure if Phase 0 established a different API response convention.

Follow the project's existing API response standard.

============================================================
11. LOGOUT
============================================================

Implement:

POST /api/v1/auth/logout

The current authentication token/session must be invalidated/revoked appropriately.

Do not revoke every token belonging to the user unless that is an intentional documented behavior.

A logout should normally invalidate the current authentication credential.

============================================================
12. CURRENT USER
============================================================

Implement:

GET /api/v1/auth/me

It must return:

- current authenticated user
- current merchant
- role
- relevant authentication context

It must never return another merchant's information.

============================================================
13. VALIDATION
============================================================

Use Laravel Form Requests where appropriate.

Validate:

- merchant identifier
- email
- password
- required fields
- role values where role input exists

Do not allow a client to arbitrarily assign themselves the owner role.

User role assignment must be server-controlled.

============================================================
14. FRONTEND AUTHENTICATION
============================================================

Implement the Vue authentication foundation.

Create:

- Login page
- Auth state/store
- API authentication client
- Protected route handling
- Logout
- Current-user loading
- Authentication error handling
- Loading state

After successful login:

Login
  ↓
Store authentication state
  ↓
Fetch /auth/me if required
  ↓
Load merchant/user context
  ↓
Redirect to authenticated application area

Unauthenticated users attempting to access protected routes must be redirected to login.

Do not build business dashboard modules yet.

A minimal authenticated shell/layout is enough.

============================================================
15. FRONTEND SECURITY
============================================================

Do not expose secrets in frontend source code.

Do not put private server credentials into Vite environment variables.

Follow the authentication mechanism established during Phase 0.

The frontend is NOT the authorization authority.

The API must independently authenticate and authorize every protected request.

============================================================
16. DATABASE FACTORIES
============================================================

Create factories for:

Merchant
User

Factories must support:

- owner
- member
- multiple merchants
- users belonging to different merchants

Do not create factories for future business entities.

============================================================
17. SEEDING
============================================================

Create minimal development seed data if appropriate.

Example:

Merchant:
Acme Corporation

Users:

owner@acme.test
member@acme.test

If another development seed structure already exists from Phase 0, integrate with it rather than duplicating it.

Do not create Plans, Customers, Subscriptions, Usage or Billing seed data.

Document development credentials clearly.

Never use real credentials.

============================================================
18. TESTING
============================================================

Tests are a major requirement.

Create meaningful tests for:

AUTHENTICATION

1. User can login with valid credentials.
2. Invalid password is rejected.
3. Unknown merchant is rejected.
4. Unknown user is rejected.
5. Login validation works.
6. Password is never returned in the API response.
7. Token is issued correctly.

LOGOUT

8. Authenticated user can logout.
9. Logged-out token can no longer access protected endpoints.

CURRENT USER

10. Authenticated user can retrieve /auth/me.
11. /auth/me returns the correct merchant.
12. /auth/me cannot expose another merchant.

TENANCY

13. User belongs to exactly the expected merchant.
14. Users from Merchant A cannot access Merchant B context.
15. Merchant A users cannot use manipulated merchant identifiers to switch tenants.
16. Tenant context always comes from authenticated identity.

AUTHORIZATION

17. Owner has owner-level access.
18. Member cannot perform owner-only operations.
19. Invalid/expired/revoked authentication is rejected.

FACTORIES/SEEDING

20. Merchant factory works.
21. Owner factory works.
22. Member factory works.
23. Multiple merchants can coexist correctly.

Do not create meaningless tests simply to increase coverage.

Every test should verify real behavior.

============================================================
19. SECURITY TESTS
============================================================

Explicitly test:

- missing authentication
- invalid token
- revoked token
- malformed token
- wrong merchant
- privilege escalation attempt
- mass assignment attempt
- role manipulation attempt

Example attack:

User from Merchant A sends:

{
    "merchant_id": "merchant-b"
}

The server must NOT switch tenant context.

The authenticated identity remains authoritative.

============================================================
20. API ERROR CONTRACT
============================================================

Follow the project's established error response convention.

Ensure authentication failures consistently return appropriate HTTP status codes.

Use:

401 for unauthenticated requests where appropriate.

403 for authenticated users lacking permission where appropriate.

404 where resource hiding is intentionally used to avoid tenant information leakage.

422 for validation errors.

Do not expose stack traces or sensitive internal details in production responses.

============================================================
21. RATE LIMITING
============================================================

Do not implement the complete usage rate limiter yet.

However, establish the authentication/rate-limit foundation if Phase 0 did not already do so.

Do not implement:

POST /usage

or usage-specific limits.

Those belong to a later phase.

============================================================
22. AUDIT / LOGGING
============================================================

Add appropriate structured logging for authentication events if the Phase 0 logging foundation supports it.

Examples:

- successful login
- failed login
- logout

Do not log:

- passwords
- raw authentication secrets
- bearer tokens

Do not create a full audit-log system unless required by the current architecture.

============================================================
23. API DOCUMENTATION
============================================================

Document the Phase 1 endpoints:

POST /api/v1/auth/login

POST /api/v1/auth/logout

GET /api/v1/auth/me

Document:

- request
- response
- authentication requirements
- validation errors
- authentication errors

Do not document future endpoints as implemented.

============================================================
24. FRONTEND UI
============================================================

Create a clean but simple authentication UI.

Login should contain:

Merchant / Organization
Email
Password
Login button

Display:

- validation errors
- invalid credentials
- loading state
- server errors

After login show a minimal authenticated shell:

Merchant name
Logged-in user
Role
Logout

Do NOT build:

- Plans UI
- Customers UI
- Subscriptions UI
- Usage UI
- Billing UI
- Dashboard analytics

Those belong to later phases.

============================================================
25. README UPDATE
============================================================

Update README with Phase 1 information:

- Authentication architecture
- Merchant/tenant model
- User roles
- Token authentication
- Tenant context
- Authorization rules
- API endpoints
- Frontend authentication
- Security decisions
- Testing strategy

Clearly state:

Phase 1 implemented.

Future business modules are planned but not implemented.

============================================================
26. ARCHITECTURE DECISION RECORD
============================================================

Create/update a suitable architecture documentation file if the project already uses one.

Document these decisions:

1. Merchant is the tenant boundary.
2. User belongs to one merchant.
3. Tenant context comes from authenticated identity.
4. Client-supplied merchant_id is never trusted for authorization.
5. Owner/member roles are used initially.
6. Sanctum handles API authentication.
7. API remains the ultimate authorization authority.
8. Frontend route protection is UX/security-in-depth, not authorization.
9. Passwords are hashed.
10. Tokens/secrets are never logged.

Keep the document concise.

============================================================
27. GIT WORKFLOW
============================================================

Work ONLY on:

phase-01-auth-tenancy

Before implementation:

1. Verify current branch.
2. Verify Phase 0 changes are available.
3. Verify working tree state.
4. Do not modify main directly.

After implementation:

1. Run backend tests.
2. Run frontend tests.
3. Run formatting.
4. Run static analysis.
5. Run TypeScript checks.
6. Run frontend build.
7. Run coverage checks applicable to this phase.
8. Verify Docker services.
9. Verify database migrations.
10. Verify Redis if authentication depends on it.
11. Review git diff.
12. Review for secrets.
13. Commit with a meaningful message.

Suggested commit:

feat: implement authentication and tenant foundation

Then:

- Push phase-01-auth-tenancy.
- Create a Pull Request targeting main.
- Do NOT merge the PR.
- Do NOT start Phase 2.
- STOP after PR creation.

============================================================
28. STRICT SCOPE CONTROL
============================================================

This is extremely important.

DO NOT implement future phases.

Do not create:

plans
customers
subscriptions
usage
daily_usage
invoices
billing calculations
dashboard analytics

Even if they are obvious next steps.

Phase 1 is ONLY:

Merchant
+
User
+
Authentication
+
Authorization
+
Tenant Context
+
Frontend Authentication

============================================================
29. FINAL VERIFICATION
============================================================

Before finishing, verify:

Backend:

- application starts
- migrations work
- authentication works
- logout works
- /auth/me works
- tenant context works
- authorization works
- tests pass

Frontend:

- application starts
- login page works
- authentication state works
- protected route works
- logout works
- errors render correctly
- production build works

Security:

- passwords are hashed
- secrets are not committed
- tokens are not logged
- merchant_id cannot override tenant context
- cross-tenant access is blocked
- role escalation is blocked

Git:

- branch is phase-01-auth-tenancy
- no changes are made directly on main
- clean commit created
- PR created
- PR is NOT merged

============================================================
FINAL RESPONSE FROM THE AGENT
============================================================

When complete, report:

1. Files/modules created.
2. Database schema created.
3. Authentication flow.
4. Tenant-resolution mechanism.
5. Authorization mechanism.
6. API endpoints.
7. Frontend authentication flow.
8. Tests added.
9. Test results.
10. Coverage results.
11. Static analysis/lint results.
12. Build result.
13. Security checks performed.
14. Git branch.
15. Commit hash.
16. Pull Request URL.
17. Any assumptions or issues.

Then STOP.

DO NOT IMPLEMENT PHASE 2.

DO NOT MERGE THE PR.

WAIT FOR MY NEXT INSTRUCTION.
