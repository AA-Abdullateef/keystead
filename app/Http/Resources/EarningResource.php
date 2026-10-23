<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Earning */
class EarningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'month' => $this->month,
            'year' => $this->year,
            'reference' => ['type' => $this->reference_type, 'id' => $this->reference_id],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
