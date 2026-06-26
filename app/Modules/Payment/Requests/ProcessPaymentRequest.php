<?php

namespace App\Modules\Payment\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')
                    ->where(
                        fn (Builder $query) => $query->where(
                            'is_active',
                            true
                        )
                    ),
            ],

            /*
             * When omitted, the service pays the full remaining amount.
             */
            'amount' => [
                'nullable',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'idempotency_key' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('payments', 'idempotency_key'),
            ],

            'gateway_payload' => [
                'sometimes',
                'array',
            ],

            /*
             * Simulation field inside gateway_payload.
             */
            'gateway_payload.simulate_success' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}