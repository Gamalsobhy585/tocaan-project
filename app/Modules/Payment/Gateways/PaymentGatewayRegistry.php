<?php

namespace App\Modules\Payment\Gateways;

use App\Modules\Payment\Exceptions\UnsupportedPaymentGatewayException;
use App\Modules\Payment\Gateways\Contracts\PaymentGatewayStrategy;

class PaymentGatewayRegistry
{
    /**
     * @var array<string, PaymentGatewayStrategy>
     */
    private array $strategies = [];

    /**
     * @param iterable<PaymentGatewayStrategy> $strategies
     */
    public function __construct(iterable $strategies)
    {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->key()] = $strategy;
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->strategies);
    }

    public function resolve(string $key): PaymentGatewayStrategy
    {
        if (! $this->has($key)) {
            throw new UnsupportedPaymentGatewayException(
                "Payment gateway strategy [{$key}] is not registered."
            );
        }

        return $this->strategies[$key];
    }

    public function registeredKeys(): array
    {
        return array_keys($this->strategies);
    }
}