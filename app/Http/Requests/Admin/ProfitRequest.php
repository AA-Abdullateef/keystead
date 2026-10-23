<?php

namespace App\Http\Requests\Admin;

class ProfitRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'investment_project_id' => ['required', 'uuid', 'exists:investment_projects,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ];
    }
}
