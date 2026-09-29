# Assumptions & Trade-offs

This document records architectural decisions and assumptions that a future
reader might question.

## Phase 0 — Foundation

| # | Decision | Reasoning |
|---|----------|-----------|
| 1 | **MySQL 8.4** as the primary datastore | The assignment specifies MySQL. Version 8.4 is the current LTS. |
| 2 | **Redis 7.4** for cache, queues, and rate-limiting | Redis is the standard Laravel companion for these concerns. A single Redis instance is sufficient for local dev; production would use separate instances or clusters. |
| 3 | **Sanctum** for API authentication | Token-based auth fits the multi-tenant SaaS model. Sanctum is first-party and well-integrated with Laravel. |
| 4 | **Single Docker image** for all PHP responsibilities | Keeps the dev environment simple. Production would use separate images for web, queue, and scheduler. |
| 5 | **SQLite not used** for testing | MySQL-specific features (strict mode, transactions, charset) are part of the contract. Tests run against MySQL to catch incompatibilities early. |
| 6 | **Money as integer minor units** | Per the assignment. All monetary values will be stored and transmitted as integers representing the smallest currency unit (e.g., cents). |
| 7 | **UTC everywhere** | All timestamps are stored in UTC. Display formatting happens at the frontend edge. |
| 8 | **Vue 3 SPA** with server-side API | The frontend is a Vue 3 SPA served by a catch-all blade template. API-first design keeps the frontend decoupled from the backend. |
