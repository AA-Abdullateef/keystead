<?php

namespace Tests\Feature\Services;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Models\MortgageLoan;
use App\Services\LoanService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsFinance;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase, BuildsFinance;

    private LoanService $loans;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->loans = app(LoanService::class);
    }

    /** Product: value 120 000, initial 20 000, 10 % flat, 12 months → loan 100 000, total 110 000, monthly 9 166.67. */
    private function applied(string $funds = '50000.00', LoanType $type = LoanType::Mortgage)
    {
        $user = $this->investor($funds);
        $product = $this->loanProject($type);
        $loan = $this->loans->apply($type, $product->id, '20000.00', $user->id);

        return [$user, $product, $loan];
    }

    public function test_application_computes_terms_and_takes_the_initial_payment(): void
    {
        [$user, , $loan] = $this->applied();

        $this->assertSame(LoanStatus::Pending, $loan->status);
        $this->assertSame('100000.00', $loan->loan_amount);
        $this->assertSame('110000.00', $loan->total_payable);
        $this->assertSame('9166.67', $loan->monthly_payment);
        $this->assertSame('110000.00', $loan->balance_remaining);
        $this->assertSame('30000.00', $this->balance($user));
    }

    public function test_initial_payment_must_equal_the_product_minimum(): void
    {
        $user = $this->investor('50000.00');
        $product = $this->loanProject();

        foreach (['19999.99', '20000.01'] as $bad) {
            try {
                $this->loans->apply(LoanType::Mortgage, $product->id, $bad, $user->id);
                $this->fail("accepted $bad");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('initial_payment', $e->errors());
            }
        }
        $this->assertSame('50000.00', $this->balance($user));
    }

    public function test_product_type_must_match_the_route_type(): void
    {
        $user = $this->investor('50000.00');
        $product = $this->loanProject(LoanType::Property);

        $this->expectException(ValidationException::class);
        $this->loans->apply(LoanType::Mortgage, $product->id, '20000.00', $user->id);
    }

    public function test_insufficient_funds_leaves_no_loan_behind(): void
    {
        $user = $this->investor('100.00');
        $product = $this->loanProject();

        try {
            $this->loans->apply(LoanType::Mortgage, $product->id, '20000.00', $user->id);
            $this->fail('accepted');
        } catch (ValidationException) {
        }
        $this->assertSame(0, MortgageLoan::count());
        $this->assertSame('100.00', $this->balance($user));
    }

    public function test_a_second_open_application_for_the_same_product_is_refused(): void
    {
        [$user, $product] = $this->applied();

        $this->expectException(ValidationException::class);
        $this->loans->apply(LoanType::Mortgage, $product->id, '20000.00', $user->id);
    }

    public function test_suspended_user_cannot_apply(): void
    {
        $user = $this->investor('50000.00');
        $user->forceFill(['status' => 'suspended'])->save();

        $this->expectException(ValidationException::class);
        $this->loans->apply(LoanType::Mortgage, $this->loanProject()->id, '20000.00', $user->id);
    }

    public function test_approval_activates_the_loan_and_keeps_the_initial_payment(): void
    {
        [$user, , $loan] = $this->applied();
        $admin = $this->admin();

        $approved = $this->loans->approve(LoanType::Mortgage, $loan->id, $admin->id);

        $this->assertSame(LoanStatus::Active, $approved->status);
        $this->assertSame($admin->id, $approved->approved_by);
        $this->assertNotNull($approved->start_date);
        $this->assertSame('30000.00', $this->balance($user));
    }

    public function test_rejection_and_cancellation_refund_exactly_once(): void
    {
        [$u1, , $l1] = $this->applied();
        $this->loans->reject(LoanType::Mortgage, $l1->id);
        $this->assertSame('50000.00', $this->balance($u1));

        [$u2, , $l2] = $this->applied();
        $this->loans->cancel(LoanType::Mortgage, $l2->id, $u2->id);
        $this->assertSame('50000.00', $this->balance($u2));

        foreach ([[$l1, fn ($l) => $this->loans->reject(LoanType::Mortgage, $l->id)],
                  [$l2, fn ($l) => $this->loans->cancel(LoanType::Mortgage, $l->id, $l->user_id)]] as [$loan, $act]) {
            try {
                $act($loan);
                $this->fail('second decision accepted');
            } catch (ValidationException) {
            }
        }
        $this->assertSame('50000.00', $this->balance($u1));
        $this->assertSame('50000.00', $this->balance($u2));
    }

    public function test_invalid_transitions_are_refused(): void
    {
        [$user, , $loan] = $this->applied();
        $admin = $this->admin();
        $this->loans->approve(LoanType::Mortgage, $loan->id, $admin->id);

        foreach ([
            fn () => $this->loans->approve(LoanType::Mortgage, $loan->id, $admin->id),
            fn () => $this->loans->reject(LoanType::Mortgage, $loan->id),
            fn () => $this->loans->cancel(LoanType::Mortgage, $loan->id, $user->id),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('invalid transition accepted');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame('30000.00', $this->balance($user));
    }

    public function test_pending_loan_cannot_be_repaid(): void
    {
        [$user, , $loan] = $this->applied();

        $this->expectException(ValidationException::class);
        $this->loans->pay(LoanType::Mortgage, $loan->id, $user->id);
    }

    public function test_installment_is_debited_and_only_one_is_allowed_per_month(): void
    {
        [$user, , $loan] = $this->applied();
        $this->loans->approve(LoanType::Mortgage, $loan->id, $this->admin()->id);

        $this->loans->pay(LoanType::Mortgage, $loan->id, $user->id);

        $fresh = $loan->fresh();
        $this->assertSame('9166.67', $fresh->total_paid);
        $this->assertSame('100833.33', $fresh->balance_remaining);
        $this->assertSame('20833.33', $this->balance($user));

        try {
            $this->loans->pay(LoanType::Mortgage, $loan->id, $user->id);
            $this->fail('duplicate repayment accepted');
        } catch (ValidationException) {
        }
        $this->assertSame('20833.33', $this->balance($user));
        $this->assertSame(1, $fresh->payments()->count());
    }

    public function test_wrong_amount_is_refused(): void
    {
        [$user, , $loan] = $this->applied();
        $this->loans->approve(LoanType::Mortgage, $loan->id, $this->admin()->id);

        $this->expectException(ValidationException::class);
        $this->loans->pay(LoanType::Mortgage, $loan->id, $user->id, '100.00');
    }

    public function test_short_wallet_fails_cleanly_without_a_payment_row(): void
    {
        [$user, , $loan] = $this->applied('20000.00'); // 0 left after the initial payment
        $this->loans->approve(LoanType::Mortgage, $loan->id, $this->admin()->id);

        try {
            $this->loans->pay(LoanType::Mortgage, $loan->id, $user->id);
            $this->fail('accepted');
        } catch (ValidationException) {
        }
        $this->assertSame(0, $loan->fresh()->payments()->count());
        $this->assertSame('110000.00', $loan->fresh()->balance_remaining);
    }

    public function test_final_installment_clears_the_balance_exactly_and_completes_the_loan(): void
    {
        [$user, , $loan] = $this->applied('500000.00');
        $this->loans->approve(LoanType::Mortgage, $loan->id, $this->admin()->id);

        // 11 × 9166.67 = 100833.37 paid; remaining 9166.63 is the last installment.
        $loan->forceFill(['total_paid' => '100833.37', 'balance_remaining' => '9166.63'])->save();
        $payment = $this->loans->pay(LoanType::Mortgage, $loan->id, $user->id);

        $this->assertSame('9166.63', $payment->amount);
        $fresh = $loan->fresh();
        $this->assertSame('0.00', $fresh->balance_remaining);
        $this->assertSame(LoanStatus::Completed, $fresh->status);
    }

    public function test_someone_elses_loan_looks_like_it_does_not_exist(): void
    {
        [, , $loan] = $this->applied();
        $this->loans->approve(LoanType::Mortgage, $loan->id, $this->admin()->id);
        $stranger = $this->investor('50000.00');

        $this->expectException(ModelNotFoundException::class);
        $this->loans->pay(LoanType::Mortgage, $loan->id, $stranger->id);
    }

    public function test_overdue_refresh_flags_loans_behind_schedule_and_payment_recovers_them(): void
    {
        [$user, , $loan] = $this->applied();
        $this->loans->approve(LoanType::Mortgage, $loan->id, $this->admin()->id);
        $loan->forceFill(['start_date' => now()->subMonths(3)->toDateString()])->save();

        $this->assertSame(1, $this->loans->refreshOverdueLoans());
        $this->assertSame(LoanStatus::Overdue, $loan->fresh()->status);

        // Idempotent
        $this->assertSame(0, $this->loans->refreshOverdueLoans());

        $this->loans->pay(LoanType::Mortgage, $loan->id, $user->id);
        $this->assertSame(LoanStatus::Overdue, $loan->fresh()->status, 'still behind after one of three missed installments');
    }

    public function test_property_loans_use_their_own_tables(): void
    {
        [$user, , $loan] = $this->applied('50000.00', LoanType::Property);
        $this->loans->approve(LoanType::Property, $loan->id, $this->admin()->id);
        $this->loans->pay(LoanType::Property, $loan->id, $user->id);

        $this->assertSame(1, $loan->payments()->count());
        $this->assertSame(0, MortgageLoan::count());
    }
}
