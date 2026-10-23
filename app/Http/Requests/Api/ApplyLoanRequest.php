<?php

namespace App\Http\Requests\Api;

class ApplyLoanRequest extends ApiRequest
{
    protected array $moneyFields = ['initial_payment'];

    /**
     * Only the product and the initial payment come from the client. Property value, rate and
     * duration are taken from the loan project server-side, so extra fields are ignored.
     */
    public function rules(): array
    {
        return [
            'loan_project_id' => ['required', 'uuid', 'exists:loan_projects,id'],
            'initial_payment' => $this->money(),
        ];
    }
}
