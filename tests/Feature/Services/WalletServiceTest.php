<?php

namespace Tests\Feature\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsFinance;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase, BuildsFinance;

    private WalletService $wallets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->wallets = app(WalletService::class);
    }

    public function test_deposit_credits_wallet_and_writes_ledger_row_with_running_balance(): void
    {
        $user = $this->investor('0.00');
        $txn = $this->wallets->deposit($user->id, '250.50', $user->id);

        $this->assertSame('250.50', $this->balance($user));
        $this->assertSame('250.50', $txn->balance_after);
        $this->assertSame(TransactionType::Deposit, $txn->type);
    }

    public function test_debit_beyond_balance_is_rejected_and_leaves_no_trace(): void
    {
        $user = $this->investor('100.00');
        $before = Transaction::count();

        $this->expectException(ValidationException::class);
        try {
            $this->wallets->debit($user->id, '100.01', TransactionType::Investment, 'investment', 'x');
        } finally {
            $this->assertSame('100.00', $this->balance($user));
            $this->assertSame($before, Transaction::count());
        }
    }

    public function test_non_positive_amounts_are_rejected(): void
    {
        $user = $this->investor('100.00');
        foreach (['0', '0.00', '-5'] as $bad) {
            try {
                $this->wallets->credit($user->id, $bad, TransactionType::Deposit, 'wallet', 'x');
                $this->fail("amount $bad accepted");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame('100.00', $this->balance($user));
    }

    public function test_decimal_math_has_no_float_drift(): void
    {
        $user = $this->investor('0.00');
        for ($i = 0; $i < 10; $i++) {
            $this->wallets->deposit($user->id, '0.10', $user->id);
        }
        $this->assertSame('1.00', $this->balance($user));
    }

    public function test_withdrawal_reserves_funds_and_approval_completes_it(): void
    {
        $user = $this->investor('500.00');
        $admin = $this->admin();

        $txn = $this->wallets->requestWithdrawal($user->id, '200.00');
        $this->assertSame(TransactionStatus::Pending, $txn->status);
        $this->assertSame('300.00', $this->balance($user));

        $this->wallets->approveWithdrawal($txn->id);
        $this->assertSame(TransactionStatus::Completed, $txn->fresh()->status);
        $this->assertSame('300.00', $this->balance($user));
        $this->assertSame('200.00', $user->portfolio()->first()->total_withdrawn);
    }

    public function test_rejecting_a_withdrawal_refunds_with_its_own_ledger_row(): void
    {
        $user = $this->investor('500.00');
        $txn = $this->wallets->requestWithdrawal($user->id, '200.00');

        $this->wallets->rejectWithdrawal($txn->id);

        $this->assertSame('500.00', $this->balance($user));
        $this->assertTrue(Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::WithdrawalRefund)->where('amount', '200.00')->exists());
        $this->assertSame('0.00', $user->portfolio()->first()->total_withdrawn);
    }

    public function test_a_withdrawal_cannot_be_decided_twice(): void
    {
        $user = $this->investor('500.00');
        $txn = $this->wallets->requestWithdrawal($user->id, '200.00');
        $this->wallets->rejectWithdrawal($txn->id);

        $this->expectException(ValidationException::class);
        $this->wallets->rejectWithdrawal($txn->id); // would double-refund
    }

    public function test_only_one_pending_withdrawal_and_no_overdraw(): void
    {
        $user = $this->investor('500.00');
        $this->wallets->requestWithdrawal($user->id, '100.00');

        $this->expectException(ValidationException::class);
        $this->wallets->requestWithdrawal($user->id, '100.00');
    }

    public function test_withdrawal_larger_than_balance_is_rejected(): void
    {
        $user = $this->investor('50.00');
        $this->expectException(ValidationException::class);
        $this->wallets->requestWithdrawal($user->id, '50.01');
    }
}
