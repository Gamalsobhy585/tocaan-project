<?php

namespace App\Modules\Payment\Gateways\Contracts;

use App\Modules\Payment\DTOs\PaymentGatewayContext;
use App\Modules\Payment\DTOs\PaymentGatewayResult;

interface PaymentGatewayStrategy
{
    /**
     * Must match payment_methods.strategy_key.
     */
    public function key(): string;

    public function process(
        PaymentGatewayContext $context
    ): PaymentGatewayResult;
}