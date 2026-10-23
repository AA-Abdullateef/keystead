<?php

namespace App\Http\Requests\Admin;

class IncreaseInvestmentRequest extends AdminRequest
{
    public function rules(): array
    {
        return ['total_invested' => $this->money()];
    }
}
