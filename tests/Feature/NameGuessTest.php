<?php

use App\Models\GiftList;
use App\Models\NameGuess;
use Inertia\Testing\AssertableInertia as Assert;

test('a logged in visitor can guess the baby name', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();

    $this->actingAs($user)
        ->post(route('list.guess.store', ['giftList' => $giftList->slug]), [
            'name' => 'nora',
        ])->assertRedirect();

    $dbGuess = NameGuess::query()->sole();

    expect($dbGuess->name)->toBe('Nora')
        ->and($dbGuess->user_id)->toBe($user->id);
});

test('a new guess replaces the previous one', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();

    $this->actingAs($user)
        ->post(route('list.guess.store', ['giftList' => $giftList->slug]), ['name' => 'Nora']);
    $this->post(route('list.guess.store', ['giftList' => $giftList->slug]), ['name' => 'Lou']);

    expect(NameGuess::query()->count())->toBe(1)
        ->and(NameGuess::query()->sole()->name)->toBe('Lou');
});

test('guests cannot guess but do see the aggregated guesses', function () {
    $giftList = GiftList::factory()->create();
    NameGuess::factory()->for(donor())->create(['name' => 'Nora']);
    NameGuess::factory()->for(donor())->create(['name' => 'Nora']);
    NameGuess::factory()->for(donor())->create(['name' => 'Lou']);

    $this->post(route('list.guess.store', ['giftList' => $giftList->slug]), ['name' => 'Piet'])
        ->assertRedirect(route('login'));

    $this->get(route('list.view', ['giftList' => $giftList->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('nameGuesses', 2)
            ->where('nameGuesses.0.name', 'Nora')
            ->where('nameGuesses.0.count', 2)
            ->where('myNameGuess', null)
        );
});

test('the list shows your own guess', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();
    NameGuess::factory()->for($user)->create(['name' => 'Nora']);

    $this->actingAs($user)
        ->get(route('list.view', ['giftList' => $giftList->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('myNameGuess', 'Nora')
        );
});

test('weird characters are refused', function () {
    $giftList = GiftList::factory()->create();

    $this->actingAs(donor())
        ->post(route('list.guess.store', ['giftList' => $giftList->slug]), [
            'name' => 'DROP TABLE;',
        ])->assertSessionHasErrors('name');
});

test('admins see who guessed what', function () {
    $this->actingAs(admin());
    GiftList::factory()->create();
    $guesser = donor();
    NameGuess::factory()->for($guesser)->create(['name' => 'Nora']);

    $this->get(route('admin.contributions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('nameGuesses', 1)
            ->where('nameGuesses.0.name', 'Nora')
            ->where('nameGuesses.0.by', $guesser->name)
        );
});
