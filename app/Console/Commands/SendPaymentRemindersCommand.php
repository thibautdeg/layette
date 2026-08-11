<?php

namespace App\Console\Commands;

use App\Mail\ContributionPaymentReminderMail;
use App\Models\Contribution;
use App\Models\GiftList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'contributions:send-payment-reminders';

    protected $description = 'Mail donors whose contribution is still waiting for their transfer';

    public function handle(): int
    {
        if (GiftList::current()->iban === null) {
            $this->components->warn('No account number configured, so there is nothing to remind donors of.');

            return self::SUCCESS;
        }

        $contributions = Contribution::query()
            ->dueForPaymentReminder()
            ->with(['user', 'gift'])
            ->get();

        foreach ($contributions as $contribution) {
            Mail::to($contribution->user->email)->send(new ContributionPaymentReminderMail($contribution));

            $contribution->payment_reminders_sent++;
            $contribution->payment_reminded_at = now();
            $contribution->save();
        }

        $this->components->info("Sent {$contributions->count()} payment reminder(s).");

        return self::SUCCESS;
    }
}
