<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelContributionAction;
use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ContributionCancellationController extends Controller
{
    /**
     * Cancel a contribution that will no longer arrive, freeing up the amount on the gift.
     */
    public function store(#[CurrentUser] User $user, Contribution $contribution, CancelContributionAction $cancelContribution): RedirectResponse
    {
        $cancelContribution->handle($contribution, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bijdrage geannuleerd.']);

        return back();
    }
}
