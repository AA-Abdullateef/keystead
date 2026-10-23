<?php

namespace App\Enums;

/**
 * Loan lifecycle (mortgage and property loans share it).
 *
 *   pending ──► active ──► completed
 *      │          ▲ │
 *      │          │ └──► overdue ──► active (caught up) / completed
 *      ├──► rejected   (admin, initial payment refunded)
 *      └──► cancelled  (borrower, initial payment refunded)
 *
 * `defaulted` is reserved for a future collections workflow and is not reachable today.
 */
enum LoanStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Overdue = 'overdue';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Defaulted = 'defaulted';

    public function isRepayable(): bool
    {
        return $this === self::Active || $this === self::Overdue;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Rejected, self::Cancelled, self::Defaulted], true);
    }
}
