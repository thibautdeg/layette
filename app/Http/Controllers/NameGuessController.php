<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNameGuessRequest;
use App\Models\GiftList;
use App\Models\NameGuess;
use Illuminate\Http\RedirectResponse;

class NameGuessController extends Controller
{
    /**
     * Store or replace the current user's guess for the baby name.
     */
    public function store(StoreNameGuessRequest $request, GiftList $giftList): RedirectResponse
    {
        $request->user()->nameGuess()->updateOrCreate(
            [],
            ['name' => NameGuess::normalize($request->string('name')->toString())],
        );

        return back()->with('status', 'name-guessed');
    }
}
