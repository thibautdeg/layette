<?php

use App\Enums\Role;
use App\Http\Controllers\Admin\BankStatementImportController;
use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\BankTransactionIgnoreController;
use App\Http\Controllers\Admin\BankTransactionMatchController;
use App\Http\Controllers\Admin\ContributionCancellationController;
use App\Http\Controllers\Admin\ContributionConfirmationController;
use App\Http\Controllers\Admin\ContributionController;
use App\Http\Controllers\Admin\GiftController;
use App\Http\Controllers\Admin\GiftListSettingController;
use App\Http\Controllers\Admin\GiftOrderController;
use App\Http\Controllers\Admin\GiftVisibilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:'.Role::Admin->value])->prefix('beheer')->name('admin.')->group(function () {
    Route::redirect('/', '/beheer/bijdragen');

    Route::get('cadeaus', [GiftController::class, 'index'])->name('gifts.index');
    Route::post('cadeaus', [GiftController::class, 'store'])->name('gifts.store');
    Route::patch('cadeaus/volgorde', [GiftOrderController::class, 'update'])->name('gifts.order.update');
    Route::post('cadeaus/{gift}', [GiftController::class, 'update'])->name('gifts.update');
    Route::delete('cadeaus/{gift}', [GiftController::class, 'destroy'])->name('gifts.destroy');
    Route::patch('cadeaus/{gift}/zichtbaarheid', [GiftVisibilityController::class, 'update'])->name('gifts.visibility.update');

    Route::get('instellingen', [GiftListSettingController::class, 'edit'])->name('settings.edit');
    Route::post('instellingen', [GiftListSettingController::class, 'update'])->name('settings.update');

    Route::get('bijdragen', [ContributionController::class, 'index'])->name('contributions.index');
    Route::patch('bijdragen/{contribution}', [ContributionController::class, 'update'])->name('contributions.update');
    Route::post('bijdragen/{contribution}/bevestigen', [ContributionConfirmationController::class, 'store'])->name('contributions.confirm');
    Route::post('bijdragen/{contribution}/annuleren', [ContributionCancellationController::class, 'store'])->name('contributions.cancel');

    Route::get('bank', [BankTransactionController::class, 'index'])->name('bank.index');
    Route::post('bank/import', [BankStatementImportController::class, 'store'])->name('bank.import');
    Route::post('bank/{bankTransaction}/koppelen', [BankTransactionMatchController::class, 'store'])->name('bank.match');
    Route::post('bank/{bankTransaction}/negeren', [BankTransactionIgnoreController::class, 'store'])->name('bank.ignore');
});
