<?php

namespace App\Http\Controllers;

use App\Actions\CreateFreeContributionAction;
use App\Enums\Role;
use App\Http\Requests\StoreFreeContributionRequest;
use App\Mail\ContributionReceivedMail;
use App\Models\GiftList;
use App\Models\User;
use App\Notifications\ContributionCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class FreeContributionController extends Controller
{
    public function create(GiftList $giftList): Response
    {
        return Inertia::render('list/ContributeFree', [
            'giftList' => [
                'slug' => $giftList->slug,
                'title' => $giftList->title,
                'is_closed' => $giftList->isClosed(),
            ],
        ]);
    }

    public function store(StoreFreeContributionRequest $request, GiftList $giftList, CreateFreeContributionAction $createFreeContribution): RedirectResponse
    {
        $contribution = $createFreeContribution->handle($giftList, $request->user(), $request->fromRequest());

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
