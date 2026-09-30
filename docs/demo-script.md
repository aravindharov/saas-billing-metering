# Demo script (~5–10 minutes)

Use this outline for a **live demo** or a **screen recording**. For hosting and the public link evaluators will open, see **[demo-recording.md](demo-recording.md)**.

Target: evaluator with Docker. Base URL: **http://localhost:8000**.

## Before you start

```bash
cp .env.example .env
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose up -d queue
```

Login: merchant slug `acme`, `owner@acme.test` / `password`.

Seeded data: plans (Starter, Professional, Enterprise), customers, John Smith on Starter, usage events + **daily usage** (aggregation run synchronously in seeder).

---

### 1. Architecture — 30 seconds

Walk through:

```
Merchant → Customers → Plans → Subscriptions (pricing snapshot)
  → Usage Events → Daily Usage → Billing / Invoices → Dashboard
```

Point to [`docs/architecture.md`](architecture.md) for 50L+ detail.

---

### 2. Authentication — 30 seconds

- Open `/login`, sign in as Acme owner.
- Show merchant name and role in the shell (tenant context).

Optional API:

```bash
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"merchant":"acme","email":"owner@acme.test","password":"password"}'
```

---

### 3. Plan — 30 seconds

- **Plans** nav: show Starter — base price, monthly cycle, included units, overage rate (integer paise in API; UI formatted).

---

### 4. Customer + subscription — 1 minute

- **Customers**: John Smith (active).
- **Subscriptions**: John on Starter; open detail — pricing snapshot fields, billing period dates.

Optional: create Jane’s subscription via UI (**Create Subscription**).

---

### 5. Usage ingestion — 1 minute

- **Record Usage** page: POST-style form or use API:

```bash
TOKEN="<from login>"
curl -s -X POST http://localhost:8000/api/v1/usage \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "event_id": "demo-evt-001",
    "customer_id": "<customer public id>",
    "subscription_id": "<subscription public id>",
    "quantity": 100,
    "occurred_at": "2026-09-29T12:00:00Z"
  }'
```

- Repeat same `event_id` → idempotent 200, same record.
- Mention rate limit: 500 events/minute per merchant.

---

### 6. Daily usage — 30 seconds

- **Daily Usage** page: aggregated totals for John (seed + any new events).
- Explain: raw `usage_events` → queue job → `daily_usage` read model.

If queue is `sync`, aggregation is immediate; with Redis queue, ensure `queue` service is running.

---

### 7. Plan change — 1 minute

- Subscription detail → **Change plan** (e.g. Starter → Professional).
- Show plan change history with from/to pricing.
- Explain billing will split the period into segments using history.

---

### 8. Invoice — 1–2 minutes

Invoices generate for **ended** billing periods.

Options:

1. **UI (owner):** Subscription detail → **Generate invoice** (when period ended).
2. **Command:**

```bash
docker compose exec app php artisan billing:generate-invoices
# optional: --before=2026-10-01T00:00:00Z
```

3. **API:**

```bash
curl -s -X POST http://localhost:8000/api/v1/subscriptions/{id}/generate-invoice \
  -H "Authorization: Bearer $TOKEN"
```

- Open **Invoices** → detail: base line, overage line, total (integer paise).
- Re-run generate → duplicate protection (same period not double-billed).

*Tip for live demo:* advance `--before` or temporarily adjust subscription period in tinker only if needed; prefer explaining period-end rule.

---

### 9. Dashboard — 1 minute

- **Home**: top customers MTD, projected overage, customers with >50% MoM drop.
- API: `GET /api/v1/merchants/{merchant_public_id}/dashboard` (merchant id must match tenant).

---

### 10. Scalability — 1 minute

- Append-only usage writes, composite indexes, async jobs, chunked `usage:aggregate` rebuild.
- Dashboard/billing read `daily_usage`, not full event scans.
- Partitioning: documented future step in architecture doc.

---

## Quick verification commands

```bash
make verify          # Pint, PHPStan, vue-tsc, ESLint, Prettier, PHPUnit, Vitest, Vite build
docker compose exec app php artisan usage:aggregate --from=2026-09-01 --to=2026-09-30
```
