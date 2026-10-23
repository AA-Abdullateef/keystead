#!/usr/bin/env bash
# Fires real parallel PHP processes at the money services on MySQL/MariaDB and prints the outcomes.
# DESTRUCTIVE to RACE_DB_DATABASE (default: keystead_race). Example:
#   RACE_DB_PORT=3307 RACE_DB_USERNAME=root ./scripts/concurrency-check.sh
set -u
cd "$(dirname "$0")/.."
H="php scripts/concurrency-check.php"
DB="${RACE_DB_DATABASE:-keystead_race}"; HOST="${RACE_DB_HOST:-127.0.0.1}"; PORT="${RACE_DB_PORT:-3306}"; USER="${RACE_DB_USERNAME:-root}"
q() { mysql -h"$HOST" -P"$PORT" -u"$USER" ${RACE_DB_PASSWORD:+-p"$RACE_DB_PASSWORD"} "$DB" -N -e "$1"; }
par() { n=$1; shift; t=$(mktemp -d); for i in $(seq "$n"); do $H "$@" >"$t/$i" 2>&1 & done; wait; cat "$t"/* | sort | uniq -c; rm -rf "$t"; }

$H setup
echo "== 12 parallel investments of 400 against a 1000.00 wallet (at most 2 may succeed)"; par 12 invest
q "select 'wallet', balance from wallets; select 'project raised', total_raised from investment_projects; select 'portfolio invested', total_invested from portfolios;"
echo "== 8 parallel withdrawal requests (one pending allowed)"; par 8 withdraw
q "select 'pending withdrawals', count(*) from transactions where type='withdrawal' and status='pending';"
q "update wallets set balance = balance" # no-op; documents that we never hand-edit balances
echo "== 8 parallel applications for the same loan product (needs funds: re-run setup)"; $H setup >/dev/null; par 8 apply
q "select 'loans', count(*) from mortgage_loans;"
LID=$(q "select id from mortgage_loans limit 1")
echo "== 6 approvals racing 6 rejections on one pending loan (exactly one decision, one refund at most)"
t=$(mktemp -d); for i in 1 2 3 4 5 6; do $H approve "$LID" >"$t/a$i" 2>&1 & $H reject "$LID" >"$t/r$i" 2>&1 & done; wait; cat "$t"/* | sort | uniq -c; rm -rf "$t"
q "select 'loan status', status from mortgage_loans; select 'wallet', balance from wallets;"
echo "== 8 parallel installment payments in the same month (only if the loan ended up active)"
if [ "$(q "select status from mortgage_loans")" = "active" ]; then par 8 pay "$LID"; q "select 'payments', count(*) from mortgage_payments;"; else echo "(loan was rejected — skipped)"; fi
echo "== reconcile"; php artisan finance:reconcile
