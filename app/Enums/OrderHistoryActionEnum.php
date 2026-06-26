<?php

namespace App\Enums;

enum OrderHistoryActionEnum: int
{
    /**
     * The order was created.
     */
    case Created = 0;

    /**
     * The order was updated.
     */
    case Updated = 1;

    /**
     * The order was cancelled and soft-deleted.
     */
    case Cancelled = 2;

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Cancelled => 'Cancelled',
        };
    }
}