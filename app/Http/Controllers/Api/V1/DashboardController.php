<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvestmentResource;
use App\Http\Resources\LoanResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\WalletResource;
use App\Services\FinancialProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly FinancialProfileService $profiles)
    {
    }

    public function portfolio(Request $request): JsonResponse
    {
        $p = $this->profiles->forUser($request->user()->id, withLoans: false);

        return $this->success('Portfolio.', [
            'wallet' => new WalletResource($p['wallet']),
            'portfolio' => $p['portfolio'],
            'investments' => InvestmentResource::collection($p['investments']),
        ]);
    }

    public function financialProfile(Request $request): JsonResponse
    {
        $p = $this->profiles->forUser($request->user()->id);

        return $this->success('Financial profile.', [
            'user' => new UserResource($p['user']),
            'wallet' => new WalletResource($p['wallet']),
            'portfolio' => $p['portfolio'],
            'loans_summary' => $p['loans'],
            'investments' => InvestmentResource::collection($p['investments']),
            'mortgage_loans' => LoanResource::collection($p['mortgage_loans']),
            'property_loans' => LoanResource::collection($p['property_loans']),
            'recent_transactions' => TransactionResource::collection($p['transactions']),
        ]);
    }
}
