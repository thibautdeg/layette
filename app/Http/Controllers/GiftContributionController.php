<?php

namespace App\Http\Controllers;

use App\Actions\CreateContributionAction;
use App\Enums\Role;
use App\Http\Requests\StoreGiftContributionRequest;
use App\Mail\ContributionReceivedMail;
use App\Models\Gift;
use App\Models\GiftList;
use App\Models\User;
use App\Notifications\ContributionCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GiftContributionController extends Controller
{
    public function create(GiftList $giftList, Gift $gift): Response
    {
        abort_if($gift->isHidden(), 404);

        $gift->load('contributions');

        return Inertia::render('list/Contribute', [
            'giftList' => [
                'slug' => $giftList->slug,
                'title' => $giftList->title,
                'is_closed' => $giftList->isClosed(),
            ],
            'gift' => [
                'id' => $gift->id,
                'title' => $gift->title,
                'description' => $gift->description,
                'price' => $gift->price,
                'image_url' => $gift->image_path !== null ? Storage::disk(config('filesystems.uploads'))->url($gift->image_path) : null,
                'remaining' => $gift->remainingAmount(),
                'allows_partial_contributions' => $gift->allows_partial_contributions,
                'allows_purchase' => $gift->allows_purchase && ! $gift->hasActiveContributions(),
                'is_available' => $gift->isAvailable() && ! $giftList->isClosed(),
            ],
        ]);
    }

    public function store(StoreGiftContributionRequest $request, GiftList $giftList, Gift $gift, CreateContributionAction $createContribution): RedirectResponse
    {
        $contribution = $createContribution->handle($gift, $request->user(), $request->fromRequest());

        defer(function () use ($contribution): void {
            Mail::to($contribution->user->email)->send(new ContributionReceivedMail($contribution));

            Notification::send(
                User::query()->whereHasRole(Role::Admin->value)->get(),
                new ContributionCreatedNotification($contribution),
            );
        });

        return to_route('contribution.instructions', ['contribution' => $contribution->reference]);
    }
}
