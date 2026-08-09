<?php

namespace App\Actions;

use App\DTO\FreeContributionData;
use App\Enums\ContributionEventAction;
use App\Enums\ContributionType;
use App\Models\Contribution;
use App\Models\GiftList;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateFreeContributionAction
{
    /**
     * Create a contribution that is not tied to any gift.
     */
    public function handle(GiftList $giftList, User $user, FreeContributionData $data): Contribution
    {
        if ($giftList->isClosed()) {
            throw ValidationException::withMessages([
                'gift' => 'De lijst is afgesloten, er zijn geen nieuwe bijdragen meer mogelijk.',
            ]);
        }

        return DB::transaction(function () use ($user, $data): Contribution {
            $contribution = new Contribution([
                'name' => $user->name,
                'together_with' => $data->togetherWith,
                'amount' => $data->amount,
                'message' => $data->message,
            ]);

            $contribution->reference = Contribution::generateReference();
            $contribution->type = ContributionType::Contribution;
            $contribution->user()->associate($user);

            $contribution->save();

            $contribution->logEvent(ContributionEventAction::Created, $user);

            return $contribution;
        });
    }
}
