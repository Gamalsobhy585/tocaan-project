<?php

namespace App\Modules\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,

            'order' => $this->whenLoaded(
                'order',
                fn () => $this->order
                    ? [
                        'id' => $this->order->id,
                        'order_number' => $this->order->order_number,
                    ]
                    : null
            ),

            'payment_method' => $this->whenLoaded(
                'paymentMethod',
                fn () => [
                    'id' => $this->paymentMethod->id,
                    'name_ar' => $this->paymentMethod->name_ar,
                    'name_en' => $this->paymentMethod->name_en,
                    'code' => $this->paymentMethod->code,
                ]
            ),

            'currency' => $this->whenLoaded(
                'currency',
                fn () => [
                    'id' => $this->currency->id,
                    'name_ar' => $this->currency->name_ar,
                    'name_en' => $this->currency->name_en,
                    'code' => $this->currency->code,
                ]
            ),

            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],

            'amount' => (float) $this->amount,
            'transaction_reference' =>
                $this->transaction_reference,
            'gateway_response' => $this->gateway_response,
            'failure_reason' => $this->failure_reason,

            'processed_by' => $this->whenLoaded(
                'processor',
                fn () => $this->processor
                    ? [
                        'id' => $this->processor->id,
                        'name' => $this->processor->name,
                    ]
                    : null
            ),

            'processed_at' =>
                $this->processed_at?->toISOString(),

          

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}