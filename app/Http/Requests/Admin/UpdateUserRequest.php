<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateUserRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ];
    }
}
