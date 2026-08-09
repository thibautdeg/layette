<?php

use App\Mail\ContributionReceivedMail;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\GiftList;
use App\Notifications\ContributionCreatedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('guests are sent to the login page with context before they can contribute', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create();

    $this->get(route('list.contribute.create', ['giftList' => $giftList->slug, 'gift' => $gift]))
        ->assertRedirect(route('login', ['bedoeling' => 'bijdragen']));

    $this->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
        'type' => 'contribution',
        'amount' => 1000,
    ])->assertRedirect(route('login', ['bedoeling' => 'bijdragen']));
});

test('after logging in the donor continues to the gift they wanted to contribute to', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create();

    $contributeUrl = route('list.contribute.create', ['giftList' => $giftList->slug, 'gift' => $gift]);

    $this->get($contributeUrl);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($contributeUrl);
});

test('a registered donor can announce a contribution to a gift', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);

    $response = $this->actingAs($user)
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 2500,
            'message' => 'Veel liefs!',
        ]);

    $dbContribution = Contribution::query()->sole();

    $response->assertRedirect(route('contribution.instructions', ['contribution' => $dbContribution->reference]));

    expect($dbContribution->user_id)->toBe($user->id)
        ->and($dbContribution->name)->toBe($user->name)
        ->and($dbContribution->amount)->toBe(2500)
        ->and($dbContribution->isPending())->toBeTrue()
        ->and($dbContribution->reference)->toStartWith('BL-');
});

test('a donor can give together with someone', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);

    $this->actingAs($user)
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 2500,
            'together_with' => 'Marcel',
        ])->assertRedirect();

    $dbContribution = Contribution::query()->sole();

    expect($dbContribution->together_with)->toBe('Marcel')
        ->and($dbContribution->displayName())->toBe("{$user->name} & Marcel");
});

test('a registered donor can reserve a gift to buy it themselves', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create();

    $this->actingAs($user)
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'purchase',
        ])->assertRedirect();

    $dbGift = Gift::query()->with('contributions')->find($gift->id);

    expect($dbGift->isReserved())->toBeTrue()
        ->and($dbGift->contributions->sole()->amount)->toBeNull();
});

test('a gift with contributions can no longer be bought outright', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);
    Contribution::factory()->for($gift)->create(['amount' => 1000]);

    $this->actingAs(donor())
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'purchase',
        ])->assertSessionHasErrors('gift');
});

test('nobody can contribute to a reserved gift', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);
    Contribution::factory()->for($gift)->purchase()->create();

    $this->actingAs(donor())
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 1000,
        ])->assertSessionHasErrors('gift');
});

test('the amount is fixed when partial contributions are not allowed', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->fixedAmount()->create(['price' => 7500]);

    $this->actingAs(donor())
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 5000,
        ])->assertSessionHasErrors('amount');

    $this->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
        'type' => 'contribution',
        'amount' => 7500,
    ])->assertSessionDoesntHaveErrors()->assertRedirect();
});

test('pending contributions block a fully funded gift for new donors', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);
    Contribution::factory()->for($gift)->create(['amount' => 10000]);

    $this->actingAs(donor())
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 1000,
        ])->assertSessionHasErrors('gift');
});

test('giving more than what remains is refused', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);
    Contribution::factory()->for($gift)->create(['amount' => 4000]);

    $this->actingAs(donor())
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 7000,
        ])->assertSessionHasErrors('amount');
});

test('giving exactly what remains is allowed', function () {
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);
    Contribution::factory()->for($gift)->create(['amount' => 4000]);

    $this->actingAs(donor())
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 6000,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();
});

test('a closed list refuses new contributions', function () {
    $giftList = GiftList::factory()->closed()->create();
    $gift = Gift::factory()->for($giftList)->create();

    $this->actingAs(donor())
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 1000,
        ])->assertSessionHasErrors('gift');
});

test('the donor receives a confirmation mail and the admins a notification', function () {
    Mail::fake();
    Notification::fake();

    $adminUser = admin();
    $user = donor();
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);

    $this->actingAs($user)
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 2500,
        ]);

    Mail::assertSent(ContributionReceivedMail::class, fn (ContributionReceivedMail $mail) => $mail->hasTo($user->email));
    Notification::assertSentTo($adminUser, ContributionCreatedNotification::class);
    Notification::assertNotSentTo($user, ContributionCreatedNotification::class);
});

test('the instruction page is visible for the donor who contributed', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);

    $this->actingAs($user)
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 2500,
        ]);

    $contribution = Contribution::query()->sole();

    $this->get(route('contribution.instructions', ['contribution' => $contribution->reference]))
        ->assertSuccessful();
});

test('the instruction page is not visible to others without a signed link', function () {
    $contribution = Contribution::factory()->create();

    $this->get(route('contribution.instructions', ['contribution' => $contribution->reference]))
        ->assertForbidden();

    $this->actingAs(donor())
        ->get(route('contribution.instructions', ['contribution' => $contribution->reference]))
        ->assertForbidden();

    $this->get(URL::signedRoute('contribution.instructions', ['contribution' => $contribution->reference]))
        ->assertSuccessful();
});
