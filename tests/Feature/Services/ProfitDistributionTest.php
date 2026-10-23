<?php

namespace Tests\Feature\Services;

use App\Enums\ProjectStatus;
use App\Models\ProfitDistribution;
use App\Models\Transaction;
use App\Services\InvestmentService;
use App\Services\ProfitDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsFinance;
use Tests\TestCase;

class ProfitDistributionTest extends TestCase
{
    use RefreshDatabase, BuildsFinance;

    private InvestmentService $inv;
    private ProfitDistributionService $profit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->inv = app(InvestmentService::class);
        $this->profit = app(ProfitDistributionService::class);
    }

    public function test_distribution_math_and_wallet_credits(): void
    {
        $admin = $this->admin();
        $a = $this->investor('50000.00');
        $b = $this->investor('50000.00');
        $project = $this->project(); // target 100 000, fee 10 %

        $this->inv->invest($a->id, $project->id, '30000.00');  // 30 %
        $this->inv->invest($b->id, $project->id, '20000.00');  // 20 %
        $this->inv->recordIncome($project->id, '5000.00', $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);
        $this->inv->recordExpense($project->id, 'maintenance', '500.00', $this->lastMonth(1)[0], $this->lastMonth(1)[1], null, $admin->id);

        $d = $this->profit->distribute($project->id, $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);

        // net 4500, fee 450, distributable 4050
        $this->assertSame('4500.00', $d->net_profit);
        $this->assertSame('450.00', $d->management_fee_amount);
        $this->assertSame('4050.00', $d->distributable_profit);
        $this->assertSame('1215.00', $d->userProfitShares()->where('user_id', $a->id)->value('amount'));
        $this->assertSame('810.00', $d->userProfitShares()->where('user_id', $b->id)->value('amount'));
        $this->assertSame('2025.00', $d->investor_total);
        $this->assertSame('2025.00', $d->company_share); // the 50 % that is still unfunded

        $this->assertSame('21215.00', $this->balance($a));
        $this->assertSame('30810.00', $this->balance($b));
        $this->assertSame([], $this->profit->verify($d));
        $this->artisan('finance:reconcile')->assertSuccessful();
    }

    public function test_rounding_never_overpays_and_residual_goes_to_company(): void
    {
        $admin = $this->admin();
        $users = [$this->investor('5000.00'), $this->investor('5000.00'), $this->investor('5000.00')];
        $project = $this->project(['target_amount' => '3000.00', 'min_investment' => '1.00', 'management_fee_percent' => '0.00']);
        foreach ($users as $u) {
            $this->inv->invest($u->id, $project->id, '1000.00'); // 1/3 each
        }
        $this->inv->recordIncome($project->id, '100.00', $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);

        $d = $this->profit->distribute($project->id, $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);

        $this->assertSame('33.33', $d->userProfitShares()->first()->amount);
        $this->assertSame('99.99', $d->investor_total);
        $this->assertSame('0.01', $d->company_share);
        $this->assertSame([], $this->profit->verify($d));
    }

    public function test_a_period_cannot_be_distributed_twice(): void
    {
        $admin = $this->admin();
        $u = $this->investor();
        $project = $this->project();
        $this->inv->invest($u->id, $project->id, '5000.00');
        $this->inv->recordIncome($project->id, '1000.00', $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);

        $this->profit->distribute($project->id, $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);
        $balance = $this->balance($u);

        try {
            $this->profit->distribute($project->id, $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);
            $this->fail('double distribution');
        } catch (ValidationException) {
        }
        $this->assertSame($balance, $this->balance($u));
        $this->assertSame(1, ProfitDistribution::count());
    }

    public function test_future_periods_cannot_be_distributed(): void
    {
        $admin = $this->admin();
        $project = $this->project();
        $next = now()->addMonth();

        $this->expectException(ValidationException::class);
        $this->profit->distribute($project->id, (int) $next->format('n'), (int) $next->format('Y'), $admin->id);
    }

    public function test_loss_making_month_cannot_be_distributed_and_pays_nobody(): void
    {
        $admin = $this->admin();
        $u = $this->investor();
        $project = $this->project(['target_amount' => '5000.00']);
        $this->inv->invest($u->id, $project->id, '5000.00');
        $this->inv->recordExpense($project->id, 'repairs', '700.00', $this->lastMonth(2)[0], $this->lastMonth(2)[1], null, $admin->id);

        $before = Transaction::count();
        try {
            $this->profit->distribute($project->id, $this->lastMonth(2)[0], $this->lastMonth(2)[1], $admin->id);
            $this->fail('a loss was distributed');
        } catch (ValidationException) {
        }
        $this->assertSame($before, Transaction::count());
        $this->assertSame(0, ProfitDistribution::count());
    }

    public function test_funded_project_moves_to_distributing(): void
    {
        $admin = $this->admin();
        $u = $this->investor();
        $project = $this->project(['target_amount' => '5000.00']);
        $this->inv->invest($u->id, $project->id, '5000.00');
        $this->inv->recordIncome($project->id, '100.00', $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);

        $this->profit->distribute($project->id, $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);
        $this->assertSame(ProjectStatus::Distributing, $project->fresh()->status);
    }

    public function test_investment_cannot_be_reversed_after_distribution(): void
    {
        $admin = $this->admin();
        $u = $this->investor();
        $project = $this->project();
        $investment = $this->inv->invest($u->id, $project->id, '5000.00');
        $this->inv->recordIncome($project->id, '100.00', $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);
        $this->profit->distribute($project->id, $this->lastMonth(1)[0], $this->lastMonth(1)[1], $admin->id);

        $this->expectException(ValidationException::class);
        $this->inv->reverseInvestment($investment->id);
    }

    public function test_reconcile_command_detects_tampering(): void
    {
        $user = $this->investor('100.00');
        $user->wallet()->first()->forceFill(['balance' => '999.00'])->save();

        $this->artisan('finance:reconcile')->assertFailed();
    }
}
