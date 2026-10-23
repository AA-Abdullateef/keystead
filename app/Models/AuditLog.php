<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/** Append-only. The database also rejects UPDATE/DELETE (triggers), so this guard is belt and braces. */
class AuditLog extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'old_values' => 'array',
            'new_values' => 'array',
            'context' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Audit log entries are immutable.'));
        static::deleting(fn () => throw new \LogicException('Audit log entries cannot be deleted.'));
    }
}
