<?php

namespace App\Http\Requests\Admin;

use App\Models\State;

class UpdateUserProfileRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
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
        return [function ($v) {
            if ($this->filled('state_id') && $this->filled('country_id')
                && State::whereKey($this->state_id)->where('country_id', '!=', $this->country_id)->exists()) {
                $v->errors()->add('state_id', 'The selected state does not belong to the selected country.');
            }
        }];
    }
}
