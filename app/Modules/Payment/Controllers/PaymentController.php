<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Modules\Payment\Requests\IndexPaymentRequest;
use App\Modules\Payment\Requests\ProcessPaymentRequest;
use App\Modules\Payment\Resources\PaymentResource;
use App\Modules\Payment\Services\Interfaces\IPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function __construct(
        private readonly IPaymentService $service
    ) {
    }

    public function index(
        IndexPaymentRequest $request
    ): AnonymousResourceCollection {
        return PaymentResource::collection(
            $this->service->index(
                $request->validated()
            )
        );
    }

    public function forOrder(
        Request $request,
        int $order
    ): AnonymousResourceCollection {
        $validated = $request->validate([
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        return PaymentResource::collection(
            $this->service->forOrder(
                orderId: $order,
                perPage: (int) (
                    $validated['per_page'] ?? 15
                )
            )
        );
    }

    public function show(Payment $payment): PaymentResource
    {
        return new PaymentResource(
            $this->service->show($payment->id)
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

        return response()->json([
            'message' => $payment->status->label()
                === 'Successful'
                ? 'Payment processed successfully.'
                : 'Payment processing failed.',
            'data' => new PaymentResource($payment),
        ], $payment->status->value === 1 ? 201 : 422);
    }
}