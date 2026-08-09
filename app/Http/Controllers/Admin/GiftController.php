<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGiftRequest;
use App\Http\Requests\Admin\UpdateGiftRequest;
use App\Models\Gift;
use App\Models\GiftList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $gift = new Gift($request->safe()->except(['image', 'image_url']));
        $gift->giftList()->associate($giftList);
        $gift->position = ($giftList->gifts()->max('position') ?? 0) + 1;

        if ($request->hasFile('image')) {
            $gift->image_path = $request->file('image')->store('gifts', 'public') ?: null;
        } elseif ($request->filled('image_url')) {
            $gift->image_path = $this->downloadImage($request->string('image_url')->toString());
        }

        $gift->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cadeau toegevoegd.']);

        return back();
    }

    public function update(UpdateGiftRequest $request, Gift $gift): RedirectResponse
    {
        $gift->fill($request->safe()->except(['image', 'image_url']));

        if ($request->hasFile('image')) {
            if ($gift->image_path !== null) {
                Storage::disk('public')->delete($gift->image_path);
            }

            $gift->image_path = $request->file('image')->store('gifts', 'public') ?: null;
        } elseif ($request->filled('image_url')) {
            $downloaded = $this->downloadImage($request->string('image_url')->toString());

            if ($downloaded !== null) {
                if ($gift->image_path !== null) {
                    Storage::disk('public')->delete($gift->image_path);
                }

                $gift->image_path = $downloaded;
            }
        }

        $gift->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cadeau aangepast.']);

        return back();
    }

    /**
     * Download a product photo from a webshop and store it on the public disk.
     */
    protected function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Geboortelijst/1.0)'])
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        $contentType = Str::before($response->header('Content-Type'), ';');

        if (! isset($extensions[$contentType])) {
            return null;
        }

        if (mb_strlen($response->body(), '8bit') > 5 * 1024 * 1024) {
            return null;
        }

        $path = 'gifts/'.Str::random(20).'.'.$extensions[$contentType];

        Storage::disk('public')->put($path, $response->body());

        return $path;
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
