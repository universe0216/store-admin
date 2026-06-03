<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum Gender: string
{
    use HasEnumOptions;

    case Men    = 'men';
    case Women  = 'women';
    case Unisex = 'unisex';
    case Boys   = 'boys';
    case Girls  = 'girls';
    case Kids   = 'kids';

    public function label(): string
    {
        return match ($this) {
            self::Men    => 'Men',
            self::Women  => 'Women',
            self::Unisex => 'Unisex',
            self::Boys   => 'Boys',
            self::Girls  => 'Girls',
            self::Kids   => 'Kids',
        };
    }
}
