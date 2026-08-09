<?php

namespace App\Mail;

use App\Models\Contribution;
use App\Models\GiftList;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ContributionReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Contribution $contribution) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->contribution->isPurchase()
                ? 'Bedankt voor je reservatie!'
                : 'Bedankt voor je bijdrage!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contribution-received',
            with: [
                'contribution' => $this->contribution,
                'gift' => $this->contribution->gift,
                'giftList' => $this->contribution->gift->giftList ?? GiftList::current(),
                'instructionsUrl' => URL::signedRoute('contribution.instructions', ['contribution' => $this->contribution->reference]),
                'accountUrl' => route('account.contributions'),
            ],
        );
    }
}
