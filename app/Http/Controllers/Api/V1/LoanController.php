<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LoanType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApplyLoanRequest;
use App\Http\Requests\Api\LoanPaymentRequest;
use App\Http\Requests\Api\PaginationRequest;
use App\Http\Resources\LoanPaymentResource;
use App\Http\Resources\LoanProjectResource;
use App\Http\Resources\LoanResource;
use App\Models\LoanProject;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;

/**
 * One controller for both loan kinds: the {type} route segment (mortgage|property) is bound
 * to the LoanType enum, so an unknown type is a plain 404.
 */
class LoanController extends Controller
{
    public function __construct(private readonly LoanService $loans)
    {
    }

    public function products(LoanType $type, PaginationRequest $request): JsonResponse
    {
        $page = LoanProject::with('property')->where('loan_type', $type)->where('status', 'active')->latest()->paginate($request->perPage());

        return $this->paginated(ucfirst($type->value) . ' loan products.', $page, LoanProjectResource::class);
    }

    public function apply(LoanType $type, ApplyLoanRequest $request): JsonResponse
    {
        $loan = $this->loans->apply($type, $request->validated('loan_project_id'), $request->validated('initial_payment'), $request->user()->id);

        return $this->created($type->label() . ' loan application submitted.', new LoanResource($loan));
    }

    public function index(LoanType $type, PaginationRequest $request): JsonResponse
    {
        $page = $type->model()::with('loanProject.property')->where('user_id', $request->user()->id)->latest()->paginate($request->perPage());

        return $this->paginated('Your ' . $type->value . ' loans.', $page, LoanResource::class);
    }

    public function show(LoanType $type, string $id): JsonResponse
    {
        $loan = $type->model()::with(['loanProject.property', 'payments'])->findOrFail($id);
        $this->authorize('view', $loan);

        return $this->success(ucfirst($type->value) . ' loan.', new LoanResource($loan));
    }

    public function pay(LoanType $type, string $id, LoanPaymentRequest $request): JsonResponse
    {
        $loan = $type->model()::findOrFail($id);
        $this->authorize('pay', $loan);

        $payment = $this->loans->pay($type, $id, $request->user()->id, $request->validated('amount'));

        return $this->created('Installment paid.', new LoanPaymentResource($payment));
    }

    public function cancel(LoanType $type, string $id): JsonResponse
    {
        $loan = $type->model()::findOrFail($id);
        $this->authorize('cancel', $loan);

        $loan = $this->loans->cancel($type, $id, request()->user()->id);

        return $this->success('Application cancelled and the initial payment refunded.', new LoanResource($loan));
    }
}
