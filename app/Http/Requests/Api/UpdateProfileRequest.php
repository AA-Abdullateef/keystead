<?php

namespace App\Http\Requests\Api;

use App\Models\State;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'dob' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{6,32}$/'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'state_id' => ['nullable', 'uuid', 'exists:states,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $stateId = $this->input('state_id');
            if (! $stateId || $validator->errors()->isNotEmpty()) {
                return;
            }

            $state = State::find($stateId);
            $countryId = $this->input('country_id');

            if ($countryId && $state && $state->country_id !== $countryId) {
                $validator->errors()->add('state_id', 'The selected state does not belong to the selected country.');
            } elseif (! $countryId && $state) {
                $this->merge(['country_id' => $state->country_id]);
            }
        }];
    }
}
