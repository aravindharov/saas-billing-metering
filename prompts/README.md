# AI prompt evidence

This directory holds the **AI prompt log** for the take-home assignment: full prompt text (transcripts) per development phase.

Transcripts were exported from the Cursor agent session that built phases 0–9. Each phase folder includes:

| File | Purpose |
|------|---------|
| `PROMPT.md` | Primary phase kickoff prompt |
| `FOLLOWUPS.md` | Additional user prompts in that phase (CI fixes, UI tweaks, etc.), if any |
| `NOTES.md` | Branch name and delivery notes |

## Structure

```
prompts/
├── README.md
├── meta/
│   └── FOLLOWUPS.md          # Prompt-log maintenance (README verification, etc.)
├── phase-00-foundation/
├── phase-01-auth-tenancy/
├── phase-02-plans-pricing/
├── phase-03-customers/
├── phase-04-subscriptions/
├── phase-05-usage-ingestion/
├── phase-06-daily-aggregation/
├── phase-07-billing-invoices/
├── phase-08-dashboard-analytics/
├── phase-09-performance-security/
└── phase-10-final-review/
```

## Phase ↔ README mapping

| Folder | README phase |
|--------|----------------|
| `phase-00-foundation` | Phase 0 — Foundation |
| `phase-01-auth-tenancy` | Phase 1 — Authentication & Tenancy |
| `phase-02-plans-pricing` | Phase 2 — Plans & Pricing |
| `phase-03-customers` | Phase 3 — Customers |
| `phase-04-subscriptions` | Phase 4 — Subscriptions & Plan Changes |
| `phase-05-usage-ingestion` | Phase 5 — Usage Event Ingestion |
| `phase-06-daily-aggregation` | Phase 6 — Daily Usage Aggregation |
| `phase-07-billing-invoices` | Phase 7 — Billing & Invoices |
| `phase-08-dashboard-analytics` | Phase 8 — Merchant Dashboard & Analytics |
| `phase-09-performance-security` | Phase 9 — Performance & Security Hardening |
| `phase-10-final-review` | Phase 10 — Final assignment review |
