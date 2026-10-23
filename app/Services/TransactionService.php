<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Read-side access to the ledger. Writes go through WalletService. */
class TransactionService
{
    public function paginate(?string $type = null, ?string $userId = null, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        return Transaction::with('user')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function withdrawals(?string $status = null, ?string $userId = null, int $perPage = 20): LengthAwarePaginator
    {
        return Transaction::with('user')
            ->where('type', TransactionType::Withdrawal)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(string $id): Transaction
    {
        return Transaction::with('user')->findOrFail($id);
    }

    public function users()
    {
        return User::orderBy('name')->get(['id', 'name', 'email']);
    }
}
