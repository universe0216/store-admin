<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum Season: string
{
    use HasEnumOptions;

    case Spring    = 'spring';
    case Summer    = 'summer';
    case Fall      = 'fall';
    case Winter    = 'winter';
    case AllSeason = 'all_season';

    public function label(): string
    {
        return match ($this) {
            self::Spring    => 'Spring',
            self::Summer    => 'Summer',
            self::Fall      => 'Fall',
            self::Winter    => 'Winter',
            self::AllSeason => 'All season',
        };
    }
}
