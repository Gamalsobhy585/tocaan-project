<?php

namespace App\Models;

use App\Enums\OrderHistoryActionEnum;
use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'action',
        'old_status',
        'new_status',
        'changes',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'action' => OrderHistoryActionEnum::class,
            'old_status' => OrderStatusEnum::class,
            'new_status' => OrderStatusEnum::class,
            'changes' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)
            ->withTrashed();
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'performed_by'
        );
    }
}