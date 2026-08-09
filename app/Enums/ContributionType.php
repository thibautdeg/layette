<?php

namespace App\Enums;

enum ContributionType: string
{
    case Contribution = 'contribution';
    case Purchase = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::Contribution => 'Bijdrage',
            self::Purchase => 'Koopt zelf',
        };
    }
}
