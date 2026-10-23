<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserProfitShare */
class ProfitShareResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $d = $this->profitDistribution;

        return [
            'id' => $this->id,
            'project_id' => $d->investment_project_id,
            'property_name' => $d->project?->property?->name,
            'month' => $d->month,
            'year' => $d->year,
            'net_profit' => $d->net_profit,
            'management_fee' => $d->management_fee_amount,
            'distributable_profit' => $d->distributable_profit,
            'my_share' => $this->amount,
            'share_percent' => $this->share_percent,
            'distributed_at' => $d->distributed_at?->toIso8601String(),
        ];
    }
}
