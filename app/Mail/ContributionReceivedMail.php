<?php

namespace App\Mail;

use App\Actions\GeneratePaymentQrAction;
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
        $giftList = $this->contribution->gift->giftList ?? GiftList::current();

        return new Content(
            markdown: 'mail.contribution-received',
            with: [
                'contribution' => $this->contribution,
                'gift' => $this->contribution->gift,
                'giftList' => $giftList,
                'instructionsUrl' => URL::signedRoute('contribution.instructions', ['contribution' => $this->contribution->reference]),
                'accountUrl' => route('account.contributions'),
                'qrPng' => $this->contribution->isPurchase()
                    ? null
                    : app(GeneratePaymentQrAction::class)->png($giftList, $this->contribution),
            ],
        );
    }
}
