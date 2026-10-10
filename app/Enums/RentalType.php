<?php

namespace App\Enums;

enum RentalType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';

    public function label(): string
    {
        return match ($this) {
            self::Residential => 'سكني',
            self::Commercial => 'تجاري',
        };
    }
}
