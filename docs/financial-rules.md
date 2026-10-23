# Financial rules

These preserve the original product's rules; where the original was ambiguous or contradictory the decision is recorded here.

## Money
Decimal strings + BCMath (`App\Support\Money`). Two decimal places everywhere. Inputs with more than two decimals are rejected, not rounded.

## Wallet & ledger
* Balance = Σ credits − Σ debits over the user's ledger (checked by `finance:reconcile`).
* Types: `deposit`, `investment`, `investment_reversal`, `profit_distribution`, `withdrawal`, `withdrawal_refund`, `loan_initial_payment`, `loan_refund`, `loan_payment`.
* Withdrawal: debit with status `pending` at request time (funds reserved). Approve → `completed`. Reject → `rejected` + a separate `withdrawal_refund` credit. One pending request per user.
* Deposits are **admin-recorded demo funding**; there is no payment processor. The investor is e-mailed when one is recorded.
* Integrity guards (no change to the rules above): balance can never go negative (CHECK/trigger); ledger rows are immutable except `status`; each user's rows carry a gapless `user_seq`; one-time credits (refunds, reversals, profit shares) carry a `dedupe_key` so a retry cannot pay twice.

## Investments
* Amount ≥ project minimum, ≤ remaining (target − raised). Repeat contributions accumulate on one row.
* `ownership_percent = invested ÷ target × 100` (4 dp).
* Reaching the target → `funded` (still accepts income/expenses; the original wrongly blocked this by closing the project).
* Terms (target, minimum, fee) are frozen once anyone has invested.
* Reversal (admin): refunds the wallet, shrinks `total_raised`, reopens a funded project — only before any distribution.

## Profit distribution (per project, per closed month)
```
net           = rental income − expenses            (must be > 0)
fee           = net × management_fee_percent        (company)
distributable = net − fee
investor      = floor(distributable × invested ÷ TARGET, 2)
company_share = distributable − Σ investor            (unfunded capital + rounding dust)
```
Shares are measured against the **target**, so unfunded capital earns the company share — the original behaviour, kept.
Rounding down guarantees investors are never over-paid. Future periods, loss-making periods, closed projects and repeat distributions are refused.
`verify()` proves: income − expenses = net; fee + distributable = net; investors + company = distributable; Σ shares = Σ earnings = Σ wallet credits = investor total.

## Loans
```
financed  = property value − initial payment
total     = financed × (1 + rate%)     (flat interest)
monthly   = total ÷ months  (2 dp);  last installment = remaining balance
```
* The initial payment must equal the product's minimum **exactly** (the original code contradicted itself; the strict rule was kept).
* It is debited at application, refunded once on reject/cancel, kept on approve.
* One open application/loan per user and product. Loan type must match the product and the route.
* One installment per calendar month (`period` = `YYYY-MM`, unique per loan). Wrong amount → 422. Insufficient funds → 422, no partial rows.
* `overdue` is set by the scheduled `loans:refresh-overdue` (never by a GET request) when paid < whole months elapsed × monthly.
