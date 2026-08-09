<?php

namespace App\Enums;

enum ContributionStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In afwachting',
            self::Confirmed => 'Bevestigd',
            self::Cancelled => 'Geannuleerd',
        };
    }

    public function donorLabel(): string
    {
        return match ($this) {
            self::Pending => 'Nog niet ontvangen',
            self::Confirmed => 'Goed ontvangen, bedankt',
            self::Cancelled => 'Geannuleerd',
        };
    }
}
