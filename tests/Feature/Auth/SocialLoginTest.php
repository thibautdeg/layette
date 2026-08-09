<?php

use App\Enums\Role;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('users are redirected to the oauth provider', function (string $provider) {
    Socialite::fake($provider);

    $this->get(route('oauth.redirect', ['provider' => $provider]))->assertRedirect();
})->with(['google', 'facebook']);

test('unknown providers return a 404', function () {
    $this->get(route('oauth.redirect', ['provider' => 'github']))->assertNotFound();
});

test('a new user is created and logged in through the oauth callback', function (string $provider, string $column) {
    Socialite::fake($provider, SocialiteUser::fake([
        'id' => 'provider-123',
        'name' => 'Jan Jansen',
        'email' => 'jan@voorbeeld.be',
    ]));

    $response = $this->get(route('oauth.callback', ['provider' => $provider]));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $dbUser = User::query()->where('email', 'jan@voorbeeld.be')->firstOrFail();

    $this->assertSame('Jan Jansen', $dbUser->name);
    $this->assertSame('provider-123', $dbUser->{$column});
    $this->assertNotNull($dbUser->email_verified_at);
    $this->assertTrue($dbUser->hasRole(Role::Donor->value));
})->with([
    'google' => ['google', 'google_id'],
    'facebook' => ['facebook', 'facebook_id'],
]);

test('an existing user with the same email is linked to the provider', function () {
    $user = donor();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-456',
        'name' => 'Ander Naam',
        'email' => $user->email,
    ]));

    $this->get(route('oauth.callback', ['provider' => 'google']))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);

    $dbUser = User::findOrFail($user->id);

    $this->assertSame('google-456', $dbUser->google_id);
    $this->assertSame($user->name, $dbUser->name);
    $this->assertSame(1, User::query()->count());
});

test('a returning social user is logged in by provider id', function () {
    $user = donor();
    $user->update(['google_id' => 'google-789']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-789',
        'name' => $user->name,
        'email' => 'nieuw-mailadres@voorbeeld.be',
    ]));

    $this->get(route('oauth.callback', ['provider' => 'google']))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    $this->assertSame(1, User::query()->count());
});

test('the callback fails gracefully when the provider returns no email', function () {
    Socialite::fake('facebook', SocialiteUser::fake([
        'id' => 'facebook-123',
        'name' => 'Jan Jansen',
        'email' => null,
    ]));

    $this->get(route('oauth.callback', ['provider' => 'facebook']))
        ->assertRedirect(route('login', absolute: false))
        ->assertSessionHasErrors('social');

    $this->assertGuest();
    $this->assertSame(0, User::query()->count());
});

test('authenticated users cannot use the social login routes', function () {
    $this->actingAs(donor())
        ->get(route('oauth.redirect', ['provider' => 'google']))
        ->assertRedirect();

    $this->assertAuthenticated();
});
