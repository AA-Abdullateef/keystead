<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserProfile */
class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'dob' => $this->dob?->toDateString(),
            'phone' => $this->phone,
            'occupation' => $this->occupation,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->whenLoaded('country', fn () => $this->country ? ['id' => $this->country->id, 'name' => $this->country->name] : null),
            'state' => $this->whenLoaded('state', fn () => $this->state ? ['id' => $this->state->id, 'name' => $this->state->name] : null),
        ];
    }
}
