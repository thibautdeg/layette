<?php

namespace App\Mail;

use App\Models\Contribution;
use App\Models\GiftList;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContributionThankYouMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Contribution $contribution) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Goed ontvangen — dankjewel! 💛',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contribution-thank-you',
            with: [
                'contribution' => $this->contribution,
                'gift' => $this->contribution->gift,
                'giftList' => $this->contribution->gift->giftList ?? GiftList::current(),
            ],
        );
    }
}
