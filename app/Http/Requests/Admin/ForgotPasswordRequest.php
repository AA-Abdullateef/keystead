<?php

namespace App\Http\Requests\Admin;

class ForgotPasswordRequest extends AdminRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'email']];
    }
}
