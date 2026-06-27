<?php

namespace App\Modules\Payment\Services;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Modules\Payment\DTOs\PaymentGatewayContext;
use App\Modules\Payment\DTOs\PaymentGatewayResult;
use App\Modules\Payment\Gateways\PaymentGatewayRegistry;
use App\Modules\Payment\Repositories\Interfaces\IPaymentRepository;
use App\Modules\Payment\Services\Interfaces\IPaymentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService implements IPaymentService
{
    public function __construct(
        private readonly IPaymentRepository $repository,
        private readonly PaymentGatewayRegistry $gatewayRegistry,
    ) {
    }

    public function index(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function forOrder(
        int $orderId,
        int $perPage = 15
    ): LengthAwarePaginator {
        Order::query()
            ->withTrashed()
            ->findOrFail($orderId);

        return $this->repository->paginateForOrder(
            orderId: $orderId,
            perPage: $perPage
        );
    }

    public function show(int $paymentId): Payment
    {
        return $this->repository->findWithDetails(
            $paymentId
        );
    }

    public function process(
        int $orderId,
        array $data,
        int $actorId
    ): Payment {
        $prepared = DB::transaction(function () use (
            $orderId,
            $data,
            $actorId
        ) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($orderId);

            if (
                $order->status === OrderStatusEnum::Cancelled
                || $order->trashed()
            ) {
                throw ValidationException::withMessages([
                    'order' => 'A cancelled order cannot receive a payment.',
                ]);
            }

            $paymentMethod = PaymentMethod::query()
                ->whereKey($data['payment_method_id'])
                ->where('is_active', true)
                ->firstOrFail();

            /*
             * Fail before creating the payment when the strategy
             * has not been registered in the application.
             */
            $this->gatewayRegistry->resolve(
                $paymentMethod->strategy_key
            );

            $successfulAmount = (float) Payment::query()
                ->where('order_id', $order->id)
                ->where(
                    'status',
                    PaymentStatusEnum::Successful->value
                )
                ->sum('amount');

            $pendingAmount = (float) Payment::query()
                ->where('order_id', $order->id)
                ->where(
                    'status',
                    PaymentStatusEnum::Pending->value
                )
                ->sum('amount');

            $remainingAmount = round(
                (float) $order->total_amount
                    - $successfulAmount
                    - $pendingAmount,
                2
            );

            if ($remainingAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This order has no remaining amount to pay.',
                ]);
            }

            $amount = array_key_exists('amount', $data)
                ? round((float) $data['amount'], 2)
                : $remainingAmount;

            if ($amount <= 0 || $amount > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'The payment amount must be greater than zero and cannot exceed %.2f.',
                        $remainingAmount
                    ),
                ]);
            }

            $payment = $this->repository->create([
                'payment_number' => $this->generatePaymentNumber(),
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
                'currency_id' => $order->currency_id,
                'status' => PaymentStatusEnum::Pending,
                'amount' => $amount,
                'idempotency_key' => $data['idempotency_key']
                    ?? null,
                'processed_by' => $actorId,
            ]);

          

            return [
                'payment_id' => $payment->id,
                'order' => $order,
                'payment_method' => $paymentMethod,
                'gateway_payload' => $data['gateway_payload'] ?? [],
            ];
        });

        $payment = Payment::query()->findOrFail(
            $prepared['payment_id']
        );

        $strategy = $this->gatewayRegistry->resolve(
            $prepared['payment_method']->strategy_key
        );

        try {
            $result = $strategy->process(
                new PaymentGatewayContext(
                    payment: $payment,
                    order: $prepared['order'],
                    paymentMethod: $prepared['payment_method'],
                    payload: $prepared['gateway_payload'],
                )
            );
        } catch (Throwable $exception) {
            report($exception);

            $result = PaymentGatewayResult::failure(
                failureReason: 'The payment gateway could not process the request.',
                response: [
                    'exception' => class_basename($exception),
                ]
            );
        }

        return $this->finalizePayment(
            paymentId: $payment->id,
            result: $result,
            actorId: $actorId
        );
    }

    private function finalizePayment(
        int $paymentId,
        PaymentGatewayResult $result,
        int $actorId
    ): Payment {
        DB::transaction(function () use (
            $paymentId,
            $result,
            $actorId
        ) {
            $payment = $this->repository->lockById(
                $paymentId
            );

            if ($payment->status !== PaymentStatusEnum::Pending) {
                throw ValidationException::withMessages([
                    'payment' => 'This payment has already been finalized.',
                ]);
            }

            $newStatus = $result->successful
                ? PaymentStatusEnum::Successful
                : PaymentStatusEnum::Failed;

            $this->repository->update($payment, [
                'status' => $newStatus,
                'transaction_reference' =>
                    $result->transactionReference,
                'gateway_response' => $result->response,
                'failure_reason' => $result->failureReason,
                'processed_by' => $actorId,
                'processed_at' => now(),
            ]);

          
        });

        return $this->repository->findWithDetails(
            $paymentId
        );
    }

    private function generatePaymentNumber(): string
    {
        return 'PAY-' . Str::upper(
            (string) Str::ulid()
        );
    }

    private function actorName(int $actorId): ?string
    {
        return User::query()
            ->whereKey($actorId)
            ->value('name');
    }
}