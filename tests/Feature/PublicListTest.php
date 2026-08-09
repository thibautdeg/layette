<?php

use App\Models\Contribution;
use App\Models\Gift;
use App\Models\GiftList;
use Inertia\Testing\AssertableInertia as Assert;

test('the public list shows visible gifts', function () {
    $giftList = GiftList::factory()->create();
    $visible = Gift::factory()->for($giftList)->create(['title' => 'Kinderwagen']);
    Gift::factory()->for($giftList)->hidden()->create(['title' => 'Verborgen cadeau']);

    $this->get(route('list.view', ['giftList' => $giftList->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('list/Show')
            ->has('gifts', 1)
            ->where('gifts.0.title', 'Kinderwagen')
            ->where('gifts.0.is_available', true)
        );
});

test('an unknown slug results in a 404', function () {
    GiftList::factory()->create();

    $this->get(route('list.view', ['giftList' => 'onbestaande-slug']))->assertNotFound();
});

test('the public list is not indexable by search engines', function () {
    $giftList = GiftList::factory()->create();

    $this->get(route('list.view', ['giftList' => $giftList->slug]))
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('reserved and full gifts move to the bottom of the list', function () {
    $giftList = GiftList::factory()->create();

    $reserved = Gift::factory()->for($giftList)->create(['position' => 1]);
    $reserved->contributions()->save(Contribution::factory()->purchase()->make());
    $open = Gift::factory()->for($giftList)->create(['position' => 2]);

    $this->get(route('list.view', ['giftList' => $giftList->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('gifts.0.id', $open->id)
            ->where('gifts.1.id', $reserved->id)
            ->where('gifts.1.status', 'reserved')
        );
});

test('a closed list keeps its page but marks gifts unavailable', function () {
    $giftList = GiftList::factory()->closed()->create();
    Gift::factory()->for($giftList)->create();

    $this->get(route('list.view', ['giftList' => $giftList->slug]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('giftList.is_closed', true)
            ->where('gifts.0.is_available', false)
        );
});
