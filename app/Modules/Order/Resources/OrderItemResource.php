<?php

namespace App\Modules\Order\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product' => $this->whenLoaded(
                'product',
                fn () => $this->product
                    ? [
                        'id' => $this->product->id,
                        'name_ar' => $this->product->name_ar,
                        'name_en' => $this->product->name_en,
                        'code' => $this->product->code,
                    ]
                    : null
            ),

            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'line_total' => (float) $this->line_total,
        ];
    }
}