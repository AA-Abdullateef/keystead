<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'role' => ['required', Rule::in([User::ROLE_INVESTOR, User::ROLE_MANAGER, User::ROLE_ADMIN])],
        ];
    }
}
