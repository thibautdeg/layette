<?php

namespace App\Actions;

use App\DTO\ContributionData;
use App\Enums\ContributionEventAction;
use App\Enums\ContributionType;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateContributionAction
{
    public function handle(Gift $gift, User $user, ContributionData $data): Contribution
    {
        return DB::transaction(function () use ($gift, $user, $data): Contribution {
            $gift = Gift::query()->whereKey($gift->id)->lockForUpdate()->firstOrFail();
            $gift->load('contributions');

            $this->ensureGiftAcceptsContribution($gift, $data);

            $contribution = new Contribution([
                'name' => $user->name,
                'together_with' => $data->togetherWith,
                'amount' => $data->type === ContributionType::Purchase ? null : $data->amount,
                'message' => $data->message,
            ]);

            $contribution->reference = Contribution::generateReference();
            $contribution->type = $data->type;
            $contribution->gift()->associate($gift);
            $contribution->user()->associate($user);

            $contribution->save();

            $contribution->logEvent(ContributionEventAction::Created, $user);

            return $contribution;
        });
    }

    protected function ensureGiftAcceptsContribution(Gift $gift, ContributionData $data): void
    {
        if ($gift->giftList->isClosed()) {
            throw ValidationException::withMessages([
                'gift' => 'De lijst is afgesloten, er zijn geen nieuwe bijdragen meer mogelijk.',
            ]);
        }

        if ($gift->isHidden() || $gift->isReserved()) {
            throw ValidationException::withMessages([
                'gift' => 'Dit cadeau is niet meer beschikbaar.',
            ]);
        }

        if ($data->type === ContributionType::Purchase) {
            $this->ensureGiftCanBePurchased($gift);

            return;
        }

        $this->ensureGiftAcceptsAmount($gift, $data);
    }

    protected function ensureGiftCanBePurchased(Gift $gift): void
    {
        if (! $gift->allows_purchase) {
            throw ValidationException::withMessages([
                'gift' => 'Dit cadeau kan je niet zelf kopen, maar je kan er wel aan bijdragen.',
            ]);
        }

        if ($gift->hasActiveContributions()) {
            throw ValidationException::withMessages([
                'gift' => 'Er werd al aan dit cadeau bijgedragen, het kan niet meer zelf gekocht worden.',
            ]);
        }
    }

    protected function ensureGiftAcceptsAmount(Gift $gift, ContributionData $data): void
    {
        if ($gift->isFull()) {
            throw ValidationException::withMessages([
                'gift' => 'Dit cadeau is al volledig gefinancierd.',
            ]);
        }

        if ($data->amount === null || $data->amount < 100) {
            throw ValidationException::withMessages([
                'amount' => 'Geef een bedrag van minstens 1 euro op.',
            ]);
        }

        if (! $gift->allows_partial_contributions && $data->amount !== $gift->price) {
            throw ValidationException::withMessages([
                'amount' => 'Voor dit cadeau ligt het bedrag vast.',
            ]);
        }

        if ($data->amount > $gift->remainingAmount()) {
            $remaining = number_format($gift->remainingAmount() / 100, 2, ',', '.');

            throw ValidationException::withMessages([
                'amount' => "Er is nog maar € {$remaining} nodig voor dit cadeau.",
            ]);
        }
    }
}
