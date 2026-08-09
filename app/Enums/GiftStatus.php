<?php

namespace App\Enums;

enum GiftStatus: string
{
    case Open = 'open';
    case Reserved = 'reserved';
    case Full = 'full';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Reserved => 'Gereserveerd',
            self::Full => 'Volzet',
            self::Hidden => 'Verborgen',
        };
    }
}
