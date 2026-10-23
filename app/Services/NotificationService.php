<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\Financial as F;
use App\Notifications\Security as S;
use App\Support\Money;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The only place that decides WHO is told WHAT. Services and controllers call one named method here
 * and never touch Mail/Notification directly.
 *
 *  - Every send is deferred with DB::afterCommit(): nobody is e-mailed about something that rolled back.
 *  - A mail failure is reported but never fails the business operation.
 *  - Nothing is queued: financial (FinancialNotification) and security (SecurityNotification) mail is
 *    sent synchronously, right after the commit.
 */
class NotificationService
{
    /* ------------------------------------------------------------ financial */

    public function investmentConfirmed(Investment $inv, string $amount): void
    {
        $inv->loadMissing('project.property');
        $this->send($inv->user_id, fn () => new F\InvestmentConfirmed(
            $inv->project->property->name, Money::fiat($amount), Money::fiat($inv->total_invested), (string) $inv->ownership_percent));
    }

    public function investmentReversed(string $userId, string $property, string $amount): void
    {
        $this->send($userId, fn () => new F\InvestmentReversed($property, Money::fiat($amount)));
    }

    public function withdrawalRequested(Transaction $t): void
    {
        $this->send($t->user_id, fn () => new F\WithdrawalRequested(Money::fiat($t->amount)));
    }

    public function withdrawalApproved(Transaction $t): void
    {
        $this->send($t->user_id, fn () => new F\WithdrawalApproved(Money::fiat($t->amount)));
    }

    public function withdrawalRejected(Transaction $t): void
    {
        $this->send($t->user_id, fn () => new F\WithdrawalRejected(Money::fiat($t->amount)));
    }

    public function profitCredited(string $userId, string $property, string $amount, int $month, int $year): void
    {
        $this->send($userId, fn () => new F\ProfitCredited($property, Money::fiat($amount), $month, $year));
    }

    public function loanSubmitted(Loan $l): void
    {
        $this->send($l->user_id, fn () => new F\LoanApplicationSubmitted($l->loanType()->value, $this->propertyOf($l),
            Money::fiat($l->initial_payment), Money::fiat($l->monthly_payment), Money::fiat($l->total_payable), (int) $l->duration_months));
    }

    public function loanApproved(Loan $l): void
    {
        $this->send($l->user_id, fn () => new F\LoanApproved($l->loanType()->value, $this->propertyOf($l), Money::fiat($l->monthly_payment), (int) $l->duration_months));
    }

    public function loanRejected(Loan $l): void
    {
        $this->send($l->user_id, fn () => new F\LoanRejected($l->loanType()->value, $this->propertyOf($l), Money::fiat($l->initial_payment)));
    }

    public function loanCancelled(Loan $l): void
    {
        $this->send($l->user_id, fn () => new F\LoanCancelled($l->loanType()->value, $this->propertyOf($l), Money::fiat($l->initial_payment)));
    }

    public function loanPaid(Loan $l, string $amount): void
    {
        $this->send($l->user_id, fn () => new F\LoanPaymentReceived($l->loanType()->value, $this->propertyOf($l), Money::fiat($amount),
            Money::fiat($l->balance_remaining), Money::cmp($l->balance_remaining, '0') <= 0));
    }

    public function walletFunded(Transaction $t): void
    {
        $this->send($t->user_id, fn () => new F\WalletFunded(Money::fiat($t->amount), Money::fiat($t->balance_after)));
    }

    /* ------------------------------------------------------------- security */

    public function verifyRegistration(User $u, string $url, int $hours): void
    {
        $this->send($u->id, fn () => new S\VerifyRegistration($url, $hours));
    }

    public function emailVerified(User $u): void
    {
        $this->send($u->id, fn () => new S\EmailVerified);
    }

    public function passwordResetLink(User $u, string $url, int $minutes): void
    {
        $this->send($u->id, fn () => new S\ResetPasswordLink($url, $minutes));
    }

    public function passwordChanged(User $u, string $via, ?string $ip): void
    {
        $this->send($u->id, fn () => new S\PasswordChanged($via, (string) $ip));
    }

    public function accountStatusChanged(User $u, string $status): void
    {
        $this->send($u->id, fn () => new S\AccountStatusChanged($status));
    }

    /* ------------------------------------------------------------- internals */

    private function propertyOf(Loan $l): string
    {
        $l->loadMissing('loanProject.property');

        return (string) ($l->loanProject?->property?->name ?? 'the property');
    }

    /** @param \Closure():Notification $make built lazily, after the commit */
    private function send(string $userId, \Closure $make): void
    {
        DB::afterCommit(function () use ($userId, $make) {
            try {
                $user = User::find($userId);
                if ($user) {
                    $user->notify($make());
                }
            } catch (\Throwable $e) {
                report($e);
                Log::warning('Notification failed', ['user' => $userId, 'error' => $e->getMessage()]);
            }
        });
    }
}
