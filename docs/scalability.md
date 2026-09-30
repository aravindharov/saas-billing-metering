# Scalability Decisions

> **Canonical reference:** see [architecture.md](./architecture.md) for the full
> 50L+ usage pipeline, index strategy, caching, and partitioning deferral.

This file remains as a short pointer for assignment reviewers.

| Concern | Approach |
|---------|----------|
| Usage at scale | Append-only `usage_events`, indexed aggregation, `daily_usage` read model |
| Queues | Redis workers; chunked rebuild and billing commands |
| Rate limiting | 500 req/min/merchant on `POST /usage` |
| Plan cache | Merchant-scoped keys, TTL + invalidation on write |
| Partitioning | Documented future strategy only — not implemented |
