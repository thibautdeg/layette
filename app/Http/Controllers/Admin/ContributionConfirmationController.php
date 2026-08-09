<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ConfirmContributionAction;
use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ContributionConfirmationController extends Controller
{
    /**
     * Manually confirm a contribution, e.g. when someone hands over cash.
     */
    public function store(#[CurrentUser] User $user, Contribution $contribution, ConfirmContributionAction $confirmContribution): RedirectResponse
    {
        $confirmContribution->handle($contribution, null, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bijdrage bevestigd.']);

        return back();
    }
}
