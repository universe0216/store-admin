<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum Department: string
{
    use HasEnumOptions;
    case Footwear     = 'footwear';
    case Apparel      = 'apparel';
    // case Accessories  = 'accessories';
    // case Electronics  = 'electronics';
    // case Home         = 'home';
    case Other        = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Footwear    => 'Footwear',
            self::Apparel     => 'Apparel',
            // self::Accessories => 'Accessories',
            // self::Electronics => 'Electronics',
            // self::Home        => 'Home',
            self::Other       => 'Other',
        };
    }
}
