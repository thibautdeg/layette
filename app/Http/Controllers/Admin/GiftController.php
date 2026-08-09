<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGiftRequest;
use App\Http\Requests\Admin\UpdateGiftRequest;
use App\Models\Gift;
use App\Models\GiftList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GiftController extends Controller
{
    public function index(): Response
    {
        $giftList = GiftList::current();

        $gifts = Gift::query()
            ->whereBelongsTo($giftList)
            ->ordered()
            ->with('contributions')
            ->get();

        return Inertia::render('admin/Gifts', [
            'gifts' => $gifts->map(fn (Gift $gift): array => [
                'id' => $gift->id,
                'title' => $gift->title,
                'description' => $gift->description,
                'price' => $gift->price,
                'image_url' => $gift->image_path !== null ? Storage::disk('public')->url($gift->image_path) : null,
                'shop_url' => $gift->shop_url,
                'allows_partial_contributions' => $gift->allows_partial_contributions,
                'allows_purchase' => $gift->allows_purchase,
                'is_hidden' => $gift->isHidden(),
                'status' => $gift->status()->value,
                'status_label' => $gift->status()->label(),
                'pledged' => $gift->pledgedAmount(),
                'confirmed' => $gift->confirmedAmount(),
                'contribution_count' => $gift->activeContributions()->count(),
            ])->values(),
        ]);
    }

    public function store(StoreGiftRequest $request): RedirectResponse
    {
        $giftList = GiftList::current();

        $gift = new Gift($request->safe()->except('image'));
        $gift->giftList()->associate($giftList);
        $gift->position = ($giftList->gifts()->max('position') ?? 0) + 1;

        if ($request->hasFile('image')) {
            $gift->image_path = $request->file('image')->store('gifts', 'public') ?: null;
        }

        $gift->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cadeau toegevoegd.']);

        return back();
    }

    public function update(UpdateGiftRequest $request, Gift $gift): RedirectResponse
    {
        $gift->fill($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            if ($gift->image_path !== null) {
                Storage::disk('public')->delete($gift->image_path);
            }

            $gift->image_path = $request->file('image')->store('gifts', 'public') ?: null;
        }

        $gift->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cadeau aangepast.']);

        return back();
    }

    public function destroy(Gift $gift): RedirectResponse
    {
        if ($gift->image_path !== null) {
            Storage::disk('public')->delete($gift->image_path);
        }

        $gift->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cadeau verwijderd.']);

        return back();
    }
}
