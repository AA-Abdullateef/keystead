<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Base for staff-console form requests. Route middleware already enforces the role. */
abstract class AdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Positive decimal with at most two places, kept as a string. */
    protected function money(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999.99'];
    }

    protected function percent(): array
    {
        return ['required', 'numeric', 'decimal:0,2', 'between:0,100'];
    }

    /** Every amount stays a string end to end. */
    public function amount(string $key = 'amount'): string
    {
        return (string) $this->validated($key);
    }
}
