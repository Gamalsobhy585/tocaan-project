<?php

namespace App\Modules\Payment\Dtos;

class PaymentDto
{
    public function __construct(
        // public readonly string $exampleField,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            // $data['example_field'],
        );
    }

    public function toArray(): array
    {
        return [
            // 'example_field' => $this->exampleField,
        ];
    }
}