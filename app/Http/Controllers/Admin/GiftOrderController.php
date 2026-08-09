<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGiftOrderRequest;
use App\Models\Gift;
use Illuminate\Http\RedirectResponse;

class GiftOrderController extends Controller
{
    public function update(UpdateGiftOrderRequest $request): RedirectResponse
    {
        foreach ($request->array('ids') as $position => $id) {
            Gift::query()->whereKey($id)->update(['position' => $position + 1]);
        }

        return back();
    }
}
