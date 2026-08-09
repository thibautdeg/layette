<?php

namespace App\Actions;

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use Illuminate\Support\Facades\DB;

class CancelContributionAction
{
    public function handle(Contribution $contribution): Contribution
    {
        return DB::transaction(function () use ($contribution): Contribution {
            $contribution->status = ContributionStatus::Cancelled;
            $contribution->cancelled_at = now();
            $contribution->confirmed_at = null;
            $contribution->save();

            $contribution->bankTransaction?->contribution()->dissociate()->save();

            return $contribution;
        });
    }
}
