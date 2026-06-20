<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const SUPPORTED_PROVIDERS = ['google', 'github'];

    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, self::SUPPORTED_PROVIDERS), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        abort_unless(in_array($provider, self::SUPPORTED_PROVIDERS), 404);

        $socialUser = Socialite::driver($provider)->user();
        $idColumn   = "{$provider}_id";

        $user = User::query()->where($idColumn, $socialUser->getId())->first()
            ?? $this->findAndLinkByEmail($socialUser->getEmail(), $idColumn, $socialUser->getId())
            ?? $this->createUser($socialUser, $idColumn);

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }

    private function findAndLinkByEmail(string $email, string $idColumn, string $socialId): ?User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->update([$idColumn => $socialId]);
        }

        return $user;
    }

    private function createUser($socialUser, string $idColumn): User
    {
        return User::query()->create([
            'name'      => $socialUser->getName(),
            'email'     => $socialUser->getEmail(),
            'password'  => null,
            $idColumn   => $socialUser->getId(),
        ]);
    }
}
