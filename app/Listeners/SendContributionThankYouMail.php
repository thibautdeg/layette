<?php

namespace App\Listeners;

use App\Events\ContributionConfirmed;
use App\Mail\ContributionThankYouMail;
use Illuminate\Support\Facades\Mail;

class SendContributionThankYouMail
{
    public function handle(ContributionConfirmed $event): void
    {
        $contribution = $event->contribution;

        if ($contribution->isPurchase()) {
            return;
        }

        Mail::to($contribution->user->email)->send(new ContributionThankYouMail($contribution));
    }
}
