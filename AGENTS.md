# Working Agreement

This file is the contract for anyone — human or automated — changing this
repository.

## Ground Rules

1. **One phase at a time.** Phase 0 is the foundation. Domain work starts only
   after the phase it belongs to is unblocked, and each phase lands behind its
   own pull request.
2. **Never commit straight to `main`.** Branch as `phase-NN-slug`, push with
   `--set-upstream`, open a pull request, wait for CI.
3. **A test is part of the change, not a follow-up.** If behaviour changes, the
   test that proves it is in the same commit.
4. **No secrets, ever.** Credentials live in the environment; `.env.example`
   contains placeholders only.
5. **Money is never a float.** Amounts cross every signature as an integer
   number of minor units together with an explicit currency.

## Where Code Goes

| I am adding | It belongs in |
| --- | --- |
| A business rule | `app/Actions/<Domain>/`, one public entry point per rule |
| A value object or enum | `app/DTOs/<Domain>/` or `app/Enums/`, always `final readonly` |
| An HTTP endpoint | `app/Http/Controllers/Api/` — validate, delegate, return a resource |
| A JSON shape | `app/Http/Resources/` — no queries, no rules |
| Persistence | `app/Models/` plus a migration |
| External I/O | A queued job in `app/Jobs/`, never inside a request |
| A reusable Vue composable | `resources/js/composables/` with a spec next to it |
| A reusable UI piece | `resources/js/components/` with a spec next to it |
| A decision document | `docs/` and a line in the README |

## Style

* Comments explain **why**, never **what**.
* Money is an integer of minor units with explicit currency.
* Anything queued or scheduled must be **idempotent**.
* Every tenant-scoped query must trace to the authenticated merchant.
* Time is stored in UTC.
* Public API values are contract — changing one is a breaking change.

## Testing

* One behaviour per test, named after the behaviour.
* Prefer a failing assertion to a mock.
* Mocks are for boundaries that cannot be reached otherwise.
