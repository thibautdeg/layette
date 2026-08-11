<?php

use App\Mail\ContributionReceivedMail;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\GiftList;
use Inertia\Testing\AssertableInertia as Assert;

test('the IBAN is formatted in groups of four', function () {
    $giftList = GiftList::factory()->create(['iban' => 'be68539007547034']);

    expect($giftList->formattedIban())->toBe('BE68 5390 0754 7034');
});

test('an already spaced IBAN is not regrouped twice', function () {
    $giftList = GiftList::factory()->create(['iban' => 'BE68 5390 0754 7034']);

    expect($giftList->formattedIban())->toBe('BE68 5390 0754 7034');
});

test('a list without an account number has no formatted IBAN', function () {
    $giftList = GiftList::factory()->create(['iban' => null]);

    expect($giftList->formattedIban())->toBeNull();
});

test('the received mail leads with the three payment details', function () {
    $giftList = GiftList::factory()->create(['iban' => 'BE68539007547034']);
    $gift = Gift::factory()->for($giftList)->create(['title' => 'Kinderwagen']);
    $contribution = Contribution::factory()->for($gift)->create(['amount' => 4000]);

    $rendered = (new ContributionReceivedMail($contribution))->render();

    expect($rendered)
        ->toContain('BE68 5390 0754 7034')
        ->toContain('40,00')
        ->toContain($contribution->reference)
        ->toContain('Rekeningnummer')
        ->toContain('Mededeling');
});

test('the received mail no longer tells mobile readers to save the QR code', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create();
    $contribution = Contribution::factory()->for($gift)->create();

    $rendered = (new ContributionReceivedMail($contribution))->render();

    expect($rendered)
        ->not->toContain('Sla de QR-code')
        ->not->toContain('Bekijk de betaalinstructies');
});

test('the instruction page shows the formatted IBAN', function () {
    $user = donor();
    $giftList = GiftList::factory()->create(['iban' => 'BE68539007547034']);
    $gift = Gift::factory()->for($giftList)->create();
    $contribution = Contribution::factory()->for($user)->for($gift)->create();

    $this->actingAs($user)
        ->get(route('contribution.instructions', ['contribution' => $contribution->reference]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('payment.iban', 'BE68 5390 0754 7034')
        );
});
