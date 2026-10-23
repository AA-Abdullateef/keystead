<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LoanProject */
class LoanProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_type' => $this->loan_type->value,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'property_value' => $this->property_value,
            'min_initial_payment' => $this->min_initial_payment,
            'interest_rate' => $this->interest_rate,
            'duration_months' => $this->duration_months,
        ];
    }
}
