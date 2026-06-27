<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Requests\IndexOrderRequest;
use App\Modules\Order\Requests\StoreOrderRequest;
use App\Modules\Order\Requests\UpdateOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\Order\Services\Interfaces\IOrderService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly IOrderService $service
    ) {
    }

    public function index(
        IndexOrderRequest $request
    ): JsonResponse {
        $orders = $this->service->index(
            $request->validated()
        );

        $ordersResource = OrderResource::collection($orders)
            ->additional([
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                    'has_more_pages' => $orders->hasMorePages(),
                ],
            ]);

        return $this->returnDataWithPagination(
            'Orders retrieved successfully.',
            200,
            $ordersResource
        );
    }

    public function store(
        StoreOrderRequest $request
    ): JsonResponse {
        $order = $this->service->create(
            data: $request->validated(),
            actorId: (int) $request->user()->getAuthIdentifier()
        );

        return $this->returnData(
            'Order created successfully.',
            201,
            new OrderResource($order)
        );
    }

    public function update(
        UpdateOrderRequest $request,
        int $order
    ): JsonResponse {
        $updatedOrder = $this->service->update(
            orderId: $order,
            data: $request->validated(),
            actorId: (int) $request->user()->getAuthIdentifier()
        );

        return $this->returnData(
            'Order updated successfully.',
            200,
            new OrderResource($updatedOrder)
        );
    }

    public function destroy(
        int $order
    ): JsonResponse {
        $cancelledOrder = $this->service->delete(
            orderId: $order,
            actorId: (int) request()
                ->user()
                ->getAuthIdentifier()
        );

        return $this->returnData(
            'Order cancelled and deleted successfully.',
            200,
            new OrderResource($cancelledOrder)
        );
    }
}