<?php

use App\Models\BankTransaction;
use App\Models\Contribution;
use App\Models\GiftList;
use Inertia\Testing\AssertableInertia as Assert;

test('the overview lists all contributions with their status', function () {
    $this->actingAs(admin());
    Contribution::factory()->create();
    Contribution::factory()->confirmed()->create();

    $this->get(route('admin.contributions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Contributions')
            ->has('contributions', 2)
            ->has('stats.funding_percentage')
            ->has('stats.contribution_count')
            ->has('weekly', 8)
        );
});

test('contributions open for more than a week are marked as stale', function () {
    $this->actingAs(admin());
    $contribution = Contribution::factory()->create();
    $contribution->created_at = now()->subDays(10);
    $contribution->save();

    $this->get(route('admin.contributions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contributions.0.is_stale', true)
        );
});

test('an admin can confirm a contribution manually', function () {
    $this->actingAs(admin());
    $contribution = Contribution::factory()->create();

    $this->post(route('admin.contributions.confirm', ['contribution' => $contribution]))->assertRedirect();

    $dbContribution = Contribution::find($contribution->id);

    expect($dbContribution->isConfirmed())->toBeTrue()
        ->and($dbContribution->confirmed_at)->not->toBeNull();
});

test('cancelling a contribution releases its bank transaction', function () {
    $this->actingAs(admin());
    $contribution = Contribution::factory()->confirmed()->create();
    $transaction = BankTransaction::factory()->create();
    $transaction->contribution()->associate($contribution)->save();

    $this->post(route('admin.contributions.cancel', ['contribution' => $contribution]))->assertRedirect();

    $dbContribution = Contribution::find($contribution->id);
    $dbTransaction = BankTransaction::find($transaction->id);

    expect($dbContribution->isCancelled())->toBeTrue()
        ->and($dbTransaction->contribution_id)->toBeNull();
});

test('an admin can adjust the amount of a contribution', function () {
    $this->actingAs(admin());
    $contribution = Contribution::factory()->create(['amount' => 5000]);

    $this->patch(route('admin.contributions.update', ['contribution' => $contribution]), [
        'name' => $contribution->name,
        'amount' => 4500,
    ])->assertRedirect();

    expect(Contribution::find($contribution->id)->amount)->toBe(4500);
});

test('admins are warned when the payment details are still missing', function () {
    $this->actingAs(admin());
    GiftList::factory()->create(['iban' => null, 'wero_phone' => null]);

    $this->get(route('admin.contributions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('paymentDetailsMissing', true)
        );
});

test('the warning disappears once payment details are filled in', function () {
    $this->actingAs(admin());
    GiftList::factory()->create(['iban' => 'BE68539007547034']);

    $this->get(route('admin.contributions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('paymentDetailsMissing', false)
        );
});
