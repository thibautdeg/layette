<?php

use App\Actions\GeneratePaymentQrAction;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\GiftList;
use Inertia\Testing\AssertableInertia as Assert;

test('the instruction page includes a payment QR code', function () {
    $user = donor();
    $giftList = GiftList::factory()->create(['iban' => 'BE68 5390 0754 7034', 'account_holder' => 'Voornaam Achternaam']);
    $gift = Gift::factory()->for($giftList)->create();
    $contribution = Contribution::factory()->for($user)->for($gift)->create(['amount' => 2500]);

    $this->actingAs($user)
        ->get(route('contribution.instructions', ['contribution' => $contribution->reference]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('list/Instructions')
            ->where('payment.qr_svg', fn (?string $svg) => $svg !== null && str_contains($svg, '<svg'))
        );
});

test('a purchase contribution gets no payment QR code', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create();
    $contribution = Contribution::factory()->for($user)->for($gift)->purchase()->create();

    $this->actingAs($user)
        ->get(route('contribution.instructions', ['contribution' => $contribution->reference]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('payment.qr_svg', null)
        );
});

test('the QR payload follows the EPC standard', function () {
    $giftList = GiftList::factory()->create(['iban' => 'be68 5390 0754 7034', 'account_holder' => 'Voornaam Achternaam']);
    $gift = Gift::factory()->for($giftList)->create();
    $contribution = Contribution::factory()->for($gift)->create(['amount' => 2550]);

    $payload = (new GeneratePaymentQrAction)->payload($giftList, $contribution);

    expect($payload)->toBe(implode("\n", [
        'BCD',
        '002',
        '2',
        'SCT',
        '',
        'Voornaam Achternaam',
        'BE68539007547034',
        'EUR25.50',
        '',
        '',
        $contribution->reference,
    ]));
});

test('no QR code is generated without an IBAN or account holder', function () {
    $giftList = GiftList::factory()->create(['iban' => null]);
    $gift = Gift::factory()->for($giftList)->create();
    $contribution = Contribution::factory()->for($gift)->create(['amount' => 2500]);

    expect((new GeneratePaymentQrAction)->handle($giftList, $contribution))->toBeNull();

    $giftList = GiftList::factory()->create(['account_holder' => null]);

    expect((new GeneratePaymentQrAction)->handle($giftList, $contribution))->toBeNull();
});
