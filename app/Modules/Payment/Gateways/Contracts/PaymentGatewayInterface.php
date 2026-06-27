<?php

namespace App\Modules\Payment\Gateways\Contracts;

interface PaymentGatewayInterface
{
    public function key(): string;

    public function process(array $data): array;
}