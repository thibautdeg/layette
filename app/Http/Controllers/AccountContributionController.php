<?php

namespace App\Http\Controllers;

use App\Actions\CancelContributionAction;
use App\Http\Requests\DestroyAccountContributionRequest;
use App\Http\Requests\UpdateAccountContributionRequest;
use App\Models\Contribution;
use App\Models\GiftList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountContributionController extends Controller
{
    public function index(Request $request): Response
    {
        $giftList = GiftList::current();

        $contributions = $request->user()->contributions()
            ->with('gift.contributions')
            ->latest()
            ->get();

        return Inertia::render('account/Contributions', [
            'contributions' => $contributions->map(fn (Contribution $contribution): array => [
                'id' => $contribution->id,
                'reference' => $contribution->reference,
                'type' => $contribution->type->value,
                'type_label' => $contribution->type->label(),
                'amount' => $contribution->amount,
                'message' => $contribution->message,
                'status' => $contribution->status->value,
                'status_label' => $contribution->status->donorLabel(),
                'is_editable' => $contribution->isPending(),
                'created_at' => $contribution->created_at?->toDateString(),
                'gift' => [
                    'title' => $contribution->gift->title,
                    'is_full' => $contribution->gift->isFull(),
                ],
            ])->values(),
            'payment' => [
                'iban' => $giftList->iban,
                'account_holder' => $giftList->account_holder,
                'wero_phone' => $giftList->wero_phone,
            ],
            'listSlug' => $giftList->slug,
        ]);
    }

    public function update(UpdateAccountContributionRequest $request, Contribution $contribution): RedirectResponse
    {
        $contribution->update($request->safe()->only(['message']));

        return back()->with('status', 'contribution-updated');
    }

    public function destroy(DestroyAccountContributionRequest $request, Contribution $contribution, CancelContributionAction $cancelContribution): RedirectResponse
    {
        $cancelContribution->handle($contribution);

        return back()->with('status', 'contribution-cancelled');
    }
}
