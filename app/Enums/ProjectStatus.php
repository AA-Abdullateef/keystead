<?php

namespace App\Enums;

/**
 * Investment project lifecycle.
 *
 *   open ──► funded ──► distributing ──► closed
 *     └────────────────────────────────────► closed   (admin, only while no distribution exists)
 *
 * Only `open` projects accept new contributions. Income and expenses can be
 * recorded for any project that is not `closed`. A project becomes `funded`
 * automatically when contributions reach the target, and `distributing` after
 * its first profit distribution. `closed` is terminal.
 */
enum ProjectStatus: string
{
    case Open = 'open';
    case Funded = 'funded';
    case Distributing = 'distributing';
    case Closed = 'closed';

    public function acceptsInvestments(): bool
    {
        return $this === self::Open;
    }

    public function acceptsLedgerEntries(): bool
    {
        return $this !== self::Closed;
    }

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Open => [self::Funded, self::Closed],
            self::Funded => [self::Open, self::Distributing, self::Closed],
            self::Distributing => [self::Closed],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return $this === $next || in_array($next, $this->allowedNext(), true);
    }
}
