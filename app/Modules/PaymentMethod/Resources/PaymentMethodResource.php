<?php

namespace App\Modules\PaymentMethod\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'code' => $this->code,
            'strategy_key' => $this->strategy_key,
            'is_active' => $this->is_active,
        ];
    }
}