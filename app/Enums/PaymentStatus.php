<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';

    /**
     * Get the human-readable label for the payment status.
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUCCESSFUL => 'Successful',
            self::FAILED => 'Failed',
        };
    }
}
