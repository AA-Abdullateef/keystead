<?php

namespace App\Models;

use App\Enums\Direction;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id', 'user_seq', 'dedupe_key', 'amount', 'direction', 'type', 'reference_type', 'reference_id',
        'status', 'balance_after', 'description',
    ];

    /** Ledger rows are immutable except for `status` (pending → completed/rejected). Triggers enforce it in the DB too. */
    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Ledger rows cannot be deleted.'));
        static::updating(function (self $t) {
            $illegal = array_diff(array_keys($t->getDirty()), ['status', 'updated_at']);
            if ($illegal !== []) {
                throw new \LogicException('Ledger rows are immutable; attempted to change: ' . implode(', ', $illegal));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'direction' => Direction::class,
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
        ];
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
