<?php

use App\Actions\GeneratePaymentQrAction;
use App\Mail\ContributionReceivedMail;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\GiftList;
use Illuminate\Support\Facades\Mail;
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

test('the QR code can be rendered as PNG', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create();
    $contribution = Contribution::factory()->for($gift)->create(['amount' => 2500]);

    expect((new GeneratePaymentQrAction)->png($giftList, $contribution))->toStartWith("\x89PNG");

    $withoutIban = GiftList::factory()->create(['iban' => null]);

    expect((new GeneratePaymentQrAction)->png($withoutIban, $contribution))->toBeNull();
});

test('the contributions overview includes a payment QR for pending contributions only', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create();
    Contribution::factory()->for($user)->for($gift)->create(['amount' => 2500, 'created_at' => now()]);
    Contribution::factory()->for($user)->for($gift)->confirmed()->create(['amount' => 1000, 'created_at' => now()->subDay()]);
    Contribution::factory()->for($user)->for($gift)->purchase()->create(['created_at' => now()->subDays(2)]);

    $this->actingAs($user)
        ->get(route('account.contributions'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/Contributions')
            ->where('contributions.0.qr_svg', fn (?string $svg) => $svg !== null && str_contains($svg, '<svg'))
            ->where('contributions.1.qr_svg', null)
            ->where('contributions.2.qr_svg', null)
        );
});

test('the received mail embeds the payment QR code as an inline image', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create();
    $contribution = Contribution::factory()->for($gift)->create(['amount' => 2500]);

    Mail::to('gast@example.com')->send(new ContributionReceivedMail($contribution));

    $message = app('mailer')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();

    $attachments = collect($message->getAttachments())->map(fn ($attachment) => $attachment->getFilename());

    expect($attachments)->toContain("overschrijving-{$contribution->reference}.png");
});

test('the received mail for a free contribution contains the payment details', function () {
    $giftList = GiftList::factory()->create();
    $contribution = Contribution::factory()->free()->create(['amount' => 5000]);

    $rendered = (new ContributionReceivedMail($contribution))->render();

    expect($rendered)
        ->toContain($giftList->formattedIban())
        ->toContain($contribution->reference)
        ->toContain('vrije bijdrage');
});
