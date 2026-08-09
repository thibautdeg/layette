<?php

namespace App\Notifications;

use App\Models\Contribution;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContributionCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Contribution $contribution) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summary = $this->contribution->isPurchase()
            ? "{$this->contribution->name} koopt \"{$this->contribution->giftTitle()}\" zelf."
            : sprintf('%s draagt € %s bij aan "%s".', $this->contribution->displayName(), number_format($this->contribution->amount / 100, 2, ',', '.'), $this->contribution->giftTitle());

        $mail = (new MailMessage)
            ->subject('Nieuwe bijdrage op de geboortelijst')
            ->greeting('Goed nieuws!')
            ->salutation('— jullie geboortelijst')
            ->line($summary)
            ->line("Referentie: {$this->contribution->reference}");

        if ($this->contribution->message !== null) {
            $mail->line("Mededeling: \"{$this->contribution->message}\"");
        }

        return $mail->action('Bekijk de bijdragen', route('admin.contributions.index'));
    }
}
