<?php

use App\Enums\ContributionEventAction;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\GiftList;
use Illuminate\Http\UploadedFile;

test('announcing a contribution is logged with the donor as actor', function () {
    $user = donor();
    $giftList = GiftList::factory()->create();
    $gift = Gift::factory()->for($giftList)->create(['price' => 10000]);

    $this->actingAs($user)
        ->post(route('list.contribute.store', ['giftList' => $giftList->slug, 'gift' => $gift]), [
            'type' => 'contribution',
            'amount' => 2500,
        ]);

    $dbEvent = Contribution::query()->sole()->events()->sole();

    expect($dbEvent->action)->toBe(ContributionEventAction::Created)
        ->and($dbEvent->user_id)->toBe($user->id);
});

test('a manual confirmation is logged with the admin as actor', function () {
    $adminUser = admin();
    $this->actingAs($adminUser);
    $contribution = Contribution::factory()->create();

    $this->post(route('admin.contributions.confirm', ['contribution' => $contribution]));

    $dbEvent = $contribution->events()->where('action', ContributionEventAction::Confirmed)->sole();

    expect($dbEvent->user_id)->toBe($adminUser->id);
});

test('an automatic bank match is logged without an actor', function () {
    $this->actingAs(admin());
    $contribution = Contribution::factory()->create(['amount' => 2500]);

    $csv = implode("\n", [
        'Boekingsdatum;Bedrag;Mededeling',
        "05/08/2026;25,00;{$contribution->reference}",
    ]);

    $this->post(route('admin.bank.import'), [
        'statement' => UploadedFile::fake()->createWithContent('afschrift.csv', $csv),
    ]);

    $dbEvent = $contribution->events()->where('action', ContributionEventAction::Confirmed)->sole();

    expect($dbEvent->user_id)->toBeNull();
});

test('cancelling by the donor is logged with the donor as actor', function () {
    $user = donor();
    $contribution = Contribution::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('account.contributions.destroy', ['contribution' => $contribution]));

    $dbEvent = $contribution->events()->where('action', ContributionEventAction::Cancelled)->sole();

    expect($dbEvent->user_id)->toBe($user->id);
});

test('an admin adjustment is logged', function () {
    $adminUser = admin();
    $this->actingAs($adminUser);
    $contribution = Contribution::factory()->create(['amount' => 5000]);

    $this->patch(route('admin.contributions.update', ['contribution' => $contribution]), [
        'name' => $contribution->name,
        'amount' => 4500,
    ]);

    expect($contribution->events()->where('action', ContributionEventAction::Updated)->count())->toBe(1);
});
