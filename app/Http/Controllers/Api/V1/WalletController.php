<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListTransactionsRequest;
use App\Http\Requests\Api\PaginationRequest;
use App\Http\Requests\Api\WithdrawalRequest;
use App\Http\Resources\EarningResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Models\Earning;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly TransactionService $transactions,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        return $this->success('Wallet.', new WalletResource($this->wallets->getWallet($request->user()->id)));
    }

    public function transactions(ListTransactionsRequest $request): JsonResponse
    {
        $page = $this->transactions->paginate(
            $request->validated('type'), $request->user()->id, $request->validated('status'),
            (int) ($request->validated('per_page') ?? 15),
        );

        return $this->paginated('Transactions.', $page, TransactionResource::class);
    }

    public function transaction(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        return $this->success('Transaction.', new TransactionResource($transaction));
    }

    public function withdrawals(PaginationRequest $request): JsonResponse
    {
        $page = $this->transactions->withdrawals(null, $request->user()->id, $request->perPage());

        return $this->paginated('Withdrawals.', $page, TransactionResource::class);
    }

    public function requestWithdrawal(WithdrawalRequest $request): JsonResponse
    {
        $txn = $this->wallets->requestWithdrawal($request->user()->id, $request->validated('amount'));

        return $this->created('Withdrawal request submitted and awaiting approval.', new TransactionResource($txn));
    }

    /** Only the caller's own earnings (the earlier version returned every user's). */
    public function earnings(PaginationRequest $request): JsonResponse
    {
        $page = Earning::where('user_id', $request->user()->id)->latest()->paginate($request->perPage());

        return $this->paginated('Earnings.', $page, EarningResource::class);
    }
}
