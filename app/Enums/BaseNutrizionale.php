<?php

namespace App\Enums;

enum BaseNutrizionale: string
{
    case G100  = '100g';
    case ML100 = '100ml';
    case UNIT  = 'unit';

    public function label(): string
    {
        return match($this) {
            self::G100  => 'per 100 g',
            self::ML100 => 'per 100 ml',
            self::UNIT  => 'per 1 unità',
        };
    }
}
