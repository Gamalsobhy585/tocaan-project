<?php

namespace App\Modules\Order\Repositories\Implementation;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Modules\Order\Repositories\Interfaces\IOrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements IOrderRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 15), 1),
            100
        );

        $includeDeleted = (bool) (
            $filters['include_deleted'] ?? false
        );

        /*
         * Cancelled orders are soft-deleted.
         * Therefore status=2 must include trashed records.
         */
        if (
            isset($filters['status'])
            && (int) $filters['status']
                === OrderStatusEnum::Cancelled->value
        ) {
            $includeDeleted = true;
        }

        return Order::query()
            ->when(
                $includeDeleted,
                fn ($query) => $query->withTrashed()
            )
            ->with([
                'currency:id,name_ar,name_en,code',
                'items',
                'creator:id,name',
                'updater:id,name',
                'canceller:id,name',
            ])
            ->when(
                isset($filters['status']),
                fn ($query) => $query->where(
                    'status',
                    (int) $filters['status']
                )
            )
            ->when(
                filled($filters['search'] ?? null),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(
                        'order_number',
                        'like',
                        "%{$search}%"
                    );
                }
            )
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Order
    {
        return Order::query()->create($data);
    }

    public function findForUpdate(int $orderId): Order
    {
        return Order::query()
            ->with('items')
            ->lockForUpdate()
            ->findOrFail($orderId);
    }

    public function update(
        Order $order,
        array $data
    ): Order {
        $order->update($data);

        return $order->refresh();
    }

    public function delete(Order $order): bool
    {
        return (bool) $order->delete();
    }

    public function loadDetails(Order $order): Order
    {
        return $order->load([
            'currency:id,name_ar,name_en,code',
            'items',
            'histories.performer:id,name',
            'creator:id,name',
            'updater:id,name',
            'canceller:id,name',
        ]);
    }
}