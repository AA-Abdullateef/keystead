<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\TransactionType;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\InvestmentProject;
use App\Models\ProfitDistribution;
use App\Models\Property;
use App\Models\RentalIncome;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly NotificationService $notify,
        private readonly AuditLogger $audit,
    ) {
    }

    /* ------------------------------------------------------------------ projects */

    public function createProject(array $data, string $adminId): InvestmentProject
    {
        return DB::transaction(function () use ($data, $adminId) {
            $property = Property::whereKey($data['property_id'])->lockForUpdate()->firstOrFail();

            if ($property->type !== 'investment') {
                throw ValidationException::withMessages(['property_id' => 'Only properties of type "investment" can have an investment project.']);
            }

            if (InvestmentProject::where('property_id', $property->id)->exists()) {
                throw ValidationException::withMessages(['property_id' => 'An investment project already exists for this property.']);
            }

            if (Money::cmp($data['min_investment'], $data['target_amount']) > 0) {
                throw ValidationException::withMessages(['min_investment' => 'Minimum investment cannot exceed the funding target.']);
            }

            return InvestmentProject::create([
                'property_id' => $property->id,
                'target_amount' => Money::fiat($data['target_amount']),
                'min_investment' => Money::fiat($data['min_investment']),
                'management_fee_percent' => $data['management_fee_percent'],
                'expected_roi' => $data['expected_roi'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'status' => ProjectStatus::Open,
                'total_raised' => '0.00',
                'created_by' => $adminId,
            ]);
        });
    }

    /**
     * Commercial terms are frozen once anyone has invested. Status can still move along the
     * allowed lifecycle (see ProjectStatus); closing is blocked once a distribution exists
     * only in the sense that `closed` is terminal.
     */
    public function updateProject(string $projectId, array $data, string $adminId): InvestmentProject
    {
        return DB::transaction(function () use ($projectId, $data, $adminId) {
            $project = InvestmentProject::whereKey($projectId)->lockForUpdate()->firstOrFail();
            $hasInvestors = $project->investments()->exists();

            $termsChanged = collect(['target_amount', 'min_investment', 'management_fee_percent', 'property_id'])
                ->contains(fn ($f) => array_key_exists($f, $data) && $this->differs($project->{$f}, $data[$f]));

            if ($hasInvestors && $termsChanged) {
                throw ValidationException::withMessages(['project' => 'Terms cannot be changed after investors have contributed.']);
            }

            if (isset($data['status'])) {
                $next = ProjectStatus::from($data['status']);
                if (! $project->status->canTransitionTo($next)) {
                    throw ValidationException::withMessages(['status' => "A {$project->status->value} project cannot become {$next->value}."]);
                }
                $project->status = $next;
            }

            $merged = array_merge($project->only(['target_amount', 'min_investment']), array_intersect_key($data, ['target_amount' => 1, 'min_investment' => 1]));
            if (Money::cmp((string) $merged['min_investment'], (string) $merged['target_amount']) > 0) {
                throw ValidationException::withMessages(['min_investment' => 'Minimum investment cannot exceed the funding target.']);
            }

            $project->fill(array_filter(array_intersect_key($data, array_flip([
                'target_amount', 'min_investment', 'management_fee_percent', 'expected_roi', 'start_date', 'end_date',
            ])), fn ($v) => $v !== null));
            $project->updated_by = $adminId;
            $project->save();

            return $project->refresh();
        });
    }

    public function deleteProject(string $projectId): void
    {
        DB::transaction(function () use ($projectId) {
            $project = InvestmentProject::whereKey($projectId)->lockForUpdate()->firstOrFail();

            if ($project->investments()->exists() || $project->profitDistributions()->exists()
                || $project->rentals()->exists() || $project->expenses()->exists()) {
                throw ValidationException::withMessages(['project' => 'Cannot delete a project that has investments or financial records.']);
            }

            $project->delete();
        });
    }

    /* --------------------------------------------------------------- contributions */

    /**
     * Contribute to a project from the investor's wallet.
     *
     * Lock order: project → wallet. The whole operation is atomic: if any step fails nothing is kept.
     * Ownership is the contributor's share of the funding TARGET; unfunded capital is attributed
     * to the company at distribution time (see ProfitDistributionService).
     */
    public function invest(string $userId, string $projectId, string $amount): Investment
    {
        return DB::transaction(function () use ($userId, $projectId, $amount) {
            $amount = Money::fiat($amount);

            if (! Money::isPositive($amount)) {
                throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
            }

            // The project lock must be the FIRST statement: on MySQL/MariaDB (REPEATABLE READ) the first plain
            // read fixes the transaction snapshot, and anything read after waiting for a lock would be stale.
            $project = InvestmentProject::whereKey($projectId)->lockForUpdate()->firstOrFail();

            $user = User::findOrFail($userId);
            if (! $user->isActive()) {
                throw ValidationException::withMessages(['user' => 'This account is suspended and cannot invest.']);
            }

            if (! $project->status->acceptsInvestments()) {
                throw ValidationException::withMessages(['project' => 'This project is not open for investment.']);
            }

            if (Money::cmp($amount, $project->min_investment) < 0) {
                throw ValidationException::withMessages(['amount' => 'Below the minimum investment of ' . Money::display($project->min_investment) . '.']);
            }

            $remaining = Money::sub($project->target_amount, $project->total_raised);
            if (Money::cmp($amount, $remaining) > 0) {
                throw ValidationException::withMessages(['amount' => 'Exceeds the remaining amount of ' . Money::display($remaining) . '.']);
            }

            $investment = Investment::where('user_id', $userId)->where('investment_project_id', $projectId)->lockForUpdate()->first();
            if (! $investment) {
                try {
                    $investment = Investment::create([
                        'user_id' => $userId,
                        'investment_project_id' => $projectId,
                        'total_invested' => '0.00',
                        'ownership_percent' => '0.0000',
                        'status' => 'active',
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $investment = Investment::where('user_id', $userId)->where('investment_project_id', $projectId)->lockForUpdate()->firstOrFail();
                }
            }

            // Money out of the wallet first: throws (and rolls everything back) on insufficient balance.
            $this->wallets->debit($userId, $amount, TransactionType::Investment, 'investment', $investment->id,
                'Investment contribution');

            $investment->total_invested = Money::fiat(Money::add($investment->total_invested, $amount));
            $investment->ownership_percent = $this->percentOfTarget($investment->total_invested, $project->target_amount);
            $investment->save();

            $project->total_raised = Money::fiat(Money::add($project->total_raised, $amount));
            if (Money::cmp($project->total_raised, $project->target_amount) >= 0) {
                $project->status = ProjectStatus::Funded;
            }
            $project->save();

            $this->wallets->refreshPortfolio($userId);

            $this->audit->log('investment.contributed', $investment, null,
                ['amount' => $amount, 'total_invested' => $investment->total_invested, 'project_total_raised' => $project->total_raised, 'project_status' => $project->status->value],
                'Contribution debited from wallet');
            $this->notify->investmentConfirmed($investment, $amount);

            return $investment->load('project.property');
        });
    }

    /** Admin top-up on behalf of an investor. Increase-only, same rules as invest(). */
    public function increaseInvestment(string $investmentId, string $newTotal): Investment
    {
        $investment = Investment::findOrFail($investmentId);

        if (Money::cmp($newTotal, $investment->total_invested) <= 0) {
            throw ValidationException::withMessages(['total_invested' => 'The new total must be greater than the current amount.']);
        }

        return $this->invest($investment->user_id, $investment->investment_project_id,
            Money::sub($newTotal, $investment->total_invested));
    }

    /**
     * Undo an investment before any profit has been distributed: the money returns to the
     * investor's wallet (ledger row `investment_reversal`), the project total shrinks, and a
     * funded project reopens.
     */
    public function reverseInvestment(string $investmentId): void
    {
        // Resolve the project outside the transaction (autocommit read, no retained snapshot) so the
        // lock order stays project → investment → wallet, the same as invest().
        $projectId = Investment::findOrFail($investmentId)->investment_project_id;

        DB::transaction(function () use ($investmentId, $projectId) {
            $project = InvestmentProject::whereKey($projectId)->lockForUpdate()->firstOrFail();
            $investment = Investment::whereKey($investmentId)->lockForUpdate()->firstOrFail();

            // locking read: sees a distribution committed while we waited for the project lock
            if ($project->profitDistributions()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['investment' => 'Cannot reverse an investment after profit has been distributed.']);
            }
            if ($project->status === ProjectStatus::Closed) {
                throw ValidationException::withMessages(['investment' => 'The project is closed.']);
            }

            $this->wallets->credit($investment->user_id, Money::fiat($investment->total_invested), TransactionType::InvestmentReversal,
                'investment', $investment->id, 'Investment reversed by administrator', 'ir:' . $investment->id);
            $refunded = Money::fiat($investment->total_invested);
            $propertyName = $project->property()->value('name');

            $project->total_raised = Money::fiat(Money::sub($project->total_raised, $investment->total_invested));
            if ($project->status === ProjectStatus::Funded) {
                $project->status = ProjectStatus::Open;
            }
            $project->save();

            $userId = $investment->user_id;
            $this->audit->log('investment.reversed', $investment, ['total_invested' => $refunded], ['refunded_to_wallet' => $refunded], 'Investment reversed and refunded');
            $investment->delete();
            $this->wallets->refreshPortfolio($userId);
            $this->notify->investmentReversed($userId, (string) $propertyName, $refunded);
        });
    }

    /* ------------------------------------------------------- income and expenses */

    public function recordIncome(string $projectId, string $amount, int $month, int $year, string $adminId): RentalIncome
    {
        return $this->withOpenPeriod($projectId, $month, $year, function () use ($projectId, $amount, $month, $year, $adminId) {
            try {
                return RentalIncome::create([
                    'investment_project_id' => $projectId,
                    'amount' => Money::fiat($amount),
                    'month' => $month,
                    'year' => $year,
                    'recorded_by' => $adminId,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['month' => 'Rental income is already recorded for this project and period.']);
            }
        });
    }

    public function recordExpense(string $projectId, string $type, string $amount, int $month, int $year, ?string $note, string $adminId): Expense
    {
        return $this->withOpenPeriod($projectId, $month, $year, function () use ($projectId, $type, $amount, $month, $year, $note, $adminId) {
            try {
                return Expense::create([
                    'investment_project_id' => $projectId,
                    'type' => $type,
                    'amount' => Money::fiat($amount),
                    'month' => $month,
                    'year' => $year,
                    'note' => $note,
                    'recorded_by' => $adminId,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['type' => 'This expense type is already recorded for the period.']);
            }
        });
    }

    public function updateIncome(string $id, array $data): RentalIncome
    {
        $record = RentalIncome::findOrFail($id);

        return $this->mutateRecord($record, $data, fn () => $record->update(['amount' => Money::fiat($data['amount'])]), $record);
    }

    public function updateExpense(string $id, array $data): Expense
    {
        $record = Expense::findOrFail($id);

        return $this->mutateRecord($record, $data, fn () => $record->update([
            'type' => $data['type'], 'amount' => Money::fiat($data['amount']), 'note' => $data['note'] ?? null,
        ]), $record);
    }

    public function deleteIncome(string $id): void
    {
        $record = RentalIncome::findOrFail($id);
        $this->mutateRecord($record, [], fn () => $record->delete(), $record);
    }

    public function deleteExpense(string $id): void
    {
        $record = Expense::findOrFail($id);
        $this->mutateRecord($record, [], fn () => $record->delete(), $record);
    }

    /** Period (month/year/project) of an existing record can never be edited; amounts only, and only before distribution. */
    private function mutateRecord($record, array $data, \Closure $apply, $return)
    {
        return $this->withOpenPeriod($record->investment_project_id, $record->month, $record->year, function () use ($apply, $return) {
            $apply();

            return $return;
        }, validatePeriod: false);
    }

    private function withOpenPeriod(string $projectId, int $month, int $year, \Closure $work, bool $validatePeriod = true)
    {
        return DB::transaction(function () use ($projectId, $month, $year, $work, $validatePeriod) {
            // Same lock the distribution takes, so recording and distributing the same month serialise.
            $project = InvestmentProject::whereKey($projectId)->lockForUpdate()->firstOrFail();

            if (! $project->status->acceptsLedgerEntries()) {
                throw ValidationException::withMessages(['project' => 'The project is closed.']);
            }

            if (ProfitDistribution::where('investment_project_id', $projectId)->where('month', $month)->where('year', $year)->exists()) {
                throw ValidationException::withMessages(['month' => 'Profit has already been distributed for this period; it can no longer be changed.']);
            }

            if ($validatePeriod) {
                $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
                if ($periodStart->isFuture()) {
                    throw ValidationException::withMessages(['month' => 'The period cannot be in the future.']);
                }
                if ($periodStart->lt($project->start_date->copy()->startOfMonth())) {
                    throw ValidationException::withMessages(['month' => 'The period is before the project start date.']);
                }
            }

            return $work();
        });
    }

    /* ------------------------------------------------------------------ helpers */

    public function percentOfTarget(string $invested, string $target): string
    {
        return bcdiv(bcmul(Money::of($invested), '100', 8), Money::of($target), 4);
    }

    private function differs(mixed $current, mixed $incoming): bool
    {
        if (is_numeric($current) && is_numeric($incoming)) {
            return bccomp((string) $current, (string) $incoming, 2) !== 0;
        }

        return (string) $current !== (string) $incoming;
    }

    public function projectSummary(string $projectId): array
    {
        $project = InvestmentProject::with(['investments.user', 'property'])->findOrFail($projectId);
        $income = (string) $project->rentals()->sum('amount');
        $expenses = (string) $project->expenses()->sum('amount');

        return [
            'project' => $project,
            'total_invested' => Money::fiat((string) $project->investments->sum('total_invested')),
            'total_income' => Money::fiat($income),
            'total_expenses' => Money::fiat($expenses),
            'net_profit' => Money::fiat(Money::sub($income, $expenses)),
            'investors_count' => $project->investments->count(),
        ];
    }
}
