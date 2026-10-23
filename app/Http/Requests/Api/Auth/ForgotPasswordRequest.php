<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\ApiRequest;

class ForgotPasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        // No `exists:` rule on purpose: it would let anyone enumerate registered e-mail addresses.
        return ['email' => ['required', 'email']];
    }
}
