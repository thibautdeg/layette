<?php

namespace App\Actions;

use App\Enums\ContributionStatus;
use App\Models\BankTransaction;
use App\Models\Contribution;
use Illuminate\Support\Facades\DB;

class ConfirmContributionAction
{
    public function handle(Contribution $contribution, ?BankTransaction $transaction = null): Contribution
    {
        return DB::transaction(function () use ($contribution, $transaction): Contribution {
            $contribution->status = ContributionStatus::Confirmed;
            $contribution->confirmed_at = now();
            $contribution->cancelled_at = null;
            $contribution->save();

            $transaction?->contribution()->associate($contribution)->save();

            return $contribution;
        });
    }
}
