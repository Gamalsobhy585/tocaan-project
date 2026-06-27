<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Modules\Payment\Requests\IndexPaymentRequest;
use App\Modules\Payment\Requests\ProcessPaymentRequest;
use App\Modules\Payment\Resources\PaymentResource;
use App\Modules\Payment\Services\Interfaces\IPaymentService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly IPaymentService $service
    ) {
    }

    public function index(
        IndexPaymentRequest $request
    ): JsonResponse {
        $payments = $this->service->index(
            $request->validated()
        );

        $paymentsResource = PaymentResource::collection($payments)
            ->additional([
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem(),
                    'has_more_pages' => $payments->hasMorePages(),
                ],
            ]);

        return $this->returnDataWithPagination(
            'Payments retrieved successfully.',
            200,
            $paymentsResource
        );
    }

    public function forOrder(
        Request $request,
        int $order
    ): JsonResponse {
        $validated = $request->validate([
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $payments = $this->service->forOrder(
            orderId: $order,
            perPage: (int) ($validated['per_page'] ?? 15)
        );

        $paymentsResource = PaymentResource::collection($payments)
            ->additional([
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem(),
                    'has_more_pages' => $payments->hasMorePages(),
                ],
            ]);

        return $this->returnDataWithPagination(
            'Order payments retrieved successfully.',
            200,
            $paymentsResource
        );
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment = $this->service->show(
            $payment->id
        );

        return $this->returnData(
            'Payment retrieved successfully.',
            200,
            new PaymentResource($payment)
        );
    }

    public function process(
        ProcessPaymentRequest $request,
        int $order
    ): JsonResponse {
        $payment = $this->service->process(
            orderId: $order,
            data: $request->validated(),
            actorId: (int) $request
                ->user()
                ->getAuthIdentifier()
        );

        $paymentResource = new PaymentResource($payment);

        $isSuccessful = $payment->status->value === 1;

        if (!$isSuccessful) {
            return $this->returnErrorData(
                'Payment processing failed.',
                422,
                $paymentResource
            );
        }

        return $this->returnData(
            'Payment processed successfully.',
            201,
            $paymentResource
        );
    }
}