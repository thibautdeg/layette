<?php

namespace App\Actions;

use App\Enums\ContributionEventAction;
use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelContributionAction
{
    public function handle(Contribution $contribution, ?User $actor = null): Contribution
    {
        return DB::transaction(function () use ($contribution, $actor): Contribution {
            $contribution->status = ContributionStatus::Cancelled;
            $contribution->cancelled_at = now();
            $contribution->confirmed_at = null;
            $contribution->save();

            $contribution->bankTransaction?->contribution()->dissociate()->save();

            $contribution->logEvent(ContributionEventAction::Cancelled, $actor);

            return $contribution;
        });
    }
}
