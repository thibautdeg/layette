<?php

use App\Http\Controllers\AccountContributionController;
use App\Http\Controllers\ContributionInstructionController;
use App\Http\Controllers\GiftContributionController;
use App\Http\Controllers\GiftListController;
use App\Http\Middleware\PreventSearchIndexing;
use App\Models\GiftList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $giftList = GiftList::query()->first();

    abort_if($giftList === null, 404);

    return to_route('list.view', ['giftList' => $giftList->slug]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        return $request->user()->isAdmin()
            ? to_route('admin.contributions.index')
            : to_route('account.contributions');
    })->name('dashboard');
});

Route::middleware(PreventSearchIndexing::class)->group(function () {
    Route::get('lijst/{giftList:slug}', [GiftListController::class, 'view'])->name('list.view');

    Route::scopeBindings()->middleware('auth')->group(function () {
        Route::get('lijst/{giftList:slug}/cadeau/{gift}', [GiftContributionController::class, 'create'])->name('list.contribute.create');
        Route::post('lijst/{giftList:slug}/cadeau/{gift}', [GiftContributionController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('list.contribute.store');
    });

    Route::get('bijdrage/{contribution:reference}', [ContributionInstructionController::class, 'view'])->name('contribution.instructions');

    Route::middleware('auth')->group(function () {
        Route::redirect('mijn', '/mijn/bijdragen');

        Route::get('mijn/bijdragen', [AccountContributionController::class, 'index'])->name('account.contributions');
        Route::patch('mijn/bijdragen/{contribution}', [AccountContributionController::class, 'update'])->name('account.contributions.update');
        Route::delete('mijn/bijdragen/{contribution}', [AccountContributionController::class, 'destroy'])->name('account.contributions.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
