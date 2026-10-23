<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Borrowers act only on their own loans. A loan that belongs to someone else answers 404, not
 * 403, so identifiers cannot be probed. State rules (is it repayable? still pending?) are
 * enforced by LoanService and answer 422.
 */
class LoanPolicy
{
    public function view(User $user, Loan $loan): Response
    {
        return $this->owner($user, $loan);
    }

    public function pay(User $user, Loan $loan): Response
    {
        return $this->owner($user, $loan);
    }

    public function cancel(User $user, Loan $loan): Response
    {
        return $this->owner($user, $loan);
    }

    private function owner(User $user, Loan $loan): Response
    {
        return $loan->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
