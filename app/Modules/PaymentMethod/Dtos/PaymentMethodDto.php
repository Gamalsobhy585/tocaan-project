<?php

namespace App\Modules\PaymentMethod\Dtos;

class PaymentMethodDto
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