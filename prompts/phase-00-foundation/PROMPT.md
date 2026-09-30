<!-- Transcript line 1; extracted for assignment prompt log -->

# Phase 0 — user prompt

You are the lead software architect and senior Laravel engineer.

IMPORTANT: We are restarting this repository COMPLETELY FROM SCRATCH.

This repository currently contains an older implementation of a different/broader project. I do NOT want to continue, refactor, or partially reuse that implementation.

The only source of truth for the new application requirements is the attached Mallow Technologies "Senior Laravel Developer Mini Task — Take-Home Assignment" PDF.

============================================================
OBJECTIVE
============================================================

Completely reset the current repository and create a clean implementation for:

Subscription Billing & Usage-Metering System

This is a take-home assignment.

The assignment requires a small multi-tenant SaaS-style backend that:

- supports merchants/tenants
- allows merchants to define plans
- allows customers to subscribe to plans
- records customer usage events
- handles high-volume usage ingestion
- guarantees idempotency
- aggregates usage using queued/chunked processing
- generates invoices
- calculates overage
- handles subscription plan changes and proration
- provides a merchant dashboard
- uses caching
- rate-limits usage ingestion
- has appropriate tests
- documents scalability decisions
- documents assumptions and trade-offs

The attached assignment PDF is the PRIMARY source of truth.

Do not invent additional business requirements.

============================================================
PART 1 — COMPLETELY REMOVE THE OLD PROJECT
============================================================

The current repository is an OLD implementation.

Remove the old application implementation entirely.

Do NOT:

- continue the old architecture
- preserve old business modules
- migrate old tables
- reuse old controllers
- reuse old services
- reuse old Actions
- reuse old DTOs
- reuse old models
- reuse old migrations
- reuse old seeders
- reuse old frontend pages
- reuse old frontend components
- reuse old API endpoints
- reuse old subscription implementation
- reuse old billing implementation
- reuse old catalog implementation
- reuse old token/client implementation
- reuse old business logic

The old project should NOT influence the new domain design.

If something is useful as a generic configuration or dependency, evaluate it carefully. Do not blindly preserve it.

The final repository must represent a clean implementation of the Mallow assignment.

============================================================
PART 2 — COMPLETELY FRESH GIT HISTORY
============================================================

I explicitly want a COMPLETELY FRESH Git repository/history.

Do NOT preserve the old Git history.

Before creating the new implementation:

1. Remove the existing .git directory.
2. Initialize a completely new Git repository.
3. Configure Git with:

   git config user.name "Aravindhan"
   git config user.email "balaaravindh96@gmail.com"

4. Create the new repository history from scratch.

IMPORTANT:

Do not push anything to the old remote automatically.

First inspect the existing remote configuration and report it.

I will provide/configure the new remote separately if necessary.

Do NOT force-push to any remote unless I explicitly instruct you to do so.

============================================================
PART 3 — CLEAN PROJECT INITIALIZATION
============================================================

Create a fresh application using:

Backend:
- Laravel
- PHP 8.3+
- MySQL 8+
- Redis

Frontend:
- Vue 3
- TypeScript
- Vite

Testing:
- Pest/PHPUnit
- Vitest

Infrastructure:
- Docker / Docker Compose
- GitHub Actions

Use stable, current versions compatible with the assignment environment.

Before choosing versions, inspect the installed environment and existing project tooling.

Do not introduce unnecessary dependencies.

============================================================
PART 4 — NEW PROJECT DOMAIN
============================================================

The core business domain is:

Merchant
    |
    ├── Users
    |
    ├── Plans
    |
    └── Customers
           |
           └── Subscription
                  |
                  ├── Plan
                  ├── Plan Changes
                  |
                  └── Usage Events
                           |
                           ▼
                     Daily Usage
                           |
                    ┌──────┴──────┐
                    ▼             ▼
               Dashboard       Billing
                                  |
                                  ▼
                               Invoice

Do NOT implement these domain modules during this initial reset/foundation step.

This prompt is only establishing the clean project foundation.

============================================================
PART 5 — DO NOT IMPLEMENT BUSINESS FEATURES YET
============================================================

DO NOT implement:

- Plans
- Customers
- Subscriptions
- Usage Events
- Usage Aggregation
- Billing
- Invoices
- Proration
- Dashboard
- Churn analysis
- Payment processing
- Refunds
- Credit notes
- Dunning
- Tax engine
- Tiered pricing
- Usage rollover
- Complex entitlement systems

These will be implemented in later phases.

For now, establish only the clean foundation.

============================================================
PART 6 — ARCHITECTURE PRINCIPLES
============================================================

Establish project-wide conventions.

Controllers must be thin.

Do not put business logic in controllers.

Use appropriate separation such as:

- Controllers
- Form Requests
- Actions
- Services
- Models
- API Resources
- DTOs where genuinely useful
- Query objects where genuinely useful

Do not create abstractions just for the sake of abstraction.

Prefer simple Laravel conventions.

Database-related behavior should live in the appropriate model/query/domain layer.

Use transactions whenever multiple related database writes must remain atomic.

Never use floating point values for money.

Money must eventually use integer minor units.

All API validation must happen server-side.

Frontend validation is for UX only.

The frontend is never the authority for authorization or security.

Secrets must never be committed.

Configuration must use environment/configuration files.

============================================================
PART 7 — MULTI-TENANCY FOUNDATION
============================================================

The application is multi-tenant.

The primary tenant is:

Merchant

Eventually:

Merchant
├── Users
├── Plans
├── Customers
├── Subscriptions
├── Usage
└── Invoices

Establish the architectural conventions required for tenant isolation.

However:

DO NOT implement all those business tables now.

Only establish the foundation necessary for future phases.

============================================================
PART 8 — API FOUNDATION
============================================================

Establish a clean REST API structure.

Use a consistent API response format.

Establish:

- API versioning convention
- authentication convention
- validation convention
- error response convention
- HTTP status code convention

Do not implement the future business endpoints yet.

============================================================
PART 9 — FRONTEND FOUNDATION
============================================================

Create a clean Vue 3 + TypeScript application.

Establish:

- routing
- application layout
- reusable UI structure
- API client
- environment configuration
- error handling foundation
- loading state conventions
- form conventions
- TypeScript configuration

Do NOT create the complete business dashboard yet.

Only create the foundation required for future phases.

============================================================
PART 10 — DATABASE FOUNDATION
============================================================

Configure MySQL.

Establish:

- database connection
- migrations infrastructure
- factories
- seeders structure
- testing database configuration

Do not create business tables yet unless absolutely required by the framework foundation.

============================================================
PART 11 — REDIS FOUNDATION
============================================================

Configure Redis for future:

- cache
- queues
- rate limiting

Verify that the application can communicate with Redis.

Do not implement business caching yet.

============================================================
PART 12 — QUEUE FOUNDATION
============================================================

Configure Laravel queues using Redis.

Verify that a basic test/example job can be dispatched and processed.

Do not implement usage aggregation yet.

============================================================
PART 13 — DOCKER
============================================================

Create a clean Docker development environment.

It should support:

- Laravel application
- MySQL
- Redis
- queue worker
- scheduler where appropriate
- Vue/Vite development server

Ensure services communicate correctly.

Provide clear commands for:

Start:
docker compose up -d

Stop:
docker compose down

Logs:
docker compose logs -f

Tests:
appropriate backend/frontend test commands

Queue:
appropriate queue worker command

Do not assume commands without verifying them.

============================================================
PART 14 — TESTING FOUNDATION
============================================================

Set up:

Backend:
- Pest/PHPUnit
- test database
- coverage tooling

Frontend:
- Vitest
- Vue test utilities where appropriate
- coverage tooling

Create only foundation/health tests.

Do not create fake business tests.

The purpose is to prove the testing infrastructure works.

============================================================
PART 15 — CODE QUALITY
============================================================

Set up appropriate:

- PHP formatting
- PHP static analysis
- ESLint
- TypeScript checking
- Vue linting/formatting

Use practical tools.

Do not add unnecessary tooling.

All configured quality checks must be executable locally.

============================================================
PART 16 — GITHUB ACTIONS
============================================================

Create GitHub Actions CI.

The pipeline should eventually support:

Backend:
- dependency installation
- formatting/style checks
- static analysis
- tests
- coverage

Frontend:
- dependency installation
- lint
- TypeScript check
- tests
- coverage
- production build

For this initial phase, execute only checks that are applicable to the current codebase.

CI must fail when an applicable check fails.

Do not create fake tests just to satisfy coverage.

============================================================
PART 17 — README
============================================================

Create a fresh README.md.

For now document only:

1. Project overview
2. Technology stack
3. Local setup
4. Docker setup
5. Environment configuration
6. Backend commands
7. Frontend commands
8. Testing commands
9. Queue commands
10. CI overview
11. Architecture principles
12. Current implementation status

Clearly distinguish:

IMPLEMENTED

from:

PLANNED

Do not claim future functionality is implemented.

============================================================
PART 18 — PROMPT LOG
============================================================

Create:

prompts/

Use this directory for the AI prompt evidence required by the assignment.

Store phase prompt transcripts as markdown under `prompts/`.

============================================================
PART 19 — GIT WORKFLOW AFTER RESET
============================================================

After the repository has been completely reset:

Create:

main

Then create:

phase-00-foundation

ALL implementation work for this phase must happen on:

phase-00-foundation

Do NOT work directly on main.

When Phase 0 is complete:

1. Run all applicable tests.
2. Run formatting.
3. Run static analysis.
4. Run frontend lint/type checks.
5. Run frontend build.
6. Verify Docker.
7. Verify Redis.
8. Verify MySQL.
9. Verify queue.
10. Verify Git status.
11. Verify no secrets are tracked.
12. Review the diff.
13. Commit the work.
14. Push the phase branch ONLY if a valid new remote is configured.
15. Create a Pull Request targeting main.
16. Do NOT merge the PR.
17. STOP.

Do not start Phase 1 automatically.

============================================================
PART 20 — IMPORTANT SCOPE CONTROL
============================================================

This is critical.

Do NOT start implementing the complete application just because you understand the future requirements.

This execution is phase-driven.

Current phase:

PHASE 0 — FOUNDATION ONLY

Future phases will be explicitly provided later.

Do not implement future requirements now.

============================================================
PART 21 — BEFORE MAKING CHANGES
============================================================

First inspect the current repository.

Then report:

1. Current directory structure.
2. Current Git status.
3. Current Git remote.
4. Current Laravel/PHP/Node/npm versions.
5. Current Docker setup.
6. Current database configuration.
7. Current Redis configuration.

Then explain briefly what will be removed.

After that, perform the complete reset.

Do not ask me to manually remove the old project unless absolutely necessary.

============================================================
PART 22 — FINAL VERIFICATION
============================================================

Before declaring Phase 0 complete, verify:

- Old application code is removed.
- Old business migrations are removed.
- Old business routes are removed.
- Old frontend business pages are removed.
- Old business components are removed.
- Old business tests are removed.
- Old configuration that belongs only to the previous project is removed.
- Old Git history is removed.
- New Git repository exists.
- No secrets are committed.
- Laravel starts successfully.
- Vue starts successfully.
- MySQL works.
- Redis works.
- Queue works.
- Tests pass.
- Frontend build passes.
- CI configuration is valid.
- README is updated.

============================================================
FINAL OUTPUT
============================================================

At the end, report:

1. What was removed.
2. What was created.
3. Final directory structure.
4. Technology versions.
5. Docker services.
6. Git repository status.
7. Branch name.
8. Commit hash.
9. Remote status.
10. Tests/checks executed and their results.
11. Any assumptions.
12. Any problems requiring my decision.

Then STOP.

DO NOT implement Phase 1.

DO NOT merge anything.

DO NOT push to an old remote.

DO NOT force-push.

DO NOT continue automatically.

The repository must now be a genuinely fresh implementation.
