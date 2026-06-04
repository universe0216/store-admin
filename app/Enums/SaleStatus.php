<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Incomplete = 'incomplete';
    case Completed  = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Incomplete => 'Incomplete',
            self::Completed  => 'Completed',
        };
    }

    public static function fromPaymentBalance(float $amountDue, float $paidTotal): self
    {
        $unpaid = round(max(0, $amountDue - $paidTotal), 2);

        return $unpaid <= 0.01 ? self::Completed : self::Incomplete;
    }
}
