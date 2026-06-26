<?php

namespace App\Modules\Payment\DTOs;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;

final readonly class PaymentGatewayContext
{
    public function __construct(
        public Payment $payment,
        public Order $order,
        public PaymentMethod $paymentMethod,
        public array $payload = [],
    ) {
    }
}