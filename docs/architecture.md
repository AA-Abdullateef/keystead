# Architecture

## Layers
`Route → Middleware (auth:sanctum, active, role, throttle) → FormRequest → Controller → Policy → Service → Model → Resource`

* Controllers contain no money logic. Every state change lives in a service and runs inside one DB transaction.
* `WalletService` is the **only** code that changes `wallets.balance`. It writes a ledger row (`transactions`) in the same transaction.
* The staff console and the API share the same services — an admin "invest on behalf" goes through exactly the code the API uses.

## Data model (summary)
`users ↔ roles` (pivot, single source of truth) · `wallets` (1:1) · `transactions` (append-only ledger) · `portfolios` (derived snapshot)
· `properties` → `investment_projects` → `investments` · `rental_incomes` / `expenses` (unique per project+month) ·
`profit_distributions` (unique project+month+year) → `user_profit_shares` · `earnings`
· `loan_projects` (products) → `mortgage_loans` / `property_loans` → `*_payments` (unique loan+period).
UUID primary keys everywhere; foreign keys and unique constraints are the last line of defence behind the service checks.

## Lock order
Always **project / loan → investment → wallet**, never the reverse, to avoid deadlocks. The first statement in every
money transaction is the `SELECT … FOR UPDATE` on the project/loan (see the note on REPEATABLE READ in `InvestmentService::invest`).

## Lifecycles
* Project: `open → funded → distributing → closed` (`funded → open` only via reversing an investment before any distribution). `closed` is terminal.
* Loan: `pending → active → overdue ⇄ active → completed`; `pending → rejected | cancelled`. Anything else is a 422.
* Withdrawal (a ledger row): `pending → completed | rejected`.

## Authorization
`admin` — everything incl. money movement. `manager` — read and day-to-day records. `investor` — API only, own data only.
Enforced by route middleware (`role:admin`), policies (loans, transactions) and query scoping.

## API conventions
Versioned (`/api/v1`), single JSON envelope, Form Requests, API Resources, pagination `meta`/`links`, rate limiters `api`, `auth`, `money`.
Money is always a string with two decimals (`"1250.00"`), never a float, on input and output.

## Notifications
`NotificationService` is the only place that decides who is told what; services call one named method (`investmentConfirmed`, `withdrawalApproved`, `loanPaid`, `passwordChanged`, …) and never touch Mail directly. Each send is deferred with `DB::afterCommit`, so nobody is e-mailed about a rolled-back change, and a mail failure is `report()`ed but never fails the business operation.
* `Notifications/Financial/*` extend `FinancialNotification` (**not queued**, scalar payloads only): investment confirmed/reversed, withdrawal requested/approved/rejected (with refund), profit credited, loan submitted/approved/rejected/cancelled, loan payment received, wallet funded.
* `Notifications/Security/*` extend `SecurityNotification` (**not queued**, same as financial): verify registration, email verified, reset link, password changed, account status changed.
* Mail uses the Laravel markdown mailer with a Keystead theme (`resources/views/vendor/mail`).

## Audit and token services
`AuditLogger` (explicit domain events) + `Audited` model trait (field-level diffs) → `audit_logs`. `AccountTokenService` owns registration/reset tokens for both the API and the console. See [security.md](security.md).
