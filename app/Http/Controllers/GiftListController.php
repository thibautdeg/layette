<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\GiftList;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GiftListController extends Controller
{
    public function view(GiftList $giftList): Response
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
                'intro' => $giftList->intro,
                'photo_url' => $giftList->photo_path !== null ? Storage::disk('public')->url($giftList->photo_path) : null,
                'expected_at' => $giftList->expected_at?->toDateString(),
                'is_closed' => $giftList->isClosed(),
            ],
            'gifts' => $available->concat($unavailable)
                ->map(fn (Gift $gift): array => $this->giftPayload($gift, $giftList->isClosed()))
                ->values(),
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
            'image_url' => $gift->image_path !== null ? Storage::disk('public')->url($gift->image_path) : null,
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
