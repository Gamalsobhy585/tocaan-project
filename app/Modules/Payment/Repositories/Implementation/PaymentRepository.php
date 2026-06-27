<?php

namespace App\Modules\Payment\Repositories\Implementation;

use App\Models\Payment;
use App\Modules\Payment\Repositories\Interfaces\IPaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository implements IPaymentRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 15), 1),
            100
        );

        return Payment::query()
            ->with([
                'order:id,order_number',
                'paymentMethod:id,name_ar,name_en,code,strategy_key',
                'currency:id,name_ar,name_en,code',
                'processor:id,name',
            ])
            ->when(
                isset($filters['order_id']),
                fn ($query) => $query->where(
                    'order_id',
                    $filters['order_id']
                )
            )
            ->when(
                isset($filters['status']),
                fn ($query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                isset($filters['payment_method_id']),
                fn ($query) => $query->where(
                    'payment_method_id',
                    $filters['payment_method_id']
                )
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn ($query) => $query->whereDate(
                    'created_at',
                    '>=',
                    $filters['date_from']
                )
            )
            ->when(
                filled($filters['date_to'] ?? null),
                fn ($query) => $query->whereDate(
                    'created_at',
                    '<=',
                    $filters['date_to']
                )
            )
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForOrder(
        int $orderId,
        int $perPage = 15
    ): LengthAwarePaginator {
        $perPage = min(max($perPage, 1), 100);

        return Payment::query()
            ->where('order_id', $orderId)
            ->with([
                'paymentMethod:id,name_ar,name_en,code,strategy_key',
                'currency:id,name_ar,name_en,code',
                'processor:id,name',
            ])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Payment
    {
        return Payment::query()->create($data);
    }

    public function lockById(int $paymentId): Payment
    {
        return Payment::query()
            ->lockForUpdate()
            ->findOrFail($paymentId);
    }

    public function update(
        Payment $payment,
        array $data
    ): Payment {
        $payment->update($data);

        return $payment->refresh();
    }

    public function findWithDetails(int $paymentId): Payment
    {
        return Payment::query()
            ->with([
                'order',
                'paymentMethod',
                'currency',
                'processor:id,name',
            ])
            ->findOrFail($paymentId);
    }
}