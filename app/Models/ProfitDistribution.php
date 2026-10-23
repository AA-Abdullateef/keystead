<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitDistribution extends Model
{
    use HasUuid, Audited;

    protected $fillable = [
        'investment_project_id', 'month', 'year', 'total_income', 'total_expenses', 'management_fee_amount',
        'net_profit', 'distributable_profit', 'investor_total', 'company_share', 'status', 'distributed_at',
        'distributed_by',
    ];

    protected function casts(): array
    {
        return [
            'total_income' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'management_fee_amount' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'distributable_profit' => 'decimal:2',
            'investor_total' => 'decimal:2',
            'company_share' => 'decimal:2',
            'month' => 'integer',
            'year' => 'integer',
            'distributed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(InvestmentProject::class, 'investment_project_id');
    }

    /** Alias kept for existing views. */
    public function investmentProject(): BelongsTo
    {
        return $this->project();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    public function userProfitShares(): HasMany
    {
        return $this->hasMany(UserProfitShare::class, 'profit_distribution_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class, 'reference_id')->where('reference_type', 'profit_distribution');
    }
}
