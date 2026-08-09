<?php

use App\Models\Contribution;
use App\Models\GiftList;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login page with context for the account pages', function () {
    $this->get(route('account.contributions'))
        ->assertRedirect(route('login', ['bedoeling' => 'opvolgen']));
});

test('a donor only sees their own contributions', function () {
    GiftList::factory()->create();
    $user = donor();
    $own = Contribution::factory()->for($user)->create();
    Contribution::factory()->create();

    $this->actingAs($user)
        ->get(route('account.contributions'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/Contributions')
            ->has('contributions', 1)
            ->where('contributions.0.reference', $own->reference)
        );
});

test('a donor can update the message of a pending contribution', function () {
    $user = donor();
    $contribution = Contribution::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('account.contributions.update', ['contribution' => $contribution]), [
            'message' => 'Van ons allemaal!',
        ])->assertRedirect();

    $dbContribution = Contribution::find($contribution->id);

    expect($dbContribution->message)->toBe('Van ons allemaal!');
});

test('a confirmed contribution can no longer be changed by the donor', function () {
    $user = donor();
    $contribution = Contribution::factory()->for($user)->confirmed()->create();

    $this->actingAs($user)
        ->patch(route('account.contributions.update', ['contribution' => $contribution]), [
            'message' => 'Te laat',
        ])->assertForbidden();
});

test('a donor cannot touch contributions of someone else', function () {
    $other = Contribution::factory()->create();

    $this->actingAs(donor())
        ->patch(route('account.contributions.update', ['contribution' => $other]), [
            'message' => 'Hacker',
        ])->assertForbidden();
});

test('a donor can cancel a pending contribution', function () {
    $user = donor();
    $contribution = Contribution::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('account.contributions.destroy', ['contribution' => $contribution]))
        ->assertRedirect();

    expect(Contribution::find($contribution->id)->isCancelled())->toBeTrue();
});

test('a confirmed contribution cannot be cancelled by the donor', function () {
    $user = donor();
    $contribution = Contribution::factory()->for($user)->confirmed()->create();

    $this->actingAs($user)
        ->delete(route('account.contributions.destroy', ['contribution' => $contribution]))
        ->assertForbidden();
});

test('registering through the existing flow grants the donor role', function () {
    $this->post(route('register.store'), [
        'name' => 'Tante Rita',
        'email' => 'rita@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $dbUser = User::query()->where('email', 'rita@example.com')->sole();

    expect($dbUser->hasRole('donor'))->toBeTrue()
        ->and($dbUser->isAdmin())->toBeFalse();
});
