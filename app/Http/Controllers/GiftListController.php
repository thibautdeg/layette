<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\GiftList;
use App\Models\NameGuess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GiftListController extends Controller
{
    public function view(Request $request, GiftList $giftList): Response
    {
        $gifts = Gift::query()
            ->whereBelongsTo($giftList)
            ->visible()
            ->ordered()
            ->with('contributions')
            ->get();

        [$unavailable, $available] = $gifts->partition(
            fn (Gift $gift): bool => ! $gift->isAvailable() || $giftList->isClosed()
        );

        return Inertia::render('list/Show', [
            'giftList' => [
                'slug' => $giftList->slug,
                'title' => $giftList->title,
                'baby_name' => $giftList->baby_name,
                'baby_gender' => $giftList->baby_gender !== null ? [
                    'label' => $giftList->baby_gender->label(),
                    'emoji' => $giftList->baby_gender->emoji(),
                ] : null,
                'intro' => $giftList->intro,
                'photo_url' => $giftList->photo_path !== null ? Storage::disk(config('filesystems.uploads'))->url($giftList->photo_path) : null,
                'expected_at' => $giftList->expected_at?->toDateString(),
                'days_until_due' => $giftList->daysUntilDue(),
                'pregnancy_week' => $giftList->pregnancyWeek(),
                'is_closed' => $giftList->isClosed(),
            ],
            'gifts' => $available->concat($unavailable)
                ->map(fn (Gift $gift): array => $this->giftPayload($gift, $giftList->isClosed()))
                ->values(),
            'nameGuesses' => NameGuess::query()->get()
                ->groupBy('name')
                ->map(fn ($group, string $name): array => [
                    'name' => $name,
                    'count' => $group->count(),
                ])
                ->sortByDesc('count')
                ->values(),
            'myNameGuess' => $request->user() !== null
                ? NameGuess::query()->where('user_id', $request->user()->id)->first()?->name
                : null,
        ])->withViewData([
            'openGraph' => [
                'title' => $giftList->title,
                'description' => $giftList->intro !== null ? Str::limit($giftList->intro, 150) : null,
                'image' => $giftList->photo_path !== null ? Storage::disk(config('filesystems.uploads'))->url($giftList->photo_path) : null,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function giftPayload(Gift $gift, bool $listIsClosed): array
    {
        return [
            'id' => $gift->id,
            'title' => $gift->title,
            'description' => $gift->description,
            'price' => $gift->price,
            'image_url' => $gift->image_path !== null ? Storage::disk(config('filesystems.uploads'))->url($gift->image_path) : null,
            'shop_url' => $gift->shop_url,
            'status' => $gift->status()->value,
            'status_label' => $gift->status()->label(),
            'pledged' => min($gift->pledgedAmount(), $gift->price),
            'remaining' => $gift->remainingAmount(),
            'allows_partial_contributions' => $gift->allows_partial_contributions,
            'allows_purchase' => $gift->allows_purchase && ! $gift->hasActiveContributions(),
            'is_available' => $gift->isAvailable() && ! $listIsClosed,
        ];
    }
}
