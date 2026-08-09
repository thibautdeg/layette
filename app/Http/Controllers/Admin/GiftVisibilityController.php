<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gift;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class GiftVisibilityController extends Controller
{
    /**
     * Toggle whether the gift is visible on the public list.
     */
    public function update(Gift $gift): RedirectResponse
    {
        $gift->hidden_at = $gift->isHidden() ? null : now();
        $gift->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $gift->isHidden() ? 'Cadeau verborgen.' : 'Cadeau opnieuw zichtbaar.',
        ]);

        return back();
    }
}
