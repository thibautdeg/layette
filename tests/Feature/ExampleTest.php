<?php

use App\Models\GiftList;

test('the home page redirects to the gift list', function () {
    $giftList = GiftList::factory()->create();

    $this->get(route('home'))
        ->assertRedirect(route('list.view', ['giftList' => $giftList->slug]));
});

test('the home page returns a 404 when there is no gift list yet', function () {
    $this->get(route('home'))->assertNotFound();
});
