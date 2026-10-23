<?php

namespace App\Http\Requests\Api;

class LoanPaymentRequest extends ApiRequest
{
    protected array $moneyFields = ['amount'];

    public function rules(): array
    {
        // Optional: when present it must equal the installment due (checked in LoanService).
        return ['amount' => ['nullable', 'numeric', 'decimal:0,2', 'gt:0']];
    }
}
