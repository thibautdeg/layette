<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ConfirmContributionAction;
use App\Http\Controllers\Controller;
use App\Models\Contribution;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ContributionConfirmationController extends Controller
{
    /**
     * Manually confirm a contribution, e.g. when someone hands over cash.
     */
    public function store(Contribution $contribution, ConfirmContributionAction $confirmContribution): RedirectResponse
    {
        $confirmContribution->handle($contribution);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bijdrage bevestigd.']);

        return back();
    }
}
