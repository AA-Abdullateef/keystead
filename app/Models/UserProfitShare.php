<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfitShare extends Model
{
    use HasUuid;

    protected $fillable = ['profit_distribution_id', 'user_id', 'amount', 'share_percent'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'share_percent' => 'decimal:6'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profitDistribution(): BelongsTo
    {
        return $this->belongsTo(ProfitDistribution::class, 'profit_distribution_id');
    }
}
