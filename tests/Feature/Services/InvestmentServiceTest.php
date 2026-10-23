<?php

namespace Tests\Feature\Services;

use App\Enums\ProjectStatus;
use App\Models\Transaction;
use App\Services\InvestmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsFinance;
use Tests\TestCase;

class InvestmentServiceTest extends TestCase
{
    use RefreshDatabase, BuildsFinance;

    private InvestmentService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->svc = app(InvestmentService::class);
    }

    public function test_investing_debits_wallet_updates_project_and_ownership(): void
    {
        $user = $this->investor('10000.00');
        $project = $this->project();

        $inv = $this->svc->invest($user->id, $project->id, '2500.00');

        $this->assertSame('7500.00', $this->balance($user));
        $this->assertSame('2500.00', $project->fresh()->total_raised);
        $this->assertSame('2.5000', $inv->ownership_percent);
        $this->assertSame('2500.00', $user->portfolio()->first()->total_invested);
    }

    public function test_repeated_contributions_accumulate_on_one_investment_row(): void
    {
        $user = $this->investor('10000.00');
        $project = $this->project();

        $this->svc->invest($user->id, $project->id, '1000.00');
        $inv = $this->svc->invest($user->id, $project->id, '1500.00');

        $this->assertSame('2500.00', $inv->total_invested);
        $this->assertSame(1, $user->investments()->count());
    }

    public function test_ownership_keeps_four_decimals(): void
    {
        $user = $this->investor('10000.00');
        $project = $this->project(['target_amount' => '30000.00']);

        $inv = $this->svc->invest($user->id, $project->id, '1000.00');
        $this->assertSame('3.3333', $inv->ownership_percent);
    }

    public function test_below_minimum_is_rejected(): void
    {
        $user = $this->investor();
        $project = $this->project();

        $this->expectException(ValidationException::class);
        $this->svc->invest($user->id, $project->id, '999.99');
    }

    public function test_more_than_remaining_is_rejected_and_nothing_moves(): void
    {
        $user = $this->investor('500000.00');
        $project = $this->project(['target_amount' => '5000.00', 'total_raised' => '4000.00']);

        try {
            $this->svc->invest($user->id, $project->id, '1000.01');
            $this->fail('over-subscription accepted');
        } catch (ValidationException) {
        }
        $this->assertSame('500000.00', $this->balance($user));
        $this->assertSame('4000.00', $project->fresh()->total_raised);
    }

    public function test_insufficient_wallet_rolls_back_everything(): void
    {
        $user = $this->investor('1500.00');
        $project = $this->project();

        try {
            $this->svc->invest($user->id, $project->id, '2000.00');
            $this->fail('accepted');
        } catch (ValidationException) {
        }

        $this->assertSame('1500.00', $this->balance($user));
        $this->assertSame('0.00', $project->fresh()->total_raised);
        $this->assertSame(0, $user->investments()->count());
    }

    public function test_reaching_the_target_marks_project_funded_and_blocks_more(): void
    {
        $user = $this->investor('10000.00');
        $project = $this->project(['target_amount' => '5000.00']);

        $this->svc->invest($user->id, $project->id, '5000.00');
        $this->assertSame(ProjectStatus::Funded, $project->fresh()->status);

        $this->expectException(ValidationException::class);
        $this->svc->invest($user->id, $project->id, '1000.00');
    }

    public function test_suspended_user_cannot_invest(): void
    {
        $user = $this->investor('5000.00');
        $user->forceFill(['status' => 'suspended'])->save();

        $this->expectException(ValidationException::class);
        $this->svc->invest($user->id, $this->project()->id, '1000.00');
    }

    public function test_reversal_refunds_and_reopens_a_funded_project(): void
    {
        $user = $this->investor('10000.00');
        $project = $this->project(['target_amount' => '5000.00']);
        $inv = $this->svc->invest($user->id, $project->id, '5000.00');

        $this->svc->reverseInvestment($inv->id);

        $this->assertSame('10000.00', $this->balance($user));
        $this->assertSame('0.00', $project->fresh()->total_raised);
        $this->assertSame(ProjectStatus::Open, $project->fresh()->status);
        $this->assertSame(0, $user->investments()->count());
    }

    public function test_income_and_expense_are_unique_per_month_and_accept_funded_projects(): void
    {
        $admin = $this->admin();
        $user = $this->investor('10000.00');
        $project = $this->project(['target_amount' => '5000.00']);
        $this->svc->invest($user->id, $project->id, '5000.00'); // → funded (used to block income entry)

        $this->svc->recordIncome($project->id, '1000.00', $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);

        $this->expectException(ValidationException::class);
        $this->svc->recordIncome($project->id, '500.00', $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);
    }

    public function test_every_wallet_balance_equals_its_ledger(): void
    {
        $user = $this->investor('10000.00');
        $project = $this->project();
        $inv = $this->svc->invest($user->id, $project->id, '3000.00');
        $this->svc->reverseInvestment($inv->id);

        $credits = Transaction::where('user_id', $user->id)->where('direction', 'credit')->sum('amount');
        $debits = Transaction::where('user_id', $user->id)->where('direction', 'debit')->sum('amount');
        $this->assertEquals($this->balance($user), number_format($credits - $debits, 2, '.', ''));
        $this->artisan('finance:reconcile')->assertSuccessful();
    }
}
