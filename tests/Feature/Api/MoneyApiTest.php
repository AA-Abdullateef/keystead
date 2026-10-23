<?php

namespace Tests\Feature\Api;

use App\Enums\LoanType;
use App\Models\Transaction;
use App\Services\LoanService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFinance;
use Tests\TestCase;

class MoneyApiTest extends TestCase
{
    use RefreshDatabase, BuildsFinance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_invest_via_api(): void
    {
        $user = $this->investor('5000.00');
        $project = $this->project();

        $this->postJson('/api/v1/investments', ['investment_project_id' => $project->id, 'amount' => '1500.00'], $this->bearer($user))
            ->assertCreated()->assertJsonPath('data.total_invested', '1500.00');

        $this->assertSame('3500.00', $this->balance($user));
    }

    public function test_invest_validation_and_business_errors_are_422_envelopes(): void
    {
        $user = $this->investor('5000.00');
        $project = $this->project();
        $h = $this->bearer($user);

        $this->postJson('/api/v1/investments', ['investment_project_id' => $project->id, 'amount' => '12.345'], $h)->assertStatus(422)->assertJsonValidationErrors('amount');
        $this->postJson('/api/v1/investments', ['investment_project_id' => $project->id, 'amount' => '-5'], $h)->assertStatus(422);
        $this->postJson('/api/v1/investments', ['investment_project_id' => $project->id, 'amount' => '500.00'], $h)->assertStatus(422)->assertJsonPath('success', false);
        $this->postJson('/api/v1/investments', ['investment_project_id' => $project->id, 'amount' => '9000.00'], $h)->assertStatus(422);
        $this->assertSame('5000.00', $this->balance($user));
    }

    public function test_money_is_always_returned_as_two_decimal_strings(): void
    {
        $user = $this->investor('5000.5');
        $this->getJson('/api/v1/wallet', $this->bearer($user))->assertOk()->assertJsonPath('data.balance', '5000.50');
    }

    public function test_withdrawal_request_and_listing(): void
    {
        $user = $this->investor('800.00');
        $h = $this->bearer($user);

        $this->postJson('/api/v1/withdrawals', ['amount' => '300.00'], $h)->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->postJson('/api/v1/withdrawals', ['amount' => '100.00'], $h)->assertStatus(422); // one pending at a time
        $this->getJson('/api/v1/withdrawals', $h)->assertOk()->assertJsonCount(1, 'data')->assertJsonStructure(['meta' => ['current_page', 'total'], 'links']);
        $this->assertSame('500.00', $this->balance($user));
    }

    public function test_a_user_only_sees_their_own_transactions_and_earnings(): void
    {
        $a = $this->investor('100.00');
        $b = $this->investor('200.00');

        $r = $this->getJson('/api/v1/transactions', $this->bearer($a))->assertOk();
        $ids = collect($r->json('data'))->pluck('id');
        $this->assertSame(Transaction::where('user_id', $a->id)->count(), $ids->count());
        $this->assertFalse($ids->contains(Transaction::where('user_id', $b->id)->value('id')));

        $foreign = Transaction::where('user_id', $b->id)->value('id');
        $this->getJson("/api/v1/transactions/$foreign", $this->bearer($a))->assertNotFound();
    }

    public function test_earnings_endpoint_does_not_leak_other_users_rows(): void
    {
        $admin = $this->admin();
        $a = $this->investor('5000.00');
        $b = $this->investor('5000.00');
        $project = $this->project(['target_amount' => '2000.00', 'min_investment' => '1000.00']);
        app(\App\Services\InvestmentService::class)->invest($a->id, $project->id, '1000.00');
        app(\App\Services\InvestmentService::class)->invest($b->id, $project->id, '1000.00');
        [$m, $y] = $this->lastMonth();
        app(\App\Services\InvestmentService::class)->recordIncome($project->id, '1000.00', $m, $y, $admin->id);
        app(\App\Services\ProfitDistributionService::class)->distribute($project->id, $m, $y, $admin->id);

        $rows = $this->getJson('/api/v1/earnings', $this->bearer($a))->assertOk()->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame('450.00', $rows[0]['amount']);
    }

    /* ---------------------------------------------------------------- loans */

    public function test_loan_application_and_repayment_flow_over_the_api(): void
    {
        $user = $this->investor('50000.00');
        $product = $this->loanProject(LoanType::Mortgage);
        $h = $this->bearer($user);

        $id = $this->postJson('/api/v1/loans/mortgage', ['loan_project_id' => $product->id, 'initial_payment' => '20000.00'], $h)
            ->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');

        $this->postJson("/api/v1/loans/mortgage/$id/payments", [], $h)->assertStatus(422); // not active yet

        app(LoanService::class)->approve(LoanType::Mortgage, $id, $this->admin()->id);

        $this->postJson("/api/v1/loans/mortgage/$id/payments", ['amount' => '1.00'], $h)->assertStatus(422);
        $this->postJson("/api/v1/loans/mortgage/$id/payments", ['amount' => '9166.67'], $h)->assertCreated();
        $this->postJson("/api/v1/loans/mortgage/$id/payments", [], $h)->assertStatus(422); // same month again
        $this->getJson("/api/v1/loans/mortgage/$id", $h)->assertOk()->assertJsonPath('data.balance_remaining', '100833.33');
    }

    public function test_loan_type_in_the_url_must_match_the_loan(): void
    {
        $user = $this->investor('50000.00');
        $loan = app(LoanService::class)->apply(LoanType::Mortgage, $this->loanProject()->id, '20000.00', $user->id);

        $this->getJson("/api/v1/loans/property/{$loan->id}", $this->bearer($user))->assertNotFound();
        $this->getJson("/api/v1/loans/bogus/{$loan->id}", $this->bearer($user))->assertNotFound();
    }

    public function test_other_users_loans_are_invisible_and_untouchable(): void
    {
        $owner = $this->investor('50000.00');
        $stranger = $this->investor('50000.00');
        $loan = app(LoanService::class)->apply(LoanType::Mortgage, $this->loanProject()->id, '20000.00', $owner->id);
        app(LoanService::class)->approve(LoanType::Mortgage, $loan->id, $this->admin()->id);
        $h = $this->bearer($stranger);

        $this->getJson("/api/v1/loans/mortgage/{$loan->id}", $h)->assertNotFound();
        $this->postJson("/api/v1/loans/mortgage/{$loan->id}/payments", [], $h)->assertNotFound();
        $this->postJson("/api/v1/loans/mortgage/{$loan->id}/cancel", [], $h)->assertNotFound();
        $this->getJson('/api/v1/loans/mortgage', $h)->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame('50000.00', $this->balance($stranger));
    }

    public function test_cancelling_over_the_api_refunds_the_wallet(): void
    {
        $user = $this->investor('50000.00');
        $h = $this->bearer($user);
        $id = $this->postJson('/api/v1/loans/mortgage', ['loan_project_id' => $this->loanProject()->id, 'initial_payment' => '20000.00'], $h)->json('data.id');

        $this->postJson("/api/v1/loans/mortgage/$id/cancel", [], $h)->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame('50000.00', $this->balance($user));
        $this->postJson("/api/v1/loans/mortgage/$id/cancel", [], $h)->assertStatus(422);
        $this->assertSame('50000.00', $this->balance($user));
    }

    public function test_profile_update_cannot_change_roles(): void
    {
        $user = $this->investor('0.00');
        $this->putJson('/api/v1/profile', ['first_name' => 'Ada', 'role' => 'admin', 'user_id' => 'x'], $this->bearer($user))->assertOk();
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_concurrent_style_replay_of_a_withdrawal_cannot_overdraw(): void
    {
        $user = $this->investor('100.00');
        $wallets = app(WalletService::class);
        $ok = 0;
        foreach ([60, 60, 60] as $amt) {
            try {
                $wallets->debit($user->id, (string) $amt, \App\Enums\TransactionType::Investment, 'investment', 'x');
                $ok++;
            } catch (\Illuminate\Validation\ValidationException) {
            }
        }
        $this->assertSame(1, $ok);
        $this->assertSame('40.00', $this->balance($user));
    }
}
