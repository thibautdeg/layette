<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BankTransactionIgnoreController extends Controller
{
    /**
     * Mark a transaction as not relevant for the gift list.
     */
    public function store(BankTransaction $bankTransaction): RedirectResponse
    {
        $bankTransaction->ignored_at = now();
        $bankTransaction->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transactie gemarkeerd als niet relevant.']);

        return back();
    }
}
