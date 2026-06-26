<?php

namespace App\Modules\Payment\Gateways\Strategies;

use App\Modules\Payment\DTOs\PaymentGatewayContext;
use App\Modules\Payment\DTOs\PaymentGatewayResult;
use App\Modules\Payment\Gateways\Contracts\PaymentGatewayStrategy;
use Illuminate\Support\Str;

class PayPalGatewayStrategy implements PaymentGatewayStrategy
{
    public function key(): string
    {
        return 'paypal';
    }

    public function process(
        PaymentGatewayContext $context
    ): PaymentGatewayResult {
        /*
         * Simulation only.
         * Replace this block with the real PayPal SDK later.
         */
        $shouldSucceed = (bool) (
            $context->payload['simulate_success'] ?? true
        );

        if (! $shouldSucceed) {
            return PaymentGatewayResult::failure(
                failureReason: 'PayPal payment was rejected.',
                response: [
                    'gateway' => $this->key(),
                    'state' => 'rejected',
                ]
            );
        }

        return PaymentGatewayResult::success(
            transactionReference: 'PP-' . Str::upper(
                Str::random(20)
            ),
            response: [
                'gateway' => $this->key(),
                'state' => 'approved',
            ]
        );
    }
}