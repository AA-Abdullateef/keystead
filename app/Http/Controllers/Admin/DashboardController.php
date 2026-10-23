<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\InvestmentProject;
use App\Models\MortgageLoan;
use App\Models\MortgagePayment;
use App\Models\Property;
use App\Models\PropertyLoan;
use App\Models\PropertyLoanPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\Money;

class DashboardController extends Controller
{
    public function index()
    {
        $loanBook = fn (string $model) => [
            'count' => $model::count(),
            'pending' => $model::where('status', LoanStatus::Pending)->count(),
            'active' => $model::where('status', LoanStatus::Active)->count(),
            'overdue' => $model::where('status', LoanStatus::Overdue)->count(),
            'principal' => Money::fiat((string) $model::whereIn('status', [LoanStatus::Active, LoanStatus::Overdue, LoanStatus::Completed])->sum('loan_amount')),
            'outstanding' => Money::fiat((string) $model::whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])->sum('balance_remaining')),
        ];

        $monthStart = now()->startOfMonth()->toDateString();
        $collected = bcadd(
            (string) MortgagePayment::where('payment_date', '>=', $monthStart)->sum('amount'),
            (string) PropertyLoanPayment::where('payment_date', '>=', $monthStart)->sum('amount'), 2);

        return view('admin.index', [
            'users' => User::count(),
            'investors' => User::investors()->count(),
            'properties' => Property::count(),
            'projects' => InvestmentProject::count(),
            'openProjects' => InvestmentProject::where('status', 'open')->count(),
            'raised' => Money::fiat((string) InvestmentProject::sum('total_raised')),
            'investments' => Investment::count(),
            'walletTotal' => Money::fiat((string) Wallet::sum('balance')),
            'pendingWithdrawals' => Transaction::where('type', 'withdrawal')->where('status', 'pending')->count(),
            'mortgage' => $loanBook(MortgageLoan::class),
            'property' => $loanBook(PropertyLoan::class),
            'collectedThisMonth' => Money::fiat($collected),
            'recentTransactions' => Transaction::with('user')->latest()->limit(8)->get(),
            'recentLoans' => MortgageLoan::with('user', 'loanProject.property')->latest()->limit(4)->get()
                ->concat(PropertyLoan::with('user', 'loanProject.property')->latest()->limit(4)->get())
                ->sortByDesc('created_at')->take(6),
        ]);
    }
}
