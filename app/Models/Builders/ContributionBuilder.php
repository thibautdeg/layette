<?php

namespace App\Models\Builders;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Models\Contribution;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Contribution>
 */
class ContributionBuilder extends Builder
{
    public function pending(): self
    {
        return $this->where('status', ContributionStatus::Pending);
    }

    public function confirmed(): self
    {
        return $this->where('status', ContributionStatus::Confirmed);
    }

    public function active(): self
    {
        return $this->whereNot('status', ContributionStatus::Cancelled);
    }

    public function stale(): self
    {
        return $this->pending()->where('created_at', '<', now()->subWeek());
    }

    public function dueForPaymentReminder(): self
    {
        return $this->pending()
            ->whereNot('type', ContributionType::Purchase)
            ->whereNotNull('amount')
            ->where(function (Builder $query): void {
                foreach (Contribution::PAYMENT_REMINDER_DAYS as $alreadySent => $days) {
                    $query->orWhere(fn (Builder $reminder) => $reminder
                        ->where('payment_reminders_sent', $alreadySent)
                        ->where('created_at', '<=', now()->subDays($days)));
                }
            });
    }
}
