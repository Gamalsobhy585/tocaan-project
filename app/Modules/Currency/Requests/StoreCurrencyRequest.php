<?php

namespace App\Modules\Currency\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(
                    trim((string) $this->input('code'))
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name_ar' => [
                'required',
                'string',
                'max:100',
            ],

            'name_en' => [
                'required',
                'string',
                'max:100',
            ],

            'code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::unique('currencies', 'code'),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required' => 'The Arabic currency name is required.',
            'name_en.required' => 'The English currency name is required.',

            'code.required' => 'The currency code is required.',
            'code.size' => 'The currency code must contain exactly 3 characters.',
            'code.regex' => 'The currency code must contain English letters only.',
            'code.unique' => 'This currency code already exists.',

            'is_active.boolean' => 'The active value must be true or false.',
        ];
    }
}