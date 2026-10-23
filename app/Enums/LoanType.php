<?php

namespace App\Enums;

use App\Models\MortgageLoan;
use App\Models\MortgagePayment;
use App\Models\PropertyLoan;
use App\Models\PropertyLoanPayment;

enum LoanType: string
{
    case Mortgage = 'mortgage';
    case Property = 'property';

    /** @return class-string<MortgageLoan|PropertyLoan> */
    public function model(): string
    {
        return match ($this) {
            self::Mortgage => MortgageLoan::class,
            self::Property => PropertyLoan::class,
        };
    }

    /** @return class-string<MortgagePayment|PropertyLoanPayment> */
    public function paymentModel(): string
    {
        return match ($this) {
            self::Mortgage => MortgagePayment::class,
            self::Property => PropertyLoanPayment::class,
        };
    }

    public function paymentForeignKey(): string
    {
        return match ($this) {
            self::Mortgage => 'mortgage_loan_id',
            self::Property => 'property_loan_id',
        };
    }

    /** Morph-map alias stored in transactions.reference_type. */
    public function referenceType(): string
    {
        return $this->value . '_loan';
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
