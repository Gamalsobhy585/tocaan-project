<?php

namespace App\Modules\Payment\DTOs;

final readonly class PaymentGatewayResult
{
    public function __construct(
        public bool $successful,
        public ?string $transactionReference = null,
        public array $response = [],
        public ?string $failureReason = null,
    ) {
    }

    public static function success(
        string $transactionReference,
        array $response = []
    ): self {
        return new self(
            successful: true,
            transactionReference: $transactionReference,
            response: $response,
        );
    }

    public static function failure(
        string $failureReason,
        array $response = []
    ): self {
        return new self(
            successful: false,
            response: $response,
            failureReason: $failureReason,
        );
    }
}