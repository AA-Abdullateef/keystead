<?php

namespace App\Http\Requests\Admin;

class LoginRequest extends AdminRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'email'], 'password' => ['required', 'string']];
    }
}
