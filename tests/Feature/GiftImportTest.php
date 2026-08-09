<?php

use App\Models\Gift;
use App\Models\GiftList;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('the preview endpoint extracts open graph data from a shop page', function () {
    $this->actingAs(admin());

    Http::fake([
        'www.dreambaby.be/*' => Http::response(<<<'HTML'
            <html><head>
                <title>Fallback titel</title>
                <meta property="og:title" content="Wandelwagen Deluxe" />
                <meta property="og:description" content="Een fijne wandelwagen." />
                <meta property="og:image" content="https://www.dreambaby.be/foto.jpg" />
                <meta property="product:price:amount" content="299.95" />
            </head><body></body></html>
            HTML),
    ]);

    $this->postJson(route('admin.gifts.preview'), ['url' => 'https://www.dreambaby.be/wandelwagen'])
        ->assertSuccessful()
        ->assertJson([
            'title' => 'Wandelwagen Deluxe',
            'description' => 'Een fijne wandelwagen.',
            'image_url' => 'https://www.dreambaby.be/foto.jpg',
            'price' => 29995,
        ]);
});

test('an unreachable page results in a validation error', function () {
    $this->actingAs(admin());

    Http::fake([
        '*' => Http::response('', 500),
    ]);

    $this->postJson(route('admin.gifts.preview'), ['url' => 'https://www.voorbeeld.be/kapot'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('url');
});

test('a gift can be created with a downloaded webshop photo', function () {
    Storage::fake('public');

    $this->actingAs(admin());
    GiftList::factory()->create();

    Http::fake([
        'www.dreambaby.be/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $this->post(route('admin.gifts.store'), [
        'title' => 'Wandelwagen Deluxe',
        'description' => 'Een fijne wandelwagen.',
        'price' => 29995,
        'shop_url' => 'https://www.dreambaby.be/wandelwagen',
        'allows_partial_contributions' => true,
        'allows_purchase' => true,
        'image_url' => 'https://www.dreambaby.be/foto.jpg',
    ])->assertRedirect();

    $dbGift = Gift::query()->sole();

    expect($dbGift->image_path)->not->toBeNull()
        ->and($dbGift->image_path)->toEndWith('.jpg');

    Storage::disk('public')->assertExists($dbGift->image_path);
});

test('a non-image download is ignored and the gift is still created', function () {
    Storage::fake('public');

    $this->actingAs(admin());
    GiftList::factory()->create();

    Http::fake([
        '*' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html']),
    ]);

    $this->post(route('admin.gifts.store'), [
        'title' => 'Wandelwagen',
        'price' => 29995,
        'allows_partial_contributions' => true,
        'allows_purchase' => true,
        'image_url' => 'https://www.voorbeeld.be/geen-foto',
    ])->assertRedirect();

    expect(Gift::query()->sole()->image_path)->toBeNull();
});
