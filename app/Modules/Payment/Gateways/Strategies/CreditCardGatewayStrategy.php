<?php

namespace App\Modules\Payment\Gateways\Strategies;

use App\Modules\Payment\DTOs\PaymentGatewayContext;
use App\Modules\Payment\DTOs\PaymentGatewayResult;
use App\Modules\Payment\Gateways\Contracts\PaymentGatewayStrategy;
use Illuminate\Support\Str;

class CreditCardGatewayStrategy implements PaymentGatewayStrategy
{
    public function key(): string
    {
        return 'credit_card';
    }

    public function process(
        PaymentGatewayContext $context
    ): PaymentGatewayResult {
        /*
         * Simulation only.
         * Replace this block with the real provider SDK later.
         */
        $shouldSucceed = (bool) (
            $context->payload['simulate_success'] ?? true
        );

        if (! $shouldSucceed) {
            return PaymentGatewayResult::failure(
                failureReason: 'Credit card payment was declined.',
                response: [
                    'gateway' => $this->key(),
                    'code' => 'CARD_DECLINED',
                ]
            );
        }

        return PaymentGatewayResult::success(
            transactionReference: 'CC-' . Str::upper(
                Str::random(20)
            ),
            response: [
                'gateway' => $this->key(),
                'code' => 'APPROVED',
            ]
        );
    }
}