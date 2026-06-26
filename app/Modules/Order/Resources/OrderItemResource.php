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
            'product_id' => $this->product_id,

            'product' => [
                'name_ar' => $this->product_name_ar,
                'name_en' => $this->product_name_en,
                'code' => $this->product_code,
            ],

            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'line_total' => (float) $this->line_total,
        ];
    }
}