<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\ApiRequest;

class VerifyEmailRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'email'], 'token' => ['required', 'string', 'size:64']];
    }
}
