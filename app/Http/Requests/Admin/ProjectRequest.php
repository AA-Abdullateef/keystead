<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProjectStatus;
use Illuminate\Validation\Rule;

class ProjectRequest extends AdminRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'property_id' => [$creating ? 'required' : 'sometimes', 'uuid', 'exists:properties,id'],
            'target_amount' => $this->money(),
            'min_investment' => $this->money(),
            'management_fee_percent' => $this->percent(),
            'expected_roi' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,1000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => [$creating ? 'prohibited' : 'sometimes', Rule::enum(ProjectStatus::class)],
        ];
    }
}
