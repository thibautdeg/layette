<?php

use App\Models\Contribution;
use App\Models\GiftList;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('a donor can give a free contribution without picking a gift', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('list.free.store', ['giftList' => $giftList->slug]), [
            'amount' => 2500,
            'message' => 'Voor de spaarpot!',
            'together_with' => 'Marcel',
        ]);

    $dbContribution = Contribution::query()->sole();

    $response->assertRedirect(route('contribution.instructions', ['contribution' => $dbContribution->reference]));

    expect($dbContribution->gift_id)->toBeNull()
        ->and($dbContribution->isFree())->toBeTrue()
        ->and($dbContribution->giftTitle())->toBe('Vrije bijdrage')
        ->and($dbContribution->amount)->toBe(2500)
        ->and($dbContribution->user_id)->toBe($user->id);
});

test('guests are sent to the login page for a free contribution', function () {
    $giftList = GiftList::factory()->create();

    $this->get(route('list.free.create', ['giftList' => $giftList->slug]))
        ->assertRedirect(route('login', ['bedoeling' => 'bijdragen']));
});

test('a closed list refuses free contributions', function () {
    $giftList = GiftList::factory()->closed()->create();

    $this->actingAs(donor())
        ->post(route('list.free.store', ['giftList' => $giftList->slug]), [
            'amount' => 2500,
        ])->assertSessionHasErrors('gift');
});

test('a free contribution has no upper limit besides the sanity cap', function () {
    $giftList = GiftList::factory()->create();

    $this->actingAs(donor())
        ->post(route('list.free.store', ['giftList' => $giftList->slug]), [
            'amount' => 50000,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();
});

test('a free contribution shows up in the admin overview as vrije bijdrage', function () {
    $this->actingAs(admin());
    GiftList::factory()->create();
    Contribution::factory()->free()->create();

    $this->get(route('admin.contributions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contributions.0.gift_title', 'Vrije bijdrage')
        );
});

test('the bank import matches a free contribution on its reference', function () {
    $this->actingAs(admin());
    $contribution = Contribution::factory()->free()->create(['amount' => 2500]);

    $csv = implode("\n", [
        'Boekingsdatum;Bedrag;Mededeling',
        "05/08/2026;25,00;{$contribution->reference}",
    ]);

    $this->post(route('admin.bank.import'), [
        'statement' => UploadedFile::fake()->createWithContent('afschrift.csv', $csv),
    ]);

    expect(Contribution::find($contribution->id)->isConfirmed())->toBeTrue();
});

test('the instruction page works for a free contribution', function () {
    GiftList::factory()->create();
    $user = donor();
    $contribution = Contribution::factory()->free()->for($user)->create();

    $this->actingAs($user)
        ->get(route('contribution.instructions', ['contribution' => $contribution->reference]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('gift.is_free', true)
        );
});
