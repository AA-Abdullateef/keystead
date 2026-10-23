<?php

namespace App\Models;

use App\Enums\LoanType;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyLoan extends Loan
{
    protected $table = 'property_loans';

    public function loanType(): LoanType
    {
        return LoanType::Property;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PropertyLoanPayment::class, 'property_loan_id');
    }
}
