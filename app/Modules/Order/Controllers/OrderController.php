<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Requests\IndexOrderRequest;
use App\Modules\Order\Requests\StoreOrderRequest;
use App\Modules\Order\Requests\UpdateOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\Order\Services\Interfaces\IOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private readonly IOrderService $service
    ) {
    }

    public function index(
        IndexOrderRequest $request
    ): AnonymousResourceCollection {
        $orders = $this->service->index(
            $request->validated()
        );

        return OrderResource::collection($orders);
    }

    public function store(
        StoreOrderRequest $request
    ): JsonResponse {
        $order = $this->service->create(
            data: $request->validated(),
            actorId: (int) $request->user()->getAuthIdentifier()
        );

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => new OrderResource($order),
        ], 201);
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

        return response()->json([
            'message' => 'Order updated successfully.',
            'data' => new OrderResource($updatedOrder),
        ]);
    }

    public function destroy(
        int $order
    ): JsonResponse {
        $cancelledOrder = $this->service->delete(
            orderId: $order,
            actorId: (int) request()->user()->getAuthIdentifier()
        );

        return response()->json([
            'message' => 'Order cancelled and deleted successfully.',
            'data' => new OrderResource($cancelledOrder),
        ]);
    }
}