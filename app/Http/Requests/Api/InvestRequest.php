<?php

namespace App\Http\Requests\Api;

class InvestRequest extends ApiRequest
{
    protected array $moneyFields = ['amount'];

    public function rules(): array
    {
        return [
            'investment_project_id' => ['required', 'uuid', 'exists:investment_projects,id'],
            'amount' => $this->money(),
        ];
    }
}
