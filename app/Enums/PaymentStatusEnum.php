<?php

namespace App\Enums;

enum PaymentStatusEnum: int
{
    /**
     * Payment was created and is waiting for the gateway result.
     */
    case Pending = 0;

    /**
     * Gateway processing completed successfully.
     */
    case Successful = 1;

    /**
     * Gateway processing failed.
     */
    case Failed = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Successful => 'Successful',
            self::Failed => 'Failed',
        };
    }

    public static function values(): array
    {
        return array_map(
            fn (self $status): int => $status->value,
            self::cases()
        );
    }
}