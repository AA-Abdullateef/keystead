<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public function __construct(private readonly TransactionService $transactions, private readonly WalletService $wallets)
    {
    }

    public function withdrawals(Request $request)
    {
        $f = $request->validate(['status' => ['nullable', Rule::enum(TransactionStatus::class)]]);

        return view('admin.withdrawals', ['withdrawals' => $this->transactions->withdrawals($f['status'] ?? null)]);
    }

    public function approveWithdrawal(string $id): RedirectResponse
    {
        $this->wallets->approveWithdrawal($id);

        return back()->with('success', 'Withdrawal approved.');
    }

    public function rejectWithdrawal(string $id): RedirectResponse
    {
        $this->wallets->rejectWithdrawal($id);

        return back()->with('success', 'Withdrawal rejected and the funds returned to the wallet.');
    }

    public function transactions(Request $request)
    {
        $f = $request->validate([
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'status' => ['nullable', Rule::enum(TransactionStatus::class)],
            'user_id' => ['nullable', 'uuid'],
        ]);

        return view('admin.transactions', [
            'transactions' => $this->transactions->paginate($f['type'] ?? null, $f['user_id'] ?? null, $f['status'] ?? null),
            'users' => $this->transactions->users(),
            'types' => TransactionType::cases(),
        ]);
    }

    public function showTransaction(string $id)
    {
        return view('admin.show_transactions', ['transaction' => $this->transactions->find($id)]);
    }
}
