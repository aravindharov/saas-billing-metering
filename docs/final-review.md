# Final review (Phase 10)

Branch: `phase-10-final-review`  
Builds on: Phase 9 (`42bca06`), PR [#10](https://github.com/aravindharov/saas-billing-metering/pull/10) (open).

## Strengths

- End-to-end multi-tenant billing domain: plans, customers, subscriptions with snapshots and plan-change history, usage ingestion, daily aggregation, invoice generation, merchant dashboard.
- Money handled as integer minor units throughout; billing uses segment-based proration and overage from `daily_usage`.
- Strong test coverage: billing, idempotency, tenant isolation, dashboard, frontend pages; CI mirrors local `make verify`.
- Clear separation: Actions/Services for rules, thin controllers, queued jobs for aggregation and invoicing.
- Scalability narrative backed by indexes, chunking, rebuild command, and [`docs/architecture.md`](architecture.md).

## Assignment coverage

Full requirement matrix: [`docs/assignment-checklist.md`](assignment-checklist.md).

**Category A gaps fixed in Phase 10:** none (audit found no missing required features).

**Documentation added:** assignment checklist, demo script, this final review; README updated for submission (overview, verification counts, AI-assisted development).

## Known trade-offs

- **Single currency:** INR/paise implied; no multi-currency FX.
- **UTC billing periods** and usage dates; display localization at UI only.
- **Invoices issued immediately** on generation; no draft/edit workflow beyond status field.
- **Dashboard uncached** — acceptable at assignment scale; optional cache listed out of scope.
- **MySQL partitioning** documented but not implemented (unique key + partition key tension explained in architecture).
- **No payments, taxes, refunds, dunning** — explicitly out of scope.

## Known limitations

- **Demo recording:** Assignment requires a **public** walkthrough link in the README. Until that URL is set, evaluators should use [docs/demo-script.md](demo-script.md) locally or request the link from the candidate.
- **AI prompt log:** phase prompts are stored as **markdown transcripts** under [`prompts/`](../prompts/), not PNG screenshots. If the PDF requires images, add screenshots locally; none are fabricated in this repository.
- **Seeder** creates usage and daily aggregates but not invoices (billing period still active); demo uses generate-invoice flow documented in [`docs/demo-script.md`](demo-script.md).
- **Subscription `expired` status** exists in enum; automatic expiry job not implemented (cancel path is primary).

## Evaluation notes

1. **Tenant security:** Always trace data access to `MerchantContext` / authenticated user’s `merchant_id`; cross-tenant IDs return 404 on route models.
2. **Historical pricing:** Never bill from current `plans` row for an existing subscription; use subscription fields + `subscription_plan_changes`.
3. **Usage path:** Ingest is fast; aggregation is async (or sync if `QUEUE_CONNECTION=sync` for local dev).
4. **Idempotency:** Usage duplicates by `(merchant_id, event_id)`; invoices by subscription + billing period bounds.
5. **Quality bar:** Run `make verify` before judging; numbers recorded in README **Verification** section match that command output.

## Related PRs (open)

| PR | Phase |
|----|--------|
| [#8](https://github.com/aravindharov/saas-billing-metering/pull/8) | Billing & invoices |
| [#9](https://github.com/aravindharov/saas-billing-metering/pull/9) | Dashboard |
| [#10](https://github.com/aravindharov/saas-billing-metering/pull/10) | Performance & security |
| Phase 10 | Final review (this branch) |

Do not merge until review is complete.
