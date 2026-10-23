<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base for API form requests: always answer validation failures with the JSON envelope
 * (never a redirect), and normalise money inputs to strings so floats never reach services.
 */
abstract class ApiRequest extends FormRequest
{
    /** Fields holding a money amount; cast to string before validation. */
    protected array $moneyFields = [];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cast = [];
        foreach ($this->moneyFields as $field) {
            $value = $this->input($field);
            if (is_int($value) || is_float($value)) {
                $cast[$field] = rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');
            }
        }
        if ($cast) {
            $this->merge($cast);
        }
    }

    /** Money rule: positive decimal with at most two places. */
    protected function money(string ...$extra): array
    {
        return array_merge(['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999.99'], $extra);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
