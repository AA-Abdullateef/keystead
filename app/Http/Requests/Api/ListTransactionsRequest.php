<?php

namespace App\Http\Requests\Api;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Validation\Rule;

class ListTransactionsRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'status' => ['nullable', Rule::enum(TransactionStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
