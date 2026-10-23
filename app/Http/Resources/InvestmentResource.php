<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Investment */
class InvestmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->investment_project_id,
            'total_invested' => $this->total_invested,
            'ownership_percent' => $this->ownership_percent,
            'status' => $this->status,
            'project' => $this->whenLoaded('project', fn () => [
                'status' => $this->project->status->value,
                'property' => new PropertyResource($this->project->relationLoaded('property') ? $this->project->property : null),
                'target_amount' => $this->project->target_amount,
                'total_raised' => $this->project->total_raised,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
