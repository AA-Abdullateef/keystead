<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Earning extends Model
{
    use HasUuid;

    protected $fillable = ['user_id', 'amount', 'type', 'reference_type', 'reference_id', 'month', 'year'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'month' => 'integer', 'year' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
