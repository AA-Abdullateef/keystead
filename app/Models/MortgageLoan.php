<?php

namespace App\Models;

use App\Enums\LoanType;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MortgageLoan extends Loan
{
    protected $table = 'mortgage_loans';

    public function loanType(): LoanType
    {
        return LoanType::Mortgage;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MortgagePayment::class, 'mortgage_loan_id');
    }
}
