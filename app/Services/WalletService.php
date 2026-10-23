<?php

namespace App\Services;

use App\Enums\Direction;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Investment;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only code path that changes a wallet balance.
 *
 * Rules
 *  - Every balance change writes exactly one ledger row (transactions) in the same DB transaction.
 *  - The wallet row is locked FIRST (SELECT ... FOR UPDATE) before the balance is read; a plain read
 *    before the lock would fix the transaction snapshot on MySQL and allow lost updates.
 *  - Lock order across services is always: investment project / loan  →  wallet. Never the reverse.
 *  - Amounts are decimal strings (see App\Support\Money); floats never touch money.
 */
class WalletService
{
    public function __construct(
        private readonly NotificationService $notify,
        private readonly AuditLogger $audit,
    ) {
    }

    public function getWallet(string $userId): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $userId]);
    }

    /** Lock (creating if necessary) the user's wallet. Must be called inside a DB transaction. */
    public function lockWallet(string $userId): Wallet
    {
        $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

        if ($wallet) {
            return $wallet;
        }

        try {
            Wallet::create(['user_id' => $userId]);
        } catch (UniqueConstraintViolationException) {
            // another request created it first — fine
        }

        return Wallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();
    }

    public function credit(
        string $userId,
        string $amount,
        TransactionType $type,
        string $referenceType,
        string $referenceId,
        ?string $description = null,
        ?string $dedupeKey = null,
    ): Transaction {
        return DB::transaction(function () use ($userId, $amount, $type, $referenceType, $referenceId, $description, $dedupeKey) {
            $amount = $this->assertPositive($amount);
            $wallet = $this->lockWallet($userId);

            return $this->apply($wallet, Direction::Credit, $amount, $type, $referenceType, $referenceId,
                TransactionStatus::Completed, $description, $dedupeKey);
        });
    }

    public function debit(
        string $userId,
        string $amount,
        TransactionType $type,
        string $referenceType,
        string $referenceId,
        ?string $description = null,
        TransactionStatus $status = TransactionStatus::Completed,
        ?string $dedupeKey = null,
    ): Transaction {
        return DB::transaction(function () use ($userId, $amount, $type, $referenceType, $referenceId, $description, $status, $dedupeKey) {
            $amount = $this->assertPositive($amount);
            $wallet = $this->lockWallet($userId);

            if (Money::cmp($wallet->balance, $amount) < 0) {
                throw ValidationException::withMessages(['wallet' => 'Insufficient wallet balance.']);
            }

            return $this->apply($wallet, Direction::Debit, $amount, $type, $referenceType, $referenceId, $status, $description, $dedupeKey);
        });
    }

    /** Admin-recorded demo funding (no payment processor is involved). */
    public function deposit(string $userId, string $amount, string $adminId, ?string $note = null): Transaction
    {
        return DB::transaction(function () use ($userId, $amount, $adminId, $note) {
            $txn = $this->credit($userId, $amount, TransactionType::Deposit, 'wallet', $adminId, $note ?? 'Manual deposit');

            $this->audit->log('wallet.deposit', $txn, null, ['user_id' => $userId, 'amount' => $txn->amount, 'balance_after' => $txn->balance_after],
                'Administrator-recorded wallet funding', \App\Models\User::find($adminId));
            $this->notify->walletFunded($txn);

            return $txn;
        });
    }

    /**
     * A withdrawal immediately reserves the funds (debit, status=pending). An admin then
     * completes it (funds leave) or rejects it (funds are refunded with a separate credit row).
     */
    public function requestWithdrawal(string $userId, string $amount): Transaction
    {
        return DB::transaction(function () use ($userId, $amount) {
            $amount = $this->assertPositive($amount);
            $wallet = $this->lockWallet($userId); // serialises concurrent requests from the same user

            $hasPending = Transaction::where('user_id', $userId)
                ->where('type', TransactionType::Withdrawal)
                ->where('status', TransactionStatus::Pending)
                ->exists();

            if ($hasPending) {
                throw ValidationException::withMessages(['withdrawal' => 'You already have a pending withdrawal request.']);
            }

            if (Money::cmp($wallet->balance, $amount) < 0) {
                throw ValidationException::withMessages(['wallet' => 'Insufficient wallet balance.']);
            }

            $txn = $this->apply($wallet, Direction::Debit, $amount, TransactionType::Withdrawal, 'wallet',
                $wallet->id, TransactionStatus::Pending, 'Withdrawal request');

            $this->audit->log('withdrawal.requested', $txn, null, ['amount' => $txn->amount, 'status' => 'pending'], 'Withdrawal requested; funds reserved');
            $this->notify->withdrawalRequested($txn);

            return $txn;
        });
    }

    public function approveWithdrawal(string $transactionId): Transaction
    {
        return DB::transaction(function () use ($transactionId) {
            $txn = $this->lockPendingWithdrawal($transactionId);
            $txn->update(['status' => TransactionStatus::Completed]);
            $this->refreshPortfolio($txn->user_id);

            $this->audit->log('withdrawal.approved', $txn, ['status' => 'pending'], ['status' => 'completed'], 'Withdrawal approved');
            $this->notify->withdrawalApproved($txn);

            return $txn;
        });
    }

    public function rejectWithdrawal(string $transactionId): Transaction
    {
        return DB::transaction(function () use ($transactionId) {
            $txn = $this->lockPendingWithdrawal($transactionId);
            $wallet = $this->lockWallet($txn->user_id);

            $txn->update(['status' => TransactionStatus::Rejected]);

            $refund = $this->apply($wallet, Direction::Credit, Money::fiat($txn->amount), TransactionType::WithdrawalRefund,
                'transaction', $txn->id, TransactionStatus::Completed, 'Withdrawal rejected — funds returned', 'wr:' . $txn->id);

            $this->refreshPortfolio($txn->user_id);

            $this->audit->log('withdrawal.rejected', $txn, ['status' => 'pending'], ['status' => 'rejected', 'refund_transaction' => $refund->id], 'Withdrawal rejected and refunded');
            $this->notify->withdrawalRejected($txn);

            return $txn->refresh();
        });
    }

    /**
     * Recompute and persist the derived portfolio snapshot. Always derived from the
     * investments table and the ledger, never incremented, so it cannot drift.
     */
    public function refreshPortfolio(string $userId): Portfolio
    {
        // Locking reads (FOR UPDATE) always see the latest committed rows, even when this transaction's
        // snapshot was fixed before we waited on another request's lock.
        $invested = Investment::where('user_id', $userId)->lockForUpdate()->sum('total_invested');

        $ledger = fn (TransactionType $type) => (string) Transaction::where('user_id', $userId)
            ->where('type', $type)->where('status', TransactionStatus::Completed)->lockForUpdate()->sum('amount');

        $values = [
            'total_invested' => Money::fiat((string) $invested),
            'total_earnings' => Money::fiat($ledger(TransactionType::ProfitDistribution)),
            'total_withdrawn' => Money::fiat($ledger(TransactionType::Withdrawal)),
        ];

        $portfolio = Portfolio::where('user_id', $userId)->lockForUpdate()->first();
        if (! $portfolio) {
            try {
                return Portfolio::create(['user_id' => $userId] + $values);
            } catch (UniqueConstraintViolationException) {
                $portfolio = Portfolio::where('user_id', $userId)->lockForUpdate()->firstOrFail();
            }
        }
        $portfolio->update($values);

        return $portfolio;
    }

    private function apply(
        Wallet $wallet,
        Direction $direction,
        string $amount,
        TransactionType $type,
        string $referenceType,
        string $referenceId,
        TransactionStatus $status,
        ?string $description,
        ?string $dedupeKey = null,
    ): Transaction {
        $new = $direction === Direction::Credit
            ? Money::add($wallet->balance, $amount)
            : Money::sub($wallet->balance, $amount);

        $wallet->balance = Money::fiat($new);
        $wallet->ledger_seq = (int) $wallet->ledger_seq + 1; // gapless per-user sequence, allocated under the wallet lock
        $wallet->save();

        try {
            return Transaction::create([
            'user_id' => $wallet->user_id,
            'user_seq' => $wallet->ledger_seq,
            'dedupe_key' => $dedupeKey,
            'amount' => $amount,
            'direction' => $direction,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'status' => $status,
            'balance_after' => $wallet->balance,
            'description' => $description,
            ]);
        } catch (UniqueConstraintViolationException) {
            // The same one-off movement (refund, profit credit, installment …) was already recorded.
            throw ValidationException::withMessages(['ledger' => 'This movement has already been recorded.']);
        }
    }

    private function lockPendingWithdrawal(string $transactionId): Transaction
    {
        $txn = Transaction::whereKey($transactionId)->lockForUpdate()->firstOrFail();

        if ($txn->type !== TransactionType::Withdrawal || $txn->status !== TransactionStatus::Pending) {
            throw ValidationException::withMessages(['withdrawal' => 'This withdrawal is not pending.']);
        }

        return $txn;
    }

    private function assertPositive(string $amount): string
    {
        $amount = Money::fiat($amount);

        if (! Money::isPositive($amount)) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        return $amount;
    }
}
