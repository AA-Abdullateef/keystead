<?php

namespace App\Http\Requests\Admin;

class PropertyRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:investment,loan'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'purchase_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'status' => ['required', 'in:available,active,completed'],
        ];
    }
}
