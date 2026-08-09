<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContributionRequest;
use App\Models\Contribution;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    public function index(): Response
    {
        $contributions = Contribution::query()
            ->with(['gift', 'user', 'bankTransaction'])
            ->latest()
            ->get();

        return Inertia::render('admin/Contributions', [
            'contributions' => $contributions->map(fn (Contribution $contribution): array => [
                'id' => $contribution->id,
                'reference' => $contribution->reference,
                'type' => $contribution->type->value,
                'type_label' => $contribution->type->label(),
                'name' => $contribution->name,
                'email' => $contribution->user->email,
                'amount' => $contribution->amount,
                'message' => $contribution->message,
                'status' => $contribution->status->value,
                'status_label' => $contribution->status->label(),
                'is_stale' => $contribution->isStale(),
                'days_open' => $contribution->isPending() ? (int) $contribution->created_at?->diffInDays(now()) : null,
                'created_at' => $contribution->created_at?->toDateString(),
                'confirmed_at' => $contribution->confirmed_at?->toDateString(),
                'gift_title' => $contribution->gift->title,
                'transaction' => $contribution->bankTransaction !== null ? [
                    'amount' => $contribution->bankTransaction->amount,
                    'booked_at' => $contribution->bankTransaction->booked_at->toDateString(),
                    'counterparty_name' => $contribution->bankTransaction->counterparty_name,
                ] : null,
            ])->values(),
        ]);
    }

    public function update(UpdateContributionRequest $request, Contribution $contribution): RedirectResponse
    {
        $contribution->update($request->safe()->only(['name', 'amount']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bijdrage aangepast.']);

        return back();
    }
}
