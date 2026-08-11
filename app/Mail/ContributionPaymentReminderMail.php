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

class ContributionPaymentReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Contribution $contribution) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Een kleine herinnering aan je bijdrage',
        );
    }

    public function content(): Content
    {
        $giftList = $this->contribution->gift->giftList ?? GiftList::current();

        return new Content(
            markdown: 'mail.contribution-payment-reminder',
            with: [
                'contribution' => $this->contribution,
                'gift' => $this->contribution->gift,
                'giftList' => $giftList,
                'accountUrl' => route('account.contributions'),
                'qrPng' => app(GeneratePaymentQrAction::class)->png($giftList, $this->contribution),
            ],
        );
    }
}
