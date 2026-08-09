<?php

namespace App\Enums;

enum BabyGender: string
{
    case Girl = 'girl';
    case Boy = 'boy';
    case Surprise = 'surprise';

    public function label(): string
    {
        return match ($this) {
            self::Girl => 'Meisje',
            self::Boy => 'Jongen',
            self::Surprise => 'Verrassing',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Girl => '🎀',
            self::Boy => '💙',
            self::Surprise => '🎁',
        };
    }
}
