<?php

namespace App\Modules\PaymentMethod\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Modules\PaymentMethod\Requests\StorePaymentMethodRequest;
use App\Modules\PaymentMethod\Resources\PaymentMethodResource;
use App\Modules\PaymentMethod\Services\Interfaces\IPaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly IPaymentMethodService $service
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        return PaymentMethodResource::collection(
            $this->service->index()
        );
    }

    public function add(
        StorePaymentMethodRequest $request
    ): JsonResponse {
        $paymentMethod = $this->service->add(
            $request->validated()
        );

        return response()->json([
            'message' => 'Payment method added successfully.',
            'data' => new PaymentMethodResource($paymentMethod),
        ], 201);
    }

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): JsonResponse {
        $paymentMethod = $this->service->toggleActive(
            $paymentMethod
        );

        return response()->json([
            'message' => $paymentMethod->is_active
                ? 'Payment method activated successfully.'
                : 'Payment method deactivated successfully.',
            'data' => new PaymentMethodResource($paymentMethod),
        ]);
    }
}