<?php

namespace App\Modules\Order\Services;

use App\Enums\OrderHistoryActionEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Modules\Order\Repositories\Interfaces\IOrderRepository;
use App\Modules\Order\Services\Interfaces\IOrderService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService implements IOrderService
{
    public function __construct(
        private readonly IOrderRepository $repository
    ) {
    }

    public function index(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function create(
        array $data,
        int $actorId
    ): Order {
        return DB::transaction(function () use (
            $data,
            $actorId
        ) {
            $requestedItems = $data['items'];

            $productIds = collect($requestedItems)
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $products = $this->lockProducts($productIds);

            $prepared = $this->reserveProducts(
                $requestedItems,
                $products
            );

            $order = $this->repository->create([
                'order_number' => $this->generateOrderNumber(),
                'currency_id' => $data['currency_id'],
                'status' => OrderStatusEnum::Pending,
                'total_amount' => $prepared['total_amount'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $order->items()->createMany(
                $prepared['items']
            );

            $order = $this->repository->loadDetails($order);

            $order->histories()->create([
                'action' => OrderHistoryActionEnum::Created,
                'old_status' => null,
                'new_status' => OrderStatusEnum::Pending,
                'changes' => [
                    'after' => $this->snapshot($order),
                ],
                'performed_by' => $actorId,
           
            ]);

            return $this->repository->loadDetails(
                $order->refresh()
            );
        });
    }

    public function update(
        int $orderId,
        array $data,
        int $actorId
    ): Order {
        return DB::transaction(function () use (
            $orderId,
            $data,
            $actorId
        ) {
            $order = $this->repository->findForUpdate(
                $orderId
            );

            if ($order->status === OrderStatusEnum::Cancelled) {
                throw ValidationException::withMessages([
                    'order' => 'A cancelled order cannot be updated.',
                ]);
            }

            $before = $this->snapshot($order);

            $updateData = [
                'updated_by' => $actorId,
            ];

            if (array_key_exists('currency_id', $data)) {
                $updateData['currency_id'] =
                    $data['currency_id'];
            }

            if (array_key_exists('notes', $data)) {
                $updateData['notes'] = $data['notes'];
            }

            if (array_key_exists('status', $data)) {
                $updateData['status'] =
                    OrderStatusEnum::from(
                        (int) $data['status']
                    );
            }

            if (array_key_exists('items', $data)) {
                $oldProductIds = $order->items
                    ->pluck('product_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id);

                $newProductIds = collect($data['items'])
                    ->pluck('product_id')
                    ->map(fn ($id) => (int) $id);

                /*
                 * Lock all affected products in the same ID order.
                 * This reduces inconsistent stock updates.
                 */
                $allProductIds = $oldProductIds
                    ->merge($newProductIds)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                $products = $this->lockProducts(
                    $allProductIds
                );

                $this->restoreOrderStock(
                    $order,
                    $products
                );

                $prepared = $this->reserveProducts(
                    $data['items'],
                    $products
                );

                $order->items()->delete();

                $order->items()->createMany(
                    $prepared['items']
                );

                $updateData['total_amount'] =
                    $prepared['total_amount'];
            }

            $oldStatus = $order->status;

            $order = $this->repository->update(
                $order,
                $updateData
            );

            $order = $this->repository->loadDetails($order);

            $after = $this->snapshot($order);

            $order->histories()->create([
                'action' => OrderHistoryActionEnum::Updated,
                'old_status' => $oldStatus,
                'new_status' => $order->status,
                'changes' => [
                    'before' => $before,
                    'after' => $after,
                ],
                'performed_by' => $actorId,
              
            ]);

            return $this->repository->loadDetails(
                $order->refresh()
            );
        });
    }

    public function delete(
        int $orderId,
        int $actorId
    ): Order {
        return DB::transaction(function () use (
            $orderId,
            $actorId
        ) {
            $order = $this->repository->findForUpdate(
                $orderId
            );

            $before = $this->snapshot($order);

            $productIds = $order->items
                ->pluck('product_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $products = $this->lockProducts($productIds);

            /*
            * Cancelling the order returns its quantities to stock.
            */
            $this->restoreOrderStock(
                $order,
                $products
            );

            $oldStatus = $order->status;

            $order = $this->repository->update($order, [
                'status' => OrderStatusEnum::Cancelled,
                'updated_by' => $actorId,
                'cancelled_by' => $actorId,
                'cancelled_at' => now(),
            ]);

            $order = $this->repository->loadDetails($order);

            $order->histories()->create([
                'action' => OrderHistoryActionEnum::Cancelled,
                'old_status' => $oldStatus,
                'new_status' => OrderStatusEnum::Cancelled,
                'changes' => [
                    'before' => $before,
                    'after' => $this->snapshot($order),
                ],
                'performed_by' => $actorId,
            
            ]);

            $this->repository->delete($order);

            return $this->repository->loadDetails($order);
        });
    }

    private function lockProducts(
        array $productIds
    ): EloquentCollection {
        if (empty($productIds)) {
            return new EloquentCollection();
        }

        return Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function reserveProducts(
        array $requestedItems,
        EloquentCollection $products
    ): array {
        $items = [];
        $totalAmount = 0.0;

        foreach ($requestedItems as $requestedItem) {
            $productId = (int) $requestedItem['product_id'];
            $quantity = (int) $requestedItem['quantity'];

            /** @var Product|null $product */
            $product = $products->get($productId);

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => "Product {$productId} does not exist.",
                ]);
            }

            if ($product->quantity_in_stock < $quantity) {
                throw ValidationException::withMessages([
                    'items' => sprintf(
                        'Product %s has only %d items in stock.',
                        $product->code,
                        $product->quantity_in_stock
                    ),
                ]);
            }

            $unitPrice = (float) $product->unit_price;

            $lineTotal = round(
                $unitPrice * $quantity,
                2
            );

            $product->quantity_in_stock -= $quantity;
            $product->save();

            $items[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            $totalAmount = round(
                $totalAmount + $lineTotal,
                2
            );
        }

        return [
            'items' => $items,
            'total_amount' => $totalAmount,
        ];
    }

    private function restoreOrderStock(
        Order $order,
        EloquentCollection $products
    ): void {
        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            /** @var Product|null $product */
            $product = $products->get(
                (int) $item->product_id
            );

        
            if (! $product) {
                continue;
            }

            $product->quantity_in_stock += $item->quantity;
            $product->save();
        }
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(
            (string) Str::ulid()
        );
    }

    private function actorName(int $actorId): ?string
    {
        return User::query()
            ->whereKey($actorId)
            ->value('name');
    }

    private function snapshot(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'order_number' => $order->order_number,
            'currency_id' => $order->currency_id,
            'status' => $order->status->value,
            'total_amount' => $order->total_amount,
            'notes' => $order->notes,

            'items' => $order->items
                ->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                  
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ])
                ->values()
                ->all(),
        ];
    }
}