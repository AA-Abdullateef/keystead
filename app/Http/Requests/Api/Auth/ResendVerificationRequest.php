<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\ApiRequest;

class ResendVerificationRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'email']];
    }
}
