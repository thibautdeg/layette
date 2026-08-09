<?php

namespace App\Events;

use App\Models\Contribution;
use Illuminate\Foundation\Events\Dispatchable;

class ContributionConfirmed
{
    use Dispatchable;

    public function __construct(public Contribution $contribution) {}
}
