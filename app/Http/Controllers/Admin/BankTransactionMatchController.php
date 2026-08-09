<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ConfirmContributionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBankTransactionMatchRequest;
use App\Models\BankTransaction;
use App\Models\Contribution;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BankTransactionMatchController extends Controller
{
    /**
     * Manually link an unmatched transaction to a contribution, confirming it.
     */
    public function store(StoreBankTransactionMatchRequest $request, BankTransaction $bankTransaction, ConfirmContributionAction $confirmContribution): RedirectResponse
    {
        $contribution = Contribution::query()->findOrFail($request->integer('contribution_id'));

        $bankTransaction->ignored_at = null;

        $confirmContribution->handle($contribution, $bankTransaction, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Gekoppeld aan {$contribution->reference}."]);

        return back();
    }
}
