<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
