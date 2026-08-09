<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGiftListRequest;
use App\Models\GiftList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GiftListSettingController extends Controller
{
    public function edit(): Response
    {
        $giftList = GiftList::current();

        return Inertia::render('admin/Settings', [
            'giftList' => [
                'slug' => $giftList->slug,
                'url' => route('list.view', ['giftList' => $giftList->slug]),
                'title' => $giftList->title,
                'baby_name' => $giftList->baby_name,
                'baby_gender' => $giftList->baby_gender?->value,
                'intro' => $giftList->intro,
                'photo_url' => $giftList->photo_path !== null ? Storage::disk(config('filesystems.uploads'))->url($giftList->photo_path) : null,
                'iban' => $giftList->iban,
                'account_holder' => $giftList->account_holder,
                'wero_phone' => $giftList->wero_phone,
                'expected_at' => $giftList->expected_at?->toDateString(),
                'is_closed' => $giftList->isClosed(),
            ],
        ]);
    }

    public function update(UpdateGiftListRequest $request): RedirectResponse
    {
        $giftList = GiftList::current();

        $giftList->fill($request->safe()->except(['photo', 'is_closed']));

        $giftList->closed_at = $request->boolean('is_closed')
            ? ($giftList->closed_at ?? now())
            : null;

        if ($request->hasFile('photo')) {
            if ($giftList->photo_path !== null) {
                Storage::disk(config('filesystems.uploads'))->delete($giftList->photo_path);
            }

            $giftList->photo_path = $request->file('photo')->store('list', config('filesystems.uploads')) ?: null;
        }

        $giftList->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Instellingen bewaard.']);

        return back();
    }
}
