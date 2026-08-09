<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContributionEventAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContributionRequest;
use App\Models\Contribution;
use App\Models\ContributionEvent;
use App\Models\Gift;
use App\Models\GiftList;
use App\Models\NameGuess;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    public function index(): Response
    {
        $contributions = Contribution::query()
            ->with(['gift', 'user', 'bankTransaction', 'events.user'])
            ->latest()
            ->get();

        return Inertia::render('admin/Contributions', [
            'contributions' => $contributions->map(fn (Contribution $contribution): array => [
                'id' => $contribution->id,
                'reference' => $contribution->reference,
                'type' => $contribution->type->value,
                'type_label' => $contribution->type->label(),
                'name' => $contribution->displayName(),
                'email' => $contribution->user->email,
                'amount' => $contribution->amount,
                'message' => $contribution->message,
                'status' => $contribution->status->value,
                'status_label' => $contribution->status->label(),
                'is_stale' => $contribution->isStale(),
                'days_open' => $contribution->isPending() ? (int) $contribution->created_at?->diffInDays(now()) : null,
                'created_at' => $contribution->created_at?->toDateString(),
                'confirmed_at' => $contribution->confirmed_at?->toDateString(),
                'gift_title' => $contribution->giftTitle(),
                'transaction' => $contribution->bankTransaction !== null ? [
                    'amount' => $contribution->bankTransaction->amount,
                    'booked_at' => $contribution->bankTransaction->booked_at->toDateString(),
                    'counterparty_name' => $contribution->bankTransaction->counterparty_name,
                ] : null,
                'events' => $contribution->events->map(fn (ContributionEvent $event): array => [
                    'id' => $event->id,
                    'label' => $event->action->label(),
                    'by' => $event->user->name ?? 'automatisch (bankimport)',
                    'at' => $event->created_at?->format('d/m/Y H:i'),
                ])->values()->all(),
            ])->values(),
            'stats' => $this->stats($contributions),
            'weekly' => $this->weekly($contributions),
            'paymentDetailsMissing' => $this->paymentDetailsMissing(),
            'nameGuesses' => NameGuess::query()->with('user')->latest()->get()
                ->map(fn (NameGuess $guess): array => [
                    'id' => $guess->id,
                    'name' => $guess->name,
                    'by' => $guess->user->name,
                    'at' => $guess->created_at?->format('d/m/Y'),
                ])->values(),
        ]);
    }

    /**
     * Without an IBAN or Wero number, donors get payment instructions they cannot act on.
     */
    protected function paymentDetailsMissing(): bool
    {
        $giftList = GiftList::current();

        return $giftList->iban === null && $giftList->wero_phone === null;
    }

    /**
     * @param  Collection<int, Contribution>  $contributions
     * @return array{funding_percentage: int, contribution_count: int, top_gift: string|null}
     */
    protected function stats(Collection $contributions): array
    {
        $gifts = Gift::query()->visible()->with('contributions')->get();

        $totalPrice = (int) $gifts->sum('price');

        $funded = (int) $gifts->sum(fn (Gift $gift): int => $gift->isReserved()
            ? $gift->price
            : min($gift->confirmedAmount(), $gift->price));

        $active = $contributions->filter(fn (Contribution $contribution): bool => ! $contribution->isCancelled());

        $topGift = $active
            ->filter(fn (Contribution $contribution): bool => $contribution->gift_id !== null)
            ->groupBy('gift_id')
            ->sortByDesc(fn (Collection $group): int => $group->count())
            ->first()
            ?->first()
            ?->gift
            ->title;

        return [
            'funding_percentage' => $totalPrice > 0 ? (int) round($funded / $totalPrice * 100) : 0,
            'contribution_count' => $active->count(),
            'top_gift' => $topGift,
        ];
    }

    /**
     * The total announced per week over the last eight weeks, for the little chart.
     *
     * @param  Collection<int, Contribution>  $contributions
     * @return list<array{label: string, total: int, count: int}>
     */
    protected function weekly(Collection $contributions): array
    {
        $active = $contributions->filter(
            fn (Contribution $contribution): bool => ! $contribution->isCancelled() && $contribution->amount !== null
        );

        $weeks = [];

        for ($i = 7; $i >= 0; $i--) {
            $start = now()->startOfWeek()->subWeeks($i);
            $end = $start->endOfWeek();

            $inWeek = $active->filter(
                fn (Contribution $contribution): bool => $contribution->created_at !== null &&
                    $contribution->created_at->between($start, $end)
            );

            $weeks[] = [
                'label' => $start->format('d/m'),
                'total' => (int) $inWeek->sum('amount'),
                'count' => $inWeek->count(),
            ];
        }

        return $weeks;
    }

    public function update(UpdateContributionRequest $request, Contribution $contribution): RedirectResponse
    {
        $contribution->update($request->safe()->only(['name', 'amount']));

        $contribution->logEvent(ContributionEventAction::Updated, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bijdrage aangepast.']);

        return back();
    }
}
