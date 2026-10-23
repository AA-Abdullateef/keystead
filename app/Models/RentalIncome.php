<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalIncome extends Model
{
    use HasFactory, HasUuid, Audited;

    protected $fillable = ['investment_project_id', 'amount', 'month', 'year', 'recorded_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'month' => 'integer', 'year' => 'integer'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(InvestmentProject::class, 'investment_project_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
