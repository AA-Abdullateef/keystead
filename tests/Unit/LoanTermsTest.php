<?php

namespace Tests\Unit;

use App\Services\LoanService;
use App\Services\WalletService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoanTermsTest extends TestCase
{
    private function service(): LoanService
    {
        return app(LoanService::class);
    }

    public function test_flat_interest_terms(): void
    {
        $t = $this->service()->terms('120000.00', '20000.00', '10.00', 12);

        $this->assertSame('100000.00', $t['loan_amount']);
        $this->assertSame('110000.00', $t['total_payable']);
        $this->assertSame('9166.67', $t['monthly_payment']);
    }

    public function test_zero_interest(): void
    {
        $t = $this->service()->terms('50000.00', '5000.00', '0.00', 9);

        $this->assertSame('45000.00', $t['total_payable']);
        $this->assertSame('5000.00', $t['monthly_payment']);
    }

    public function test_initial_payment_covering_the_whole_value_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->service()->terms('1000.00', '1000.00', '5.00', 6);
    }
}
