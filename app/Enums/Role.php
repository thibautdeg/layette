<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Donor = 'donor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Beheerder',
            self::Donor => 'Schenker',
        };
    }
}
