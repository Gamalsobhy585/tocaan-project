<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => [
                'required',
                'array',
                'min:1',
            ],

            'ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'At least one product ID is required.',
            'ids.array' => 'The product IDs must be sent as an array.',
            'ids.min' => 'At least one product ID is required.',
            'ids.*.distinct' => 'Duplicate product IDs are not allowed.',
            'ids.*.exists' => 'One or more selected products do not exist.',
        ];
    }
}