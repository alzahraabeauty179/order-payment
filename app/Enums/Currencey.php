<?php

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case EGP = 'EGP';
    case SAR = 'SAR';

    /**
    * Get the human-readable label for the order status.
    * @return string
    */
    public function label(): string
    {
        return match ($this) {
            self::USD => 'US Dollar',
            self::EGP => 'Egyptian Pound',
            self::SAR => 'Saudi Riyal',
        };
    }
}
