<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for the audit trail.
 *
 * Call it INSIDE the same DB transaction as the change it describes, so the record exists if and only if
 * the change was committed. The actor is resolved from the current request (session or Sanctum), or is
 * "system" for commands/seeders.
 */
class AuditLogger
{
    /** Never written to the trail, whatever the model says. */
    private const REDACT = [
        'password', 'remember_token', 'registration_token', 'password_reset_token', 'token', 'secret',
    ];

    public function log(string $event, ?Model $subject = null, ?array $old = null, ?array $new = null, ?string $description = null, ?User $actor = null): ?AuditLog
    {
        $actor ??= Auth::user();

        try {
            return AuditLog::create([
                'occurred_at' => now(),
                'actor_id' => $actor?->getKey(),
                'actor_label' => $actor?->name ?? 'system',
                'actor_role' => $actor ? $this->roleOf($actor) : null,
                'event' => $event,
                'subject_type' => $subject ? $this->subjectType($subject) : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'old_values' => $old ? $this->clean($old) : null,
                'new_values' => $new ? $this->clean($new) : null,
                'context' => $this->context(),
            ]);
        } catch (\Throwable $e) {
            // An audit failure must be loud: financial writes in the same transaction should not commit unaudited.
            Log::critical('Audit write failed', ['event' => $event, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /** Diff of the attributes that actually changed (used by the model observer). */
    public function diff(Model $model): array
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);
        $old = array_intersect_key($model->getRawOriginal(), $changes);

        return [$old, $changes];
    }

    private function subjectType(Model $m): string
    {
        return \Illuminate\Database\Eloquent\Relations\Relation::getMorphAlias($m::class);
    }

    private function roleOf(User $u): ?string
    {
        return $u->roles()->orderBy('name')->value('name');
    }

    private function clean(array $values): array
    {
        foreach ($values as $k => $v) {
            if (in_array($k, self::REDACT, true)) {
                $values[$k] = '[redacted]';
            } elseif ($v instanceof \BackedEnum) {
                $values[$k] = $v->value;
            } elseif ($v instanceof \DateTimeInterface) {
                $values[$k] = $v->format('c');
            }
        }

        return $values;
    }

    private function context(): ?array
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return ['channel' => 'console'];
        }
        $r = request();

        return array_filter([
            'ip' => $r->ip(),
            'user_agent' => substr((string) $r->userAgent(), 0, 255),
            'route' => $r->route()?->getName() ?? $r->path(),
            'method' => $r->method(),
        ]);
    }
}
