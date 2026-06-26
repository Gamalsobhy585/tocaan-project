<?php

namespace App\Modules\Payment\Requests;

use App\Enums\PaymentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => [
                'nullable',
                'integer',
                'exists:orders,id',
            ],

            'status' => [
                'nullable',
                'integer',
                Rule::in(PaymentStatusEnum::values()),
            ],

            'payment_method_id' => [
                'nullable',
                'integer',
                'exists:payment_methods,id',
            ],

            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}