<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelContributionAction;
use App\Http\Controllers\Controller;
use App\Models\Contribution;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ContributionCancellationController extends Controller
{
    /**
     * Cancel a contribution that will no longer arrive, freeing up the amount on the gift.
     */
    public function store(Contribution $contribution, CancelContributionAction $cancelContribution): RedirectResponse
    {
        $cancelContribution->handle($contribution);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bijdrage geannuleerd.']);

        return back();
    }
}
