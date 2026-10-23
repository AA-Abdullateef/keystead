<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    use HasFactory, HasUuid, Audited;

    protected $fillable = ['user_id', 'investment_project_id', 'total_invested', 'ownership_percent', 'status'];

    protected function casts(): array
    {
        return [
            'total_invested' => 'decimal:2',
            'ownership_percent' => 'decimal:4',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
