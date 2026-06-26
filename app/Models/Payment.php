<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'order_id',
        'payment_method_id',
        'currency_id',
        'status',
        'amount',
        'transaction_reference',
        'idempotency_key',
        'gateway_response',
        'failure_reason',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatusEnum::class,
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)
            ->withTrashed();
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'processed_by'
        );
    }

  
}