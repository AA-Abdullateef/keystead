<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\TransactionType;
use App\Models\Earning;
use App\Models\InvestmentProject;
use App\Models\ProfitDistribution;
use App\Models\Transaction;
use App\Models\UserProfitShare;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Monthly profit distribution.
 *
 *   net_profit            = rental income − expenses            (that calendar month, that project)
 *   management_fee        = net_profit × management_fee_percent  (retained by the company)
 *   distributable_profit  = net_profit − management_fee
 *   investor share        = distributable_profit × invested ÷ TARGET, rounded DOWN to the cent
 *   company_share         = distributable_profit − Σ investor shares   (unfunded capital + rounding dust)
 *
 * Shares are measured against the funding target, so capital that has not been raised yet earns
 * the company's (unfunded) share, as in the original design. Rounding down guarantees investors
 * can never be paid more than the distributable amount, and the residual makes the books balance
 * to the cent. See verify() for the reconciliation that tests and `finance:reconcile` run.
 */
class ProfitDistributionService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly NotificationService $notify,
        private readonly AuditLogger $audit,
    ) {
    }

    /** Pure calculation, no writes. */
    public function preview(string $projectId, int $month, int $year): array
    {
        $project = InvestmentProject::with(['investments.user', 'property'])->findOrFail($projectId);

        return $this->calculate($project, $month, $year);
    }

    public function distributionExists(string $projectId, int $month, int $year): bool
    {
        return ProfitDistribution::where([
            'investment_project_id' => $projectId, 'month' => $month, 'year' => $year,
        ])->exists();
    }

    public function distribute(string $projectId, int $month, int $year, string $adminId): ProfitDistribution
    {
        return DB::transaction(function () use ($projectId, $month, $year, $adminId) {
            // Same project lock as invest()/recordIncome(): the base data cannot change under us.
            $locked = InvestmentProject::whereKey($projectId)->lockForUpdate()->firstOrFail();

            if ($locked->status === ProjectStatus::Closed) {
                throw ValidationException::withMessages(['project' => 'The project is closed.']);
            }
            if (Carbon::create($year, $month, 1)->endOfMonth()->isFuture()) {
                throw ValidationException::withMessages(['month' => 'A period can only be distributed after it has ended.']);
            }
            if ($this->distributionExists($projectId, $month, $year)) {
                throw ValidationException::withMessages(['profit' => 'Profit has already been distributed for this period.']);
            }

            $project = InvestmentProject::with(['investments.user', 'property'])->findOrFail($projectId);
            $calc = $this->calculate($project, $month, $year);
            $t = $calc['totals'];

            try {
                $distribution = ProfitDistribution::create([
                    'investment_project_id' => $projectId,
                    'month' => $month,
                    'year' => $year,
                    'total_income' => $t['total_income'],
                    'total_expenses' => $t['total_expenses'],
                    'net_profit' => $t['net_profit'],
                    'management_fee_amount' => $t['management_fee_amount'],
                    'distributable_profit' => $t['distributable_profit'],
                    'investor_total' => $t['investor_total'],
                    'company_share' => $t['company_share'],
                    'status' => 'distributed',
                    'distributed_at' => now(),
                    'distributed_by' => $adminId,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['profit' => 'Profit has already been distributed for this period.']);
            }

            foreach ($calc['investors'] as $row) {
                if (! Money::isPositive($row['amount'])) {
                    continue; // less than one cent: stays in the company residual
                }

                Earning::create([
                    'user_id' => $row['user_id'],
                    'amount' => $row['amount'],
                    'type' => 'profit',
                    'reference_type' => 'profit_distribution',
                    'reference_id' => $distribution->id,
                    'month' => $month,
                    'year' => $year,
                ]);

                $this->wallets->credit($row['user_id'], $row['amount'], TransactionType::ProfitDistribution,
                    'profit_distribution', $distribution->id,
                    sprintf('Profit share %02d/%d — %s', $month, $year, $project->property->name),
                    'pd:' . $distribution->id . ':' . $row['user_id']);

                UserProfitShare::create([
                    'profit_distribution_id' => $distribution->id,
                    'user_id' => $row['user_id'],
                    'amount' => $row['amount'],
                    'share_percent' => $row['share_percent'],
                ]);

                $this->wallets->refreshPortfolio($row['user_id']);
                $this->notify->profitCredited($row['user_id'], $project->property->name, $row['amount'], $month, $year);
            }

            if ($locked->status === ProjectStatus::Funded) {
                $locked->update(['status' => ProjectStatus::Distributing]);
            }

            $this->audit->log('profit.distributed', $distribution, null, [
                'period' => sprintf('%04d-%02d', $year, $month), 'net_profit' => $t['net_profit'], 'management_fee' => $t['management_fee_amount'],
                'investor_total' => $t['investor_total'], 'company_share' => $t['company_share'], 'investors_paid' => count($calc['investors']),
            ], 'Monthly profit distributed to investor wallets');

            return $distribution;
        });
    }

    /**
     * Reconcile a stored distribution against the wallet ledger. Returns a list of discrepancies
     * (empty = books balance).
     *
     * @return list<string>
     */
    public function verify(ProfitDistribution $d): array
    {
        $problems = [];
        $eq = fn ($a, $b) => Money::sameFiat($a, $b);

        if (! $eq(Money::sub($d->total_income, $d->total_expenses), $d->net_profit)) {
            $problems[] = 'net_profit ≠ income − expenses';
        }
        if (! $eq(Money::add($d->management_fee_amount, $d->distributable_profit), $d->net_profit)) {
            $problems[] = 'fee + distributable ≠ net profit';
        }
        if (! $eq(Money::add($d->investor_total, $d->company_share), $d->distributable_profit)) {
            $problems[] = 'investor total + company share ≠ distributable';
        }

        $shares = (string) $d->userProfitShares()->sum('amount');
        if (! $eq($shares, $d->investor_total)) {
            $problems[] = 'sum(user_profit_shares) ≠ investor_total';
        }

        $credits = (string) Transaction::where('type', TransactionType::ProfitDistribution)
            ->where('reference_type', 'profit_distribution')->where('reference_id', $d->id)->sum('amount');
        if (! $eq($credits, $d->investor_total)) {
            $problems[] = 'sum(wallet credits) ≠ investor_total';
        }

        $earnings = (string) Earning::where('reference_type', 'profit_distribution')->where('reference_id', $d->id)->sum('amount');
        if (! $eq($earnings, $d->investor_total)) {
            $problems[] = 'sum(earnings) ≠ investor_total';
        }

        return $problems;
    }

    /* ------------------------------------------------------------ investor views */

    public function userProfitHistory(string $userId, ?string $projectId = null, int $perPage = 20)
    {
        return UserProfitShare::with('profitDistribution.project.property')
            ->where('user_id', $userId)
            ->when($projectId, fn ($q) => $q->whereHas('profitDistribution', fn ($d) => $d->where('investment_project_id', $projectId)))
            ->latest()
            ->paginate($perPage);
    }

    public function userSummary(string $userId): array
    {
        $investments = \App\Models\Investment::with('project')->where('user_id', $userId)->get();

        return [
            'total_invested' => Money::fiat((string) $investments->sum('total_invested')),
            'total_profit' => Money::fiat((string) UserProfitShare::where('user_id', $userId)->sum('amount')),
            'open_projects' => $investments->filter(fn ($i) => $i->project->status === ProjectStatus::Open)->count(),
            'funded_projects' => $investments->filter(fn ($i) => in_array($i->project->status, [ProjectStatus::Funded, ProjectStatus::Distributing], true))->count(),
            'closed_projects' => $investments->filter(fn ($i) => $i->project->status === ProjectStatus::Closed)->count(),
        ];
    }

    /* ------------------------------------------------------------------ internals */

    private function calculate(InvestmentProject $project, int $month, int $year): array
    {
        $income = Money::fiat((string) $project->rentals()->where('month', $month)->where('year', $year)->sum('amount'));
        $expenses = Money::fiat((string) $project->expenses()->where('month', $month)->where('year', $year)->sum('amount'));
        $net = Money::fiat(Money::sub($income, $expenses));

        if (! Money::isPositive($net)) {
            throw ValidationException::withMessages(['profit' => 'No profit is available for distribution in this period.']);
        }

        $fee = Money::fiat(Money::percent($net, (string) $project->management_fee_percent, 2));
        $distributable = Money::fiat(Money::sub($net, $fee));
        $target = Money::of($project->target_amount);

        $investors = [];
        $paid = '0.00';
        foreach ($project->investments as $inv) {
            $exact = bcdiv(bcmul(Money::of($distributable), Money::of($inv->total_invested), 8), $target, 8);
            $amount = Money::floorTo($exact, 2);
            $paid = bcadd($paid, $amount, 2);

            $investors[] = [
                'user_id' => $inv->user_id,
                'name' => $inv->user?->name ?? 'Unknown investor',
                'invested' => Money::fiat($inv->total_invested),
                'share_percent' => bcdiv(bcmul(Money::of($inv->total_invested), '100', 8), $target, 6),
                'amount' => $amount,
            ];
        }

        $company = Money::fiat(Money::sub($distributable, $paid));

        return [
            'project' => $project,
            'totals' => [
                'total_income' => $income,
                'total_expenses' => $expenses,
                'net_profit' => $net,
                'management_fee_percent' => Money::fiat((string) $project->management_fee_percent),
                'management_fee_amount' => $fee,
                'distributable_profit' => $distributable,
                'investor_total' => bcadd($paid, '0', 2),
                'company_share' => $company,
            ],
            'investors' => $investors,
            'company' => [
                'name' => 'Company (unfunded capital & rounding)',
                'share_percent' => bcsub('100', array_reduce($investors, fn ($c, $r) => bcadd($c, $r['share_percent'], 6), '0'), 6),
                'amount' => $company,
            ],
        ];
    }
}
