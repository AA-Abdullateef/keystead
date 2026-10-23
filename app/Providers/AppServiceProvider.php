<?php

namespace App\Providers;

use App\Models\Earning;
use App\Models\Investment;
use App\Models\InvestmentProject;
use App\Models\MortgageLoan;
use App\Models\ProfitDistribution;
use App\Models\PropertyLoan;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Policies\LoanPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Stable, readable values in transactions.reference_type / earnings.reference_type.
        Relation::enforceMorphMap([
            'wallet' => Wallet::class,
            'transaction' => Transaction::class,
            'investment' => Investment::class,
            'investment_project' => InvestmentProject::class,
            'profit_distribution' => ProfitDistribution::class,
            'mortgage_loan' => MortgageLoan::class,
            'property_loan' => PropertyLoan::class,
            'earning' => Earning::class,
            'user' => \App\Models\User::class,
            'property' => \App\Models\Property::class,
            'loan_project' => \App\Models\LoanProject::class,
            'rental_income' => \App\Models\RentalIncome::class,
            'expense' => \App\Models\Expense::class,
            'user_profile' => \App\Models\UserProfile::class,
            'mortgage_payment' => \App\Models\MortgagePayment::class,
            'property_loan_payment' => \App\Models\PropertyLoanPayment::class,
            'audit_log' => \App\Models\AuditLog::class,
        ]);

        Gate::policy(MortgageLoan::class, LoanPolicy::class);
        Gate::policy(PropertyLoan::class, LoanPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);

        Paginator::defaultView('pagination.keystead');

        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('auth', fn (Request $r) => Limit::perMinute(10)->by(strtolower((string) $r->input('email')) . '|' . $r->ip()));
        RateLimiter::for('money', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?: $r->ip()));
    }
}
