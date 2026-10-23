<?php

namespace App\Http\Requests\Admin;

class DepositRequest extends AdminRequest
{
    public function rules(): array
    {
        return ['amount' => $this->money(), 'note' => ['nullable', 'string', 'max:255']];
    }
}
