<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
                'max:150',
            ],

            'name_en' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('products', 'code'),
            ],

            'quantity_in_stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'unit_price' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required' => 'The Arabic product name is required.',
            'name_en.required' => 'The English product name is required.',

            'code.required' => 'The product code is required.',
            'code.regex' => 'The product code may contain letters, numbers, dashes, and underscores only.',
            'code.unique' => 'This product code already exists.',

            'quantity_in_stock.required' => 'The stock quantity is required.',
            'quantity_in_stock.integer' => 'The stock quantity must be an integer.',
            'quantity_in_stock.min' => 'The stock quantity cannot be negative.',

            'unit_price.required' => 'The unit price is required.',
            'unit_price.numeric' => 'The unit price must be numeric.',
            'unit_price.min' => 'The unit price cannot be negative.',
            'unit_price.decimal' => 'The unit price can contain a maximum of two decimal places.',
        ];
    }
}