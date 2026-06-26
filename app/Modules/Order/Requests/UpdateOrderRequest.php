<?php

namespace App\Modules\Order\Requests;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('currencies', 'id')
                    ->where(
                        fn (Builder $query) => $query->where(
                            'is_active',
                            true
                        )
                    ),
            ],

            'status' => [
                'sometimes',
                'required',
                'integer',
                Rule::in(OrderStatusEnum::updatableValues()),
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],

            'items' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required_with:items',
                'integer',
                'distinct',
                Rule::exists('products', 'id'),
            ],

            'items.*.quantity' => [
                'required_with:items',
                'integer',
                'min:1',
            ],
        ];
    }
}