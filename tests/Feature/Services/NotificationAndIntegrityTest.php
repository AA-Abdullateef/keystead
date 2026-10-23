<?php

namespace Tests\Feature\Services;

use App\Enums\LoanType;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Notifications\Financial as F;
use App\Services\InvestmentService;
use App\Services\LoanService;
use App\Services\ProfitDistributionService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsFinance;
use Tests\TestCase;

class NotificationAndIntegrityTest extends TestCase
{
    use RefreshDatabase, BuildsFinance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_investment_and_reversal_notify_and_audit(): void
    {
        $user = $this->investor('5000.00');
        $project = $this->project();
        Notification::fake();

        $inv = app(InvestmentService::class)->invest($user->id, $project->id, '1500.00');
        Notification::assertSentTo($user, F\InvestmentConfirmed::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'investment.contributed', 'subject_id' => $inv->id]);

        app(InvestmentService::class)->reverseInvestment($inv->id);
        Notification::assertSentTo($user, F\InvestmentReversed::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'investment.reversed']);
        $this->assertSame('5000.00', $this->balance($user));
    }

    public function test_failed_operation_sends_nothing(): void
    {
        $user = $this->investor('100.00');
        $project = $this->project();
        Notification::fake();

        try {
            app(InvestmentService::class)->invest($user->id, $project->id, '9000.00');
        } catch (ValidationException) {
        }
        Notification::assertNothingSent();
    }

    public function test_withdrawal_lifecycle_notifications(): void
    {
        $user = $this->investor('800.00');
        $admin = $this->admin();
        $w = app(WalletService::class);
        Notification::fake();

        $t1 = $w->requestWithdrawal($user->id, '300.00');
        Notification::assertSentTo($user, F\WithdrawalRequested::class);
        $w->approveWithdrawal($t1->id, $admin->id);
        Notification::assertSentTo($user, F\WithdrawalApproved::class);

        $t2 = $w->requestWithdrawal($user->id, '100.00');
        $w->rejectWithdrawal($t2->id, $admin->id);
        Notification::assertSentTo($user, F\WithdrawalRejected::class);
        $this->assertSame('500.00', $this->balance($user));
    }

    public function test_admin_wallet_funding_notifies_the_investor(): void
    {
        $user = $this->investor('0.00');
        Notification::fake();
        app(WalletService::class)->deposit($user->id, '250.00', $this->admin()->id, 'Bank transfer');

        Notification::assertSentTo($user, F\WalletFunded::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'wallet.deposit']);
    }

    public function test_loan_lifecycle_notifications(): void
    {
        $user = $this->investor('50000.00');
        $admin = $this->admin();
        $loans = app(LoanService::class);
        $product = $this->loanProject();
        Notification::fake();

        $loan = $loans->apply(LoanType::Mortgage, $product->id, '20000.00', $user->id);
        Notification::assertSentTo($user, F\LoanApplicationSubmitted::class);
        $loans->approve(LoanType::Mortgage, $loan->id, $admin->id);
        Notification::assertSentTo($user, F\LoanApproved::class);
        $loans->pay(LoanType::Mortgage, $loan->id, $user->id);
        Notification::assertSentTo($user, F\LoanPaymentReceived::class);

        $p2 = $this->loanProject();
        $second = $loans->apply(LoanType::Mortgage, $p2->id, '20000.00', $user->id);
        $loans->reject(LoanType::Mortgage, $second->id);
        Notification::assertSentTo($user, F\LoanRejected::class);

        $p3 = $this->loanProject();
        $third = $loans->apply(LoanType::Mortgage, $p3->id, '20000.00', $user->id);
        $loans->cancel(LoanType::Mortgage, $third->id, $user->id);
        Notification::assertSentTo($user, F\LoanCancelled::class);
    }

    public function test_a_mail_failure_never_breaks_the_money_operation(): void
    {
        $user = $this->investor('0.00');
        $this->app->bind(\Illuminate\Contracts\Notifications\Dispatcher::class, fn () => new class {
            public function send(...$a): void { throw new \RuntimeException('smtp down'); }
            public function sendNow(...$a): void { throw new \RuntimeException('smtp down'); }
        });

        app(WalletService::class)->deposit($user->id, '50.00', $this->admin()->id, 'x');
        $this->assertSame('50.00', $this->balance($user));
    }

    public function test_ledger_rows_are_gapless_per_user_and_immutable(): void
    {
        $user = $this->investor('1000.00');
        app(WalletService::class)->deposit($user->id, '10.00', $this->admin()->id, 'x');

        $seqs = Transaction::where('user_id', $user->id)->orderBy('user_seq')->pluck('user_seq')->all();
        $this->assertSame(range(1, count($seqs)), $seqs);

        $txn = Transaction::where('user_id', $user->id)->firstOrFail();
        try {
            $txn->update(['amount' => '1.00']);
            $this->fail('amount mutated');
        } catch (\LogicException) {
        }
        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('transactions')->where('id', $txn->id)->update(['amount' => '1.00']);
    }

    public function test_ledger_rows_cannot_be_deleted(): void
    {
        $user = $this->investor('100.00');
        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('transactions')->where('user_id', $user->id)->delete();
    }

    public function test_dedupe_key_blocks_a_duplicate_credit(): void
    {
        $user = $this->investor('0.00');
        $w = app(WalletService::class);
        $w->credit($user->id, '5.00', \App\Enums\TransactionType::Deposit, 'test', 'ref-1', 'once', 'dup:1');

        $this->expectException(ValidationException::class);
        $w->credit($user->id, '5.00', \App\Enums\TransactionType::Deposit, 'test', 'ref-2', 'twice', 'dup:1');
    }

    public function test_balance_cannot_go_negative_even_with_raw_sql(): void
    {
        $user = $this->investor('10.00');
        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('wallets')->where('user_id', $user->id)->update(['balance' => '-1.00']);
    }

    public function test_reconcile_passes_clean_and_detects_tampering(): void
    {
        $user = $this->investor('1000.00');
        $this->artisan('finance:reconcile')->assertExitCode(0);

        \DB::table('wallets')->where('user_id', $user->id)->update(['balance' => '999.00']);
        $this->artisan('finance:reconcile')->assertExitCode(1);
    }

    public function test_reconcile_detects_project_total_drift(): void
    {
        $user = $this->investor('5000.00');
        $project = $this->project();
        app(InvestmentService::class)->invest($user->id, $project->id, '1500.00');
        $this->artisan('finance:reconcile')->assertExitCode(0);

        \DB::table('investment_projects')->where('id', $project->id)->update(['total_raised' => '1.00']);
        $this->artisan('finance:reconcile')->assertExitCode(1);
    }

    public function test_idempotency_key_replays_instead_of_repeating(): void
    {
        $user = $this->investor('1000.00');
        $h = $this->bearer($user) + ['Idempotency-Key' => 'abc-123'];

        $a = $this->postJson('/api/v1/withdrawals', ['amount' => '100.00'], $h)->assertCreated();
        $b = $this->postJson('/api/v1/withdrawals', ['amount' => '100.00'], $h);
        $b->assertCreated();
        $this->assertSame($a->json('data.id'), $b->json('data.id'));
        $this->assertSame(1, Transaction::where('user_id', $user->id)->where('type', 'withdrawal')->count());

        $this->postJson('/api/v1/withdrawals', ['amount' => '200.00'], $h)->assertStatus(422); // same key, different body
    }

    public function test_audit_viewer_is_admin_only(): void
    {
        $this->actingAs(\App\Models\User::factory()->manager()->create())->get('/admin/audit')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/audit?event=auth.')->assertOk();
    }
}
