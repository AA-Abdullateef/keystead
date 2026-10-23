<?php

namespace App\Http\Requests\Admin;

/** Rental income and expense entries share their shape; `type` only applies to expenses. */
class LedgerEntryRequest extends AdminRequest
{
    public function rules(): array
    {
        $isExpense = $this->routeIs('admin.expenses.*');

        return [
            'investment_project_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'uuid', 'exists:investment_projects,id'],
            'month' => [$this->isMethod('post') ? 'required' : 'sometimes', 'integer', 'between:1,12'],
            'year' => [$this->isMethod('post') ? 'required' : 'sometimes', 'integer', 'between:2000,2100'],
            'amount' => $this->money(),
            'type' => [$isExpense ? 'required' : 'prohibited', 'string', 'max:100'],
            'note' => [$isExpense ? 'nullable' : 'prohibited', 'string', 'max:500'],
        ];
    }
}
