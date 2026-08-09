<?php

namespace App\Actions;

use App\Enums\ContributionEventAction;
use App\Enums\ContributionStatus;
use App\Events\ContributionConfirmed;
use App\Models\BankTransaction;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmContributionAction
{
    public function handle(Contribution $contribution, ?BankTransaction $transaction = null, ?User $actor = null): Contribution
    {
        $wasConfirmed = $contribution->isConfirmed();

        DB::transaction(function () use ($contribution, $transaction, $actor, $wasConfirmed): void {
            $contribution->status = ContributionStatus::Confirmed;
            $contribution->confirmed_at = now();
            $contribution->cancelled_at = null;
            $contribution->save();

            $transaction?->contribution()->associate($contribution)->save();

            if (! $wasConfirmed) {
                $contribution->logEvent(ContributionEventAction::Confirmed, $actor);
            }
        });

        if (! $wasConfirmed) {
            ContributionConfirmed::dispatch($contribution);
        }

        return $contribution;
    }
}
