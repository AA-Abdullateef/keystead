<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyLoanPayment extends Model
{
    use HasUuid, Audited;

    protected $table = 'property_loan_payments';

    protected $fillable = ['property_loan_id', 'amount', 'payment_date', 'period', 'method', 'reference', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(PropertyLoan::class, 'property_loan_id');
    }
}
