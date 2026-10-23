<?php

namespace App\Console\Commands;

use App\Models\Investment;
use App\Models\InvestmentProject;
use App\Models\MortgageLoan;
use App\Models\ProfitDistribution;
use App\Models\PropertyLoan;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\ProfitDistributionService;
use App\Support\Money;
use Illuminate\Console\Command;

/**
 * Integrity audit: every wallet balance must equal credits − debits in its ledger,
 * and every profit distribution must reconcile with its wallet credits. Exit code 1 on any mismatch.
 */
class ReconcileFinances extends Command
{
    protected $signature = 'finance:reconcile';

    protected $description = 'Verify wallet balances, ledger sequences, project totals, loan balances and profit distributions';

    public function handle(ProfitDistributionService $profits): int
    {
        $problems = 0;

        Wallet::chunkById(200, function ($wallets) use (&$problems) {
            foreach ($wallets as $wallet) {
                // Every ledger row counts regardless of status: a rejected withdrawal is a debit that is
                // reversed by its own refund credit, so the pair nets to zero.
                $credits = (string) Transaction::where('user_id', $wallet->user_id)->where('direction', 'credit')->sum('amount');
                $debits = (string) Transaction::where('user_id', $wallet->user_id)->where('direction', 'debit')->sum('amount');
                $ledger = Money::fiat(Money::sub($credits, $debits));

                if (! Money::sameFiat($ledger, $wallet->balance)) {
                    $problems++;
                    $this->error("Wallet {$wallet->id}: balance {$wallet->balance} ≠ ledger {$ledger}");
                }

                // Per-user sequence must be 1..n with no gaps, and match the wallet's counter.
                $seqs = Transaction::where('user_id', $wallet->user_id)->orderBy('user_seq')->pluck('user_seq')->map(fn ($v) => (int) $v)->all();
                $expected = $seqs === [] ? [] : range(1, count($seqs));
                if ($seqs !== $expected || (int) $wallet->ledger_seq !== count($seqs)) {
                    $problems++;
                    $this->error("Wallet {$wallet->id}: ledger sequence has gaps or does not match wallet counter (" . count($seqs) . ' rows, counter ' . (int) $wallet->ledger_seq . ')');
                }
            }
        });

        ProfitDistribution::with('userProfitShares')->chunkById(100, function ($distributions) use ($profits, &$problems) {
            foreach ($distributions as $d) {
                foreach ($profits->verify($d) as $issue) {
                    $problems++;
                    $this->error("Distribution {$d->id} ({$d->month}/{$d->year}): {$issue}");
                }
            }
        });

        InvestmentProject::chunkById(100, function ($projects) use (&$problems) {
            foreach ($projects as $project) {
                $sum = Money::fiat((string) Investment::where('investment_project_id', $project->id)->where('status', 'active')->sum('total_invested'));
                if (! Money::sameFiat($sum, $project->total_raised)) {
                    $problems++;
                    $this->error("Project {$project->id}: total_raised {$project->total_raised} ≠ active investments {$sum}");
                }
            }
        });

        foreach ([MortgageLoan::class, PropertyLoan::class] as $model) {
            $model::chunkById(100, function ($loans) use (&$problems) {
                foreach ($loans as $loan) {
                    $paid = Money::fiat((string) $loan->payments()->sum('amount'));
                    if (! Money::sameFiat($paid, $loan->total_paid)) {
                        $problems++;
                        $this->error("Loan {$loan->id}: total_paid {$loan->total_paid} ≠ recorded payments {$paid}");
                    }
                    if (! Money::sameFiat(Money::add($loan->total_paid, $loan->balance_remaining), $loan->total_payable)) {
                        $problems++;
                        $this->error("Loan {$loan->id}: paid + balance ≠ total_payable");
                    }
                }
            });
        }

        $problems === 0 ? $this->info('All wallets, ledger sequences, projects, loans and distributions reconcile.') : $this->error("$problems problem(s) found.");

        return $problems === 0 ? self::SUCCESS : self::FAILURE;
    }
}
