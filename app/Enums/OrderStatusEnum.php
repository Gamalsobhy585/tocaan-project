<?php

namespace App\Enums;

enum OrderStatusEnum: int
{
    /**
     * Order was created but is not confirmed yet.
     */
    case Pending = 0;

    /**
     * Order was reviewed and confirmed.
     */
    case Confirmed = 1;

    /**
     * Order was cancelled and soft-deleted.
     */
    case Cancelled = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }

    public static function values(): array
    {
        return array_map(
            fn (self $status): int => $status->value,
            self::cases()
        );
    }

    /**
     * Statuses allowed through the normal update endpoint.
     *
     * Cancellation must go through DELETE /orders/{order}.
     */
    public static function updatableValues(): array
    {
        return [
            self::Pending->value,
            self::Confirmed->value,
        ];
    }
}