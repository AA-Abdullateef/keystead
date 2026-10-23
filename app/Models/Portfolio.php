<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    use HasUuid;

    protected $fillable = ['user_id', 'total_invested', 'total_earnings', 'total_withdrawn'];

    protected function casts(): array
    {
        return [
            'total_invested' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
