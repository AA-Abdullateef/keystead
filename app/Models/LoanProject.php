<?php

namespace App\Models;

use App\Enums\LoanType;
use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanProject extends Model
{
    use HasFactory, HasUuid, SoftDeletes, Audited;

    protected $fillable = [
        'property_id', 'loan_type', 'property_value', 'min_initial_payment', 'interest_rate',
        'duration_months', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'loan_type' => LoanType::class,
            'property_value' => 'decimal:2',
            'min_initial_payment' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'duration_months' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function mortgageLoans(): HasMany
    {
        return $this->hasMany(MortgageLoan::class);
    }

    public function propertyLoans(): HasMany
    {
        return $this->hasMany(PropertyLoan::class);
    }

    public function loans(): HasMany
    {
        return $this->loan_type === LoanType::Property ? $this->propertyLoans() : $this->mortgageLoans();
    }
}
