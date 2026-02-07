<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case PAYPAL = 'paypal';

    /**
     * Get the human-readable label for the payment method.
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::PAYPAL => 'PayPal',
        };
    }
}
