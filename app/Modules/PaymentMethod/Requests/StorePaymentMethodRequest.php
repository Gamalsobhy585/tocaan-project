<?php

namespace App\Modules\PaymentMethod\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower(
                trim((string) $this->input('code'))
            ),
            'strategy_key' => strtolower(
                trim((string) $this->input('strategy_key'))
            ),
        ]);
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
                'max:50',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('payment_methods', 'code'),
            ],

            'strategy_key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_-]+$/',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}