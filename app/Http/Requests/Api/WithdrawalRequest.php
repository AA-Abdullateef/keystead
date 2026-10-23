<?php

namespace App\Http\Requests\Api;

class WithdrawalRequest extends ApiRequest
{
    protected array $moneyFields = ['amount'];

    public function rules(): array
    {
        return ['amount' => $this->money()];
    }
}
