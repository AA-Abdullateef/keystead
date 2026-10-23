# Audit & change log (what was wrong, what changed)

Origin: a Laravel 12 prototype with SB Admin 2 assets, float money maths and no API structure. Rebuilt as Keystead.

## Bugs fixed (original → now)
**Security / data exposure**
* `GET earnings` returned *every* user's earnings → scoped to the caller (test).
* `role`/`phone` mass-assignable → anyone could register as admin → fillable restricted, role assigned server-side.
* Two competing role systems (`users.role` + pivot) → pivot only.
* Login ignored suspended status; no throttling; `forgot-password` enumerated accounts → fixed.
* A committed `.env` with credentials → removed; `.env.example` only. **Rotate anything that was in it.**
* Loan ownership was not checked on every action → policies, 404 for non-owners.

**Money correctness**
* Float arithmetic → BCMath decimal strings.
* No row locks (lost updates, double spends) → lock-first transactions; MariaDB-verified.
* Wallet changes without ledger rows (withdrawal rejection, admin override, loan cancel refund) → every change is a ledger row via `WalletService`.
* Distribution could over/under-pay by rounding and could run twice → floor rounding, company residual, unique constraint, reconciliation.
* Ownership stored with 2 dp → 4 dp.
* Fully funded projects became `closed`, blocking income entry → `funded` state.
* Deleting an investment refunded nothing → reversal with refund, blocked after distribution.
* Loan payment API called the service with swapped arguments (could never work); service read `request()` internally → rewritten.
* Final installment could push balance negative; duplicate monthly payments possible; "overdue" flag rolled back by its own transaction → fixed.
* Overdue refresh ran on admin GET pages → scheduled command.
* Initial payment rule contradicted itself (`>=` then `===`) → strict equality kept, documented.

**Structure**
* Migrations had no foreign keys/uniques and wrong ordering → rewritten.
* Typos (`Illuuminate\Support\Str`), non-existent columns/models (`LoanApplication`, `property_type`, `updated_by`), broken relations, double-prefixed API routes, missing pagination view, broken admin `store` routes → fixed/removed.

## Added
Versioned API with Form Requests, API Resources, single envelope, policies, middleware, rate limits, JSON error rendering;
service layer; enums; `finance:reconcile`; `loans:refresh-overdue`; admin "fund wallet" (ledger deposit); demo seeder;
89 tests; MariaDB concurrency harness; docs and Postman collection.

## Rebrand & UI
REALLY → **Keystead**. `public/admin-assets` (23 MB of SB Admin 2, Bootstrap, jQuery, DataTables, FontAwesome) deleted and replaced by
~15 KB of CSS and ~2 KB of JS in `public/assets`; new layouts (`layouts/app`, `layouts/guest`), Blade components, 20 restyled views.

## Deliberate behaviour decisions (financial rules were not changed silently)
See [financial-rules.md](financial-rules.md): shares measured against the target; exact initial payment; flat interest; one installment per month.
Added (not in the original): reversal of investments before distribution, one pending withdrawal per user.

## Phase 2 — notifications, verification, integrity, audit
* **Rebrand verified**: no `REALLY` strings remain outside this history note; config defaults (mail from, Slack username, cache/session/db prefixes) moved to Keystead; stale `bootstrap/cache` removed.
* **Notifications**: `NotificationService` + 12 financial and 5 security notifications, all sent synchronously (no queue); branded mail theme; deferred with `afterCommit`; failures reported, never fatal.
* **Verification & security**: `registration_token`, `password_reset_token` (+ expiry, sent-at, `password_changed_at`); API and console flows; `verified.email` gate on money routes; change-password endpoint; account-status e-mail.
* **Integrity**: ledger immutability and delete protection, gapless `user_seq`, `dedupe_key` on one-time credits, non-negative balance CHECK/trigger, `Idempotency-Key` middleware, extended `finance:reconcile` (sequence, project totals, loan balances).
* **Audit**: `audit_logs` with append-only triggers, `Audited` model trait, explicit domain events, admin viewer.
* **Bug found by the new tests**: failed verification/reset attempts were written to the audit log inside the transaction that then rolled back, so they were lost; they are now logged after the rollback.
* Tests 89 → 113.
