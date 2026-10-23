# Security

* **Mass assignment**: `User` accepts only name/email/password. Roles, status and wallet balance are never client-writable (registration cannot create admins).
* **AuthZ**: role middleware + policies; other users' loans/transactions return **404**, not 403 (no existence leak). The old `/earnings` endpoint returned every user's earnings — fixed and covered by a test.
* **Suspended users**: refused at login, by the `active` middleware on live tokens, and tokens are revoked on suspension.
* **Auth hygiene**: uniform login failure message; forgot-password never reveals whether an address exists; password reset revokes API tokens; Sanctum token TTL via `SANCTUM_TOKEN_TTL_MINUTES`; throttles on auth (`auth`), money (`money`) and general (`api`) routes.
* **Secrets**: `.env` is git-ignored and not shipped; `.env.example` has placeholders only. The original archive contained a committed `.env` — treat any credentials in it as compromised and rotate them.
* **Console**: CSRF on every form, session regeneration on login, staff-only login, admin-only guards on money-moving routes, confirmation on destructive actions.
* **Input**: every endpoint validates through a Form Request; money must match `decimal:0,2`.

## Concurrency
Row locking (`SELECT … FOR UPDATE`) on project/loan then wallet; unique indexes back every "only once" rule.
`scripts/concurrency-check.sh` runs real parallel PHP processes against MariaDB. Results from the run done while building this repo:

| Scenario | Result |
|---|---|
| 12 parallel investments of 400 vs a 1000 wallet | exactly 2 succeeded; wallet 200.00, project raised 800.00, portfolio 800.00 |
| 8 parallel withdrawal requests | exactly 1 pending |
| 8 parallel loan applications, same product | exactly 1 loan, wallet debited once |
| 6 approvals vs 6 rejections of one loan | exactly 1 decision; refund applied once |
| 8 parallel installment payments, same month | exactly 1 payment |
| 8 parallel distributions of one period | exactly 1 distribution, 1 wallet credit |

The run **found two real bugs** that SQLite tests could not: a stale-snapshot read after waiting on a lock (duplicate open loan applications were possible) and a first-portfolio insert race (500s under load). Both are fixed (lock-first ordering; locking reads + upsert-with-retry in `refreshPortfolio`).
Caveat: tested on MariaDB 10.11 only.

## Account security
* **Registration token / email verification.** Registering (or an admin creating a user) issues a 64-character random token. Only its SHA-256 hash is stored in `users.registration_token`, with an expiry (24 h) and a sent-at timestamp. Verification is single use, compared in constant time, and clears the token. Money endpoints refuse unverified accounts.
* **Password reset token.** Same design in `users.password_reset_token` (60 min). A successful reset changes the password, stamps `password_changed_at`, clears the token, rotates the remember token and **revokes every API token**. Completing a reset also proves mailbox ownership, so it verifies the address.
* **No enumeration.** Forgot-password and resend-verification answer identically for known and unknown addresses; a 60-second per-account cooldown limits mail flooding on top of the `auth` throttle.
* **Prefetch-safe console link.** The console verification link opens a confirmation page; only the POST consumes the token, so mail scanners that fetch URLs cannot burn it.
* **Notifications.** Password changed, reset link, verification link, email verified and account status changed are *security* notifications, and the financial ones are sent the same way: **synchronously, never queued** (after the database commit; a mail failure is reported but never fails the operation).
* Plain tokens are never stored or logged; the audit logger redacts `password`, `*_token`, `secret` keys.

## Audit trail
`audit_logs` is append-only: model guards throw on update/delete **and** database triggers (SQLite and MySQL/MariaDB) reject `UPDATE`/`DELETE`, so even raw SQL or a compromised app account cannot rewrite history without first dropping the trigger. Each row records time (µs), actor id/label/role, event, subject, description, old/new values and request context (IP, user agent, route). Rows are written **in the same transaction** as the change they describe, so a rollback leaves no phantom entry; failures of security checks (bad token, wrong password) are recorded after the rollback. Model events are captured by the `Audited` trait; domain events (`wallet.deposit`, `investment.contributed`, `loan.approved`, `auth.password_changed`, …) by the services. Admins browse it at `/admin/audit`.

## Ledger hardening (database-enforced)
* `transactions` rows are immutable except `status`; deletes are rejected (model + trigger).
* `wallets.balance >= 0` is a CHECK constraint (MariaDB) / trigger (SQLite).
* Per-user gapless `user_seq`, unique `(user_id, user_seq)`, allocated under the wallet lock.
* Unique `dedupe_key` on credits that must happen once (`pd:`, `ir:`, `wr:`, `li:`, `lr:`, `lp:` prefixes) – a repeated refund/credit fails instead of paying twice.
* `finance:reconcile` checks balances, sequence gaps, `total_raised` vs active investments, loan `total_paid`/balance vs payments, and profit distributions.

## Known limits
No MFA, no real payments/KYC, no IP allow-listing for the console. Audit rows are tamper-*resistant* (triggers), not tamper-*proof*: a DB superuser can drop the triggers; ship the table to write-once storage for stronger guarantees. Mail delivery depends on your SMTP provider; there is no retry, so a failed send is logged and lost. Add MFA and a payment/KYC provider before any real-money use.
