<?php

namespace App\Modules\Order\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'action' => [
                'value' => $this->action->value,
                'label' => $this->action->label(),
            ],

            'old_status' => $this->old_status
                ? [
                    'value' => $this->old_status->value,
                    'label' => $this->old_status->label(),
                ]
                : null,

            'new_status' => $this->new_status
                ? [
                    'value' => $this->new_status->value,
                    'label' => $this->new_status->label(),
                ]
                : null,

            'changes' => $this->changes,

            'performed_by' => [
                'id' => $this->performed_by,
                'name' => $this->performer?->name
                   ,
            ],

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}