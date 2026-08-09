<?php

namespace App\Http\Controllers;

use App\Actions\FindOrCreateSocialUserAction;
use App\Enums\SocialProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialLoginController extends Controller
{
    public function redirect(SocialProvider $provider): SymfonyRedirectResponse
    {
        return Socialite::driver($provider->value)->redirect();
    }

    public function callback(SocialProvider $provider, FindOrCreateSocialUserAction $findOrCreateSocialUser): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider->value)->user();
        } catch (Throwable) {
            return to_route('login')->withErrors([
                'social' => "Aanmelden via {$provider->label()} is niet gelukt. Probeer het opnieuw of meld je aan met je mailadres.",
            ]);
        }

        if ($socialUser->getEmail() === null) {
            return to_route('login')->withErrors([
                'social' => "We kregen geen mailadres van {$provider->label()}. Meld je aan met je mailadres of probeer een andere manier.",
            ]);
        }

        $user = $findOrCreateSocialUser->handle($provider, $socialUser);

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
