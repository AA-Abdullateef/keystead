<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Transaction */
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'direction' => $this->direction->value,
            'amount' => $this->amount,
            'status' => $this->status->value,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'reference' => ['type' => $this->reference_type, 'id' => $this->reference_id],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
