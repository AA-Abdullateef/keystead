<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class LoanProductRequest extends AdminRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'property_id' => [$creating ? 'required' : 'sometimes', 'uuid', 'exists:properties,id'],
            'loan_type' => [$creating ? 'required' : 'prohibited', Rule::in(['mortgage', 'property'])],
            'property_value' => $this->money(),
            'min_initial_payment' => $this->money(),
            'interest_rate' => ['required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'duration_months' => ['required', 'integer', 'between:1,480'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function after(): array
    {
        return [function ($v) {
            if (! $v->errors()->any() && bccomp((string) $this->min_initial_payment, (string) $this->property_value, 2) >= 0) {
                $v->errors()->add('min_initial_payment', 'The initial payment must be less than the property value.');
            }
        }];
    }
}
