<?php

namespace App\Http\Requests\Admin;

class InvestorRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'investment_project_id' => ['required', 'uuid', 'exists:investment_projects,id'],
            'amount' => $this->money(),
        ];
    }
}
