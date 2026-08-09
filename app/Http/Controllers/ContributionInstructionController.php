<?php

namespace App\Http\Controllers;

use App\Actions\GeneratePaymentQrAction;
use App\Models\Contribution;
use App\Models\GiftList;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContributionInstructionController extends Controller
{
    public function view(Request $request, Contribution $contribution, GeneratePaymentQrAction $paymentQr): Response
    {
        abort_unless($this->mayView($request, $contribution), 403);

        $giftList = $contribution->gift->giftList ?? GiftList::current();

        return Inertia::render('list/Instructions', [
            'contribution' => [
                'reference' => $contribution->reference,
                'type' => $contribution->type->value,
                'name' => $contribution->name,
                'amount' => $contribution->amount,
                'status' => $contribution->status->value,
                'status_label' => $contribution->status->donorLabel(),
            ],
            'gift' => [
                'title' => $contribution->gift->title ?? null,
                'is_free' => $contribution->isFree(),
            ],
            'payment' => [
                'iban' => $giftList->iban,
                'account_holder' => $giftList->account_holder,
                'wero_phone' => $giftList->wero_phone,
                'qr_svg' => $contribution->isPurchase() ? null : $paymentQr->handle($giftList, $contribution),
            ],
            'listSlug' => $giftList->slug,
        ]);
    }

    protected function mayView(Request $request, Contribution $contribution): bool
    {
        if ($request->hasValidSignature()) {
            return true;
        }

        return $contribution->user_id === $request->user()?->id;
    }
}
