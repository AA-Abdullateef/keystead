<?php

namespace App\Http\Resources;

use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Works for MortgageLoan and PropertyLoan. @mixin \App\Models\Loan */
class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->loanType()->value,
            'status' => $this->status->value,
            'property' => $this->whenLoaded('loanProject', fn () => $this->loanProject?->relationLoaded('property')
                ? new PropertyResource($this->loanProject->property) : null),
            'loan_project_id' => $this->loan_project_id,
            'property_value' => $this->property_value,
            'initial_payment' => $this->initial_payment,
            'loan_amount' => $this->loan_amount,
            'interest_rate' => $this->interest_rate,
            'duration_months' => $this->duration_months,
            'total_payable' => $this->total_payable,
            'monthly_payment' => $this->monthly_payment,
            'total_paid' => $this->total_paid,
            'balance_remaining' => $this->balance_remaining,
            'next_installment' => $this->when($this->status->isRepayable(), fn () => app(LoanService::class)->amountDue($this->resource)),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'payments' => LoanPaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
