<?php

namespace App\Modules\PaymentMethod\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Modules\PaymentMethod\Requests\StorePaymentMethodRequest;
use App\Modules\PaymentMethod\Resources\PaymentMethodResource;
use App\Modules\PaymentMethod\Services\Interfaces\IPaymentMethodService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly IPaymentMethodService $service
    ) {
    }

    public function index(): JsonResponse
    {
        $paymentMethods = PaymentMethodResource::collection(
            $this->service->index()
        );

        return $this->returnData(
            'Payment methods retrieved successfully.',
            200,
            $paymentMethods
        );
    }

    public function add(
        StorePaymentMethodRequest $request
    ): JsonResponse {
        $paymentMethod = $this->service->add(
            $request->validated()
        );

        return $this->returnData(
            'Payment method added successfully.',
            201,
            new PaymentMethodResource($paymentMethod)
        );
    }

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): JsonResponse {
        $paymentMethod = $this->service->toggleActive(
            $paymentMethod
        );

        $message = $paymentMethod->is_active
            ? 'Payment method activated successfully.'
            : 'Payment method deactivated successfully.';

        return $this->returnData(
            $message,
            200,
            new PaymentMethodResource($paymentMethod)
        );
    }
}