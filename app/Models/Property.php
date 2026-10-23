<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, HasUuid, SoftDeletes, Audited;

    protected $fillable = [
        'name', 'type', 'description', 'location', 'purchase_price', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['purchase_price' => 'decimal:2'];
    }

    public function investmentProjects(): HasMany
    {
        return $this->hasMany(InvestmentProject::class);
    }

    public function loanProjects(): HasMany
    {
        return $this->hasMany(LoanProject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function investorInvestments(): HasManyThrough
    {
        return $this->hasManyThrough(Investment::class, InvestmentProject::class, 'property_id', 'investment_project_id');
    }

    public function rentalIncomes(): HasManyThrough
    {
        return $this->hasManyThrough(RentalIncome::class, InvestmentProject::class, 'property_id', 'investment_project_id');
    }

    public function expenses(): HasManyThrough
    {
        return $this->hasManyThrough(Expense::class, InvestmentProject::class, 'property_id', 'investment_project_id');
    }
}
