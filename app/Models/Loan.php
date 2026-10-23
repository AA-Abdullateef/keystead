<?php

namespace App\Models;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shared behaviour of MortgageLoan and PropertyLoan (identical shape, separate tables).
 * Monetary and status fields are written only by LoanService.
 */
abstract class Loan extends Model
{
    use HasFactory, HasUuid, SoftDeletes, Audited;

    protected $guarded = ['id'];

    abstract public function loanType(): LoanType;

    abstract public function payments(): HasMany;

    protected function casts(): array
    {
        return [
            'property_value' => 'decimal:2',
            'initial_payment' => 'decimal:2',
            'loan_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'total_payable' => 'decimal:2',
            'monthly_payment' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'balance_remaining' => 'decimal:2',
            'duration_months' => 'integer',
            'status' => LoanStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loanProject(): BelongsTo
    {
        return $this->belongsTo(LoanProject::class, 'loan_project_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
