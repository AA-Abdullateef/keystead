<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\Money;

class InvestmentProject extends Model
{
    use HasFactory, HasUuid, SoftDeletes, Audited;

    protected $fillable = [
        'property_id', 'target_amount', 'min_investment', 'management_fee_percent', 'expected_roi',
        'start_date', 'end_date', 'status', 'total_raised', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'min_investment' => 'decimal:2',
            'management_fee_percent' => 'decimal:2',
            'expected_roi' => 'decimal:2',
            'total_raised' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProjectStatus::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(RentalIncome::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function profitDistributions(): HasMany
    {
        return $this->hasMany(ProfitDistribution::class);
    }

    public function remainingAmount(): string
    {
        return Money::fiat(Money::sub((string) $this->target_amount, (string) $this->total_raised));
    }
}
