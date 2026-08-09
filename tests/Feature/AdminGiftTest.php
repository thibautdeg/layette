<?php

use App\Enums\BabyGender;
use App\Models\Gift;
use App\Models\GiftList;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests cannot reach the admin area', function () {
    GiftList::factory()->create();

    $this->get(route('admin.gifts.index'))->assertRedirect(route('login'));
});

test('donors cannot reach the admin area', function () {
    GiftList::factory()->create();

    $this->actingAs(donor())
        ->get(route('admin.gifts.index'))
        ->assertForbidden();
});

test('an admin can add a gift with a photo', function () {
    Storage::fake(config('filesystems.uploads'));

    $this->actingAs(admin());
    GiftList::factory()->create();

    $this->post(route('admin.gifts.store'), [
        'title' => 'Kinderwagen',
        'description' => 'Een stevige buggy',
        'price' => 45000,
        'shop_url' => 'https://voorbeeld.be/kinderwagen',
        'allows_partial_contributions' => true,
        'allows_purchase' => false,
        'image' => UploadedFile::fake()->image('kinderwagen.jpg'),
    ])->assertRedirect();

    $dbGift = Gift::query()->sole();

    expect($dbGift->title)->toBe('Kinderwagen')
        ->and($dbGift->price)->toBe(45000)
        ->and($dbGift->allows_purchase)->toBeFalse()
        ->and($dbGift->image_path)->not->toBeNull();

    Storage::disk(config('filesystems.uploads'))->assertExists($dbGift->image_path);
});

test('an admin can update a gift', function () {
    $this->actingAs(admin());
    $gift = Gift::factory()->create(['title' => 'Oud', 'price' => 5000]);

    $this->post(route('admin.gifts.update', ['gift' => $gift]), [
        'title' => 'Nieuw',
        'description' => null,
        'price' => 7500,
        'shop_url' => null,
        'allows_partial_contributions' => true,
        'allows_purchase' => true,
    ])->assertRedirect();

    $dbGift = Gift::find($gift->id);

    expect($dbGift->title)->toBe('Nieuw')
        ->and($dbGift->price)->toBe(7500);
});

test('an admin can hide and unhide a gift', function () {
    $this->actingAs(admin());
    $gift = Gift::factory()->create();

    $this->patch(route('admin.gifts.visibility.update', ['gift' => $gift]))->assertRedirect();
    expect(Gift::find($gift->id)->isHidden())->toBeTrue();

    $this->patch(route('admin.gifts.visibility.update', ['gift' => $gift]))->assertRedirect();
    expect(Gift::find($gift->id)->isHidden())->toBeFalse();
});

test('an admin can reorder the gifts', function () {
    $this->actingAs(admin());
    $giftList = GiftList::factory()->create();
    $first = Gift::factory()->for($giftList)->create(['position' => 1]);
    $second = Gift::factory()->for($giftList)->create(['position' => 2]);

    $this->patch(route('admin.gifts.order.update'), [
        'ids' => [$second->id, $first->id],
    ])->assertRedirect();

    expect(Gift::find($second->id)->position)->toBe(1)
        ->and(Gift::find($first->id)->position)->toBe(2);
});

test('an admin can delete a gift', function () {
    $this->actingAs(admin());
    $gift = Gift::factory()->create();

    $this->delete(route('admin.gifts.destroy', ['gift' => $gift]))->assertRedirect();

    expect(Gift::query()->count())->toBe(0);
});

test('an admin can update the list settings and close the list', function () {
    $this->actingAs(admin());
    $giftList = GiftList::factory()->create();

    $this->post(route('admin.settings.update'), [
        'title' => 'Lijstje voor onze spruit',
        'baby_name' => 'Nora',
        'baby_gender' => 'girl',
        'intro' => 'Welkom!',
        'iban' => 'BE68539007547034',
        'account_holder' => 'Thibaut & partner',
        'wero_phone' => '+32470123456',
        'expected_at' => '2026-11-01',
        'is_closed' => true,
    ])->assertRedirect();

    $dbGiftList = GiftList::find($giftList->id);

    expect($dbGiftList->title)->toBe('Lijstje voor onze spruit')
        ->and($dbGiftList->baby_name)->toBe('Nora')
        ->and($dbGiftList->baby_gender)->toBe(BabyGender::Girl)
        ->and($dbGiftList->isClosed())->toBeTrue();
});
