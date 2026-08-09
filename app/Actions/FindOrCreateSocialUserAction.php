<?php

namespace App\Actions;

use App\Enums\Role;
use App\Enums\SocialProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class FindOrCreateSocialUserAction
{
    public function handle(SocialProvider $provider, SocialiteUser $socialUser): User
    {
        return DB::transaction(function () use ($provider, $socialUser): User {
            $user = User::query()->where($provider->column(), $socialUser->getId())->first();

            if ($user !== null) {
                return $user;
            }

            $user = User::query()->where('email', $socialUser->getEmail())->first();

            if ($user !== null) {
                return $this->linkProvider($user, $provider, $socialUser);
            }

            return $this->createUser($provider, $socialUser);
        });
    }

    protected function linkProvider(User $user, SocialProvider $provider, SocialiteUser $socialUser): User
    {
        $user->update([$provider->column() => $socialUser->getId()]);

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();
        }

        return $user;
    }

    protected function createUser(SocialProvider $provider, SocialiteUser $socialUser): User
    {
        $user = User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? $socialUser->getEmail(),
            'email' => $socialUser->getEmail(),
            $provider->column() => $socialUser->getId(),
        ]);

        $user->markEmailAsVerified();
        $user->addRole(Role::Donor->value);

        return $user;
    }
}
