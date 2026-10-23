<?php

namespace App\Services;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Enums\TransactionType;
use App\Models\Loan;
use App\Models\LoanProject;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Mortgage and property loans (identical mechanics, separate tables; see LoanType).
 *
 * Terms (flat interest, as configured on the loan project):
 *   loan_amount     = property_value − initial_payment
 *   total_payable   = loan_amount × (1 + interest_rate / 100)
 *   monthly_payment = total_payable ÷ duration_months, rounded to the cent
 *   the final installment is whatever balance remains, so the loan always settles to exactly 0.00
 *
 * Money flow: the initial payment is taken from the wallet at application and held; it is
 * refunded on rejection/cancellation and kept once the loan is approved.
 */
class LoanService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly NotificationService $notify,
        private readonly AuditLogger $audit,
    ) {
    }

    public function apply(LoanType $type, string $loanProjectId, string $initialPayment, string $userId): Loan
    {
        return DB::transaction(function () use ($type, $loanProjectId, $initialPayment, $userId) {
            // Lock first: see the note in InvestmentService::invest() about the REPEATABLE READ snapshot.
            $project = LoanProject::whereKey($loanProjectId)->lockForUpdate()->firstOrFail()->load('property');

            $user = User::findOrFail($userId);
            if (! $user->isActive()) {
                throw ValidationException::withMessages(['user' => 'This account is suspended and cannot apply for loans.']);
            }

            if ($project->status !== 'active' || $project->loan_type !== $type) {
                throw ValidationException::withMessages(['loan_project_id' => "This is not an active {$type->value} loan product."]);
            }
            if ($project->property->type !== 'loan') {
                throw ValidationException::withMessages(['loan_project_id' => 'The selected property is not available for loans.']);
            }

            $initial = Money::fiat($initialPayment);
            if (! Money::sameFiat($initial, $project->min_initial_payment)) {
                throw ValidationException::withMessages([
                    'initial_payment' => 'The initial payment must be exactly ' . Money::display($project->min_initial_payment) . '.',
                ]);
            }

            $model = $type->model();
            $open = $model::where('user_id', $userId)->where('loan_project_id', $project->id)
                ->whereIn('status', [LoanStatus::Pending, LoanStatus::Active, LoanStatus::Overdue])->exists();
            if ($open) {
                throw ValidationException::withMessages(['loan_project_id' => 'You already have an open application or loan for this product.']);
            }

            $terms = $this->terms((string) $project->property_value, $initial, (string) $project->interest_rate, (int) $project->duration_months);

            $loan = $model::create([
                'user_id' => $userId,
                'loan_project_id' => $project->id,
                'property_value' => Money::fiat($project->property_value),
                'initial_payment' => $initial,
                'loan_amount' => $terms['loan_amount'],
                'interest_rate' => $project->interest_rate,
                'duration_months' => $project->duration_months,
                'total_payable' => $terms['total_payable'],
                'monthly_payment' => $terms['monthly_payment'],
                'total_paid' => '0.00',
                'balance_remaining' => $terms['total_payable'],
                'status' => LoanStatus::Pending,
            ]);

            // Throws (rolling back the loan row) if the wallet cannot cover it.
            $this->wallets->debit($userId, $initial, TransactionType::LoanInitialPayment, $type->referenceType(), $loan->id,
                $type->label() . ' loan initial payment', dedupeKey: 'li:' . $loan->id);

            $this->audit->log('loan.applied', $loan, null, ['initial_payment' => $initial, 'total_payable' => $loan->total_payable, 'monthly_payment' => $loan->monthly_payment], 'Loan application submitted; initial payment debited');
            $loan->load('loanProject.property');
            $this->notify->loanSubmitted($loan);

            return $loan;
        });
    }

    public function approve(LoanType $type, string $loanId, string $adminId): Loan
    {
        return DB::transaction(function () use ($type, $loanId, $adminId) {
            $loan = $this->lock($type, $loanId);
            $this->assertStatus($loan, LoanStatus::Pending, 'Only a pending loan can be approved.');

            $loan->update([
                'status' => LoanStatus::Active,
                'approved_by' => $adminId,
                'start_date' => today(),
                'end_date' => today()->addMonths($loan->duration_months),
            ]);

            $this->audit->log('loan.approved', $loan, ['status' => 'pending'], ['status' => 'active', 'approved_by' => $adminId], 'Loan approved');
            $this->notify->loanApproved($loan);

            return $loan;
        });
    }

    public function reject(LoanType $type, string $loanId): Loan
    {
        return $this->closePending($type, $loanId, LoanStatus::Rejected, null);
    }

    /** Borrower withdraws their own pending application. */
    public function cancel(LoanType $type, string $loanId, string $userId): Loan
    {
        return $this->closePending($type, $loanId, LoanStatus::Cancelled, $userId);
    }

    /**
     * Pay the next installment from the borrower's wallet.
     *
     * @param string|null $amount if given it must equal the amount due (monthly payment, or the remaining balance on the last installment)
     */
    public function pay(LoanType $type, string $loanId, string $userId, ?string $amount = null): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($type, $loanId, $userId, $amount) {
            $loan = $this->lock($type, $loanId);

            if ($loan->user_id !== $userId) {
                // same response as a missing record: do not reveal other people's loans
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel($type->model(), [$loanId]);
            }
            if (! $loan->status->isRepayable()) {
                throw ValidationException::withMessages(['loan' => 'This loan is not open for repayment.']);
            }

            $due = $this->amountDue($loan);
            if ($amount !== null && ! Money::sameFiat($amount, $due)) {
                throw ValidationException::withMessages(['amount' => 'The installment due is exactly ' . Money::display($due) . '.']);
            }

            $period = now()->format('Y-m');
            $payments = $type->paymentModel();
            $fk = $type->paymentForeignKey();

            if ($payments::where($fk, $loan->id)->where('period', $period)->exists()) {
                throw ValidationException::withMessages(['payment' => 'This month\'s installment has already been paid.']);
            }

            $payment = null;
            try {
                $payment = $payments::create([
                    $fk => $loan->id,
                    'amount' => $due,
                    'payment_date' => today(),
                    'period' => $period,
                    'method' => 'wallet',
                    'reference' => (string) Str::uuid(),
                    'status' => 'confirmed',
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['payment' => 'This month\'s installment has already been paid.']);
            }

            // Fails with 422 (and rolls the payment row back) when the wallet is short.
            $this->wallets->debit($userId, $due, TransactionType::LoanPayment, $type->referenceType(), $payment->id,
                $type->label() . ' loan installment', dedupeKey: 'lp:' . $payment->id);

            $loan->total_paid = Money::fiat(Money::add($loan->total_paid, $due));
            $loan->balance_remaining = Money::fiat(Money::sub($loan->balance_remaining, $due));

            if (Money::cmp($loan->balance_remaining, '0') <= 0) {
                $loan->balance_remaining = '0.00';
                $loan->status = LoanStatus::Completed;
            } elseif ($loan->status === LoanStatus::Overdue && ! $this->isBehind($loan)) {
                $loan->status = LoanStatus::Active;
            }
            $loan->save();

            $this->audit->log('loan.payment', $loan, null, ['period' => $period, 'amount' => $due, 'balance_remaining' => $loan->balance_remaining, 'status' => $loan->status->value], 'Installment paid from wallet');
            $this->notify->loanPaid($loan, $due);

            return $payment;
        });
    }

    /** Amount of the next installment: the monthly payment, or whatever is left if that is smaller. */
    public function amountDue(Loan $loan): string
    {
        return Money::cmp($loan->balance_remaining, $loan->monthly_payment) < 0
            ? Money::fiat($loan->balance_remaining)
            : Money::fiat($loan->monthly_payment);
    }

    /**
     * Mark active loans that have fallen behind schedule as overdue (run daily by the scheduler:
     * `php artisan loans:refresh-overdue`). Never invoked from a GET request.
     *
     * @return int number of loans newly flagged
     */
    public function refreshOverdueLoans(): int
    {
        $flagged = 0;

        foreach (LoanType::cases() as $type) {
            $type->model()::where('status', LoanStatus::Active)->whereNotNull('start_date')
                ->chunkById(200, function ($loans) use (&$flagged) {
                    foreach ($loans as $loan) {
                        if ($this->isBehind($loan)) {
                            $loan->update(['status' => LoanStatus::Overdue]);
                            $this->audit->log('loan.marked_overdue', $loan, ['status' => 'active'], ['status' => 'overdue'], 'Flagged overdue by the scheduler');
                            $flagged++;
                        }
                    }
                });
        }

        return $flagged;
    }

    /** Whole months since the start date × monthly payment (capped at the total) not yet paid. */
    public function isBehind(Loan $loan, ?Carbon $asOf = null): bool
    {
        if (! $loan->start_date) {
            return false;
        }

        $months = (int) $loan->start_date->diffInMonths($asOf ?? now());
        $expected = Money::mul((string) $months, $loan->monthly_payment);
        if (Money::cmp($expected, $loan->total_payable) > 0) {
            $expected = Money::of($loan->total_payable);
        }

        return Money::cmp($loan->total_paid, $expected) < 0 && Money::isPositive($loan->balance_remaining);
    }

    /** @return array{loan_amount: string, total_payable: string, monthly_payment: string} */
    public function terms(string $propertyValue, string $initialPayment, string $interestRate, int $months): array
    {
        $loan = Money::fiat(Money::sub($propertyValue, $initialPayment));

        if (! Money::isPositive($loan)) {
            throw ValidationException::withMessages(['initial_payment' => 'The initial payment cannot cover the whole property value.']);
        }

        $total = Money::fiat(Money::add($loan, Money::percent($loan, $interestRate, 2)));

        return [
            'loan_amount' => $loan,
            'total_payable' => $total,
            'monthly_payment' => Money::fiat(Money::div($total, (string) $months)),
        ];
    }

    private function closePending(LoanType $type, string $loanId, LoanStatus $to, ?string $ownerId): Loan
    {
        return DB::transaction(function () use ($type, $loanId, $to, $ownerId) {
            $loan = $this->lock($type, $loanId);

            if ($ownerId !== null && $loan->user_id !== $ownerId) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel($type->model(), [$loanId]);
            }
            $this->assertStatus($loan, LoanStatus::Pending, 'Only a pending application can be ' . $to->value . '.');

            $loan->update(['status' => $to]);

            $this->wallets->credit($loan->user_id, Money::fiat($loan->initial_payment), TransactionType::LoanRefund,
                $type->referenceType(), $loan->id, 'Initial payment refunded (' . $to->value . ')', 'lr:' . $loan->id);

            $this->audit->log('loan.' . $to->value, $loan, ['status' => 'pending'], ['status' => $to->value, 'refunded' => Money::fiat($loan->initial_payment)],
                'Application ' . $to->value . '; initial payment refunded');
            $to === LoanStatus::Rejected ? $this->notify->loanRejected($loan) : $this->notify->loanCancelled($loan);

            return $loan;
        });
    }

    private function lock(LoanType $type, string $loanId): Loan
    {
        return $type->model()::whereKey($loanId)->lockForUpdate()->firstOrFail();
    }

    private function assertStatus(Loan $loan, LoanStatus $expected, string $message): void
    {
        if ($loan->status !== $expected) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
