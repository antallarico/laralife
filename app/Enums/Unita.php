<?php

namespace App\Enums;

enum Unita: string
{
    case G  = 'g';
    case ML = 'ml';
    case U  = 'u';

    public function label(): string
    {
        return match($this) {
            self::G  => 'grammi',
            self::ML => 'millilitri',
            self::U  => 'unità',
        };
    }
}
