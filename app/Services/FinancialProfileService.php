<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\MortgageLoan;
use App\Models\PropertyLoan;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;

/** One place that assembles a user's financial overview (API, investor dashboard and admin profile). */
class FinancialProfileService
{
    public function __construct(private readonly WalletService $wallets)
    {
    }

    public function forUser(string $userId, bool $withLoans = true, int $recentTransactions = 10): array
    {
        $user = User::with('profile')->findOrFail($userId);
        $wallet = $this->wallets->getWallet($userId);
        $portfolio = $this->wallets->refreshPortfolio($userId);

        // "Current value" keeps the original definition: invested + earnings − completed withdrawals.
        $current = Money::fiat(Money::sub(Money::add($portfolio->total_invested, $portfolio->total_earnings), $portfolio->total_withdrawn));

        $out = [
            'user' => $user,
            'wallet' => $wallet,
            'portfolio' => [
                'total_invested' => $portfolio->total_invested,
                'total_earnings' => $portfolio->total_earnings,
                'total_withdrawn' => $portfolio->total_withdrawn,
                'current_value' => $current,
            ],
            'investments' => Investment::with('project.property')->where('user_id', $userId)->latest()->get(),
            'transactions' => Transaction::where('user_id', $userId)->latest()->limit($recentTransactions)->get(),
        ];

        if ($withLoans) {
            $mortgages = MortgageLoan::with('loanProject.property')->where('user_id', $userId)->latest()->get();
            $properties = PropertyLoan::with('loanProject.property')->where('user_id', $userId)->latest()->get();
            $all = $mortgages->concat($properties);

            $out['mortgage_loans'] = $mortgages;
            $out['property_loans'] = $properties;
            $out['loans'] = [
                'total_paid' => Money::fiat((string) $all->sum('total_paid')),
                'balance_remaining' => Money::fiat((string) $all->whereIn('status', ['active', 'overdue', 'pending'])->sum('balance_remaining')),
            ];
        }

        return $out;
    }
}
