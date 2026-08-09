<?php

use App\Models\BankTransaction;
use App\Models\Contribution;
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
