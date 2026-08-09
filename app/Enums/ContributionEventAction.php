<?php

namespace App\Enums;

enum ContributionEventAction: string
{
    case Created = 'created';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Updated = 'updated';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Aangekondigd',
            self::Confirmed => 'Bevestigd',
            self::Cancelled => 'Geannuleerd',
            self::Updated => 'Aangepast',
        };
    }
}
