<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;

/**
 * Generic create / update / delete audit with an attribute diff. Business-level events that carry
 * more meaning (loan.approved, profit.distributed …) are logged explicitly by the services.
 *
 * Models can set `$auditExclude` to hide noisy or sensitive attributes.
 */
trait Audited
{
    public static function bootAudited(): void
    {
        static::created(fn ($m) => $m->audit('created', null, $m->getAttributes()));
        static::updated(function ($m) {
            [$old, $new] = app(AuditLogger::class)->diff($m);
            $new = array_diff_key($new, array_flip($m->auditExcluded()));
            $old = array_diff_key($old, array_flip($m->auditExcluded()));
            if ($new !== []) {
                $m->audit('updated', $old, $new);
            }
        });
        static::deleted(fn ($m) => $m->audit('deleted', $m->getAttributes(), null));
    }

    public function auditExcluded(): array
    {
        return array_merge(['updated_at', 'created_at', 'remember_token', 'password_reset_token_sent_at', 'registration_token_sent_at'], $this->auditExclude ?? []);
    }

    protected function audit(string $verb, ?array $old, ?array $new): void
    {
        $skip = array_flip($this->auditExcluded());
        $old = $old ? array_diff_key($old, $skip) : null;
        $new = $new ? array_diff_key($new, $skip) : null;
        $alias = \Illuminate\Database\Eloquent\Relations\Relation::getMorphAlias(static::class);

        app(AuditLogger::class)->log("{$alias}.{$verb}", $this, $old, $new);
    }
}
