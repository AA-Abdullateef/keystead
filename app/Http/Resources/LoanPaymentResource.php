<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'period' => $this->period,
            'payment_date' => $this->payment_date?->toDateString(),
            'method' => $this->method,
            'reference' => $this->reference,
            'status' => $this->status,
        ];
    }
}
