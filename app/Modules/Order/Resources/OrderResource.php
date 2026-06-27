<?php

namespace App\Modules\Order\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,

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

            'total_amount' => (float) $this->total_amount,
            'notes' => $this->notes,

            'items' => OrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            'history' => OrderHistoryResource::collection(
                $this->whenLoaded('histories')
            ),

            'created_by' => $this->whenLoaded(
                'creator',
                fn () => $this->creator
                    ? [
                        'id' => $this->creator->id,
                        'name' => $this->creator->name,
                    ]
                    : null
            ),

            'updated_by' => $this->whenLoaded(
                'updater',
                fn () => $this->updater
                    ? [
                        'id' => $this->updater->id,
                        'name' => $this->updater->name,
                    ]
                    : null
            ),

            'cancelled_by' => $this->whenLoaded(
                'canceller',
                fn () => $this->canceller
                    ? [
                        'id' => $this->canceller->id,
                        'name' => $this->canceller->name,
                    ]
                    : null
            ),

                'cancelled_at' => $this->cancelled_at,
                'deleted_at' => $this->deleted_at,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
        ];
    }
}