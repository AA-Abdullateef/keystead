<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InvestmentProject */
class InvestmentProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mine = $this->relationLoaded('investments')
            ? $this->investments->firstWhere('user_id', $request->user()?->id)
            : null;

        return [
            'id' => $this->id,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'target_amount' => $this->target_amount,
            'total_raised' => $this->total_raised,
            'remaining_amount' => $this->remainingAmount(),
            'min_investment' => $this->min_investment,
            'expected_roi' => $this->expected_roi,
            'management_fee_percent' => $this->management_fee_percent,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status->value,
            'investors_count' => $this->whenLoaded('investments', fn () => $this->investments->count()),
            'my_investment' => $this->when($this->relationLoaded('investments'), fn () => $mine ? new InvestmentResource($mine) : null),
        ];
    }
}
