<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MortgagePayment extends Model
{
    use HasUuid, Audited;

    protected $table = 'mortgage_payments';

    protected $fillable = ['mortgage_loan_id', 'amount', 'payment_date', 'period', 'method', 'reference', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(MortgageLoan::class, 'mortgage_loan_id');
    }
}
