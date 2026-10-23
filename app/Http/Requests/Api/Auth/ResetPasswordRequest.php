<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\ApiRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
