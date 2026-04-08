<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect('/login')->with('error', '소셜 로그인에 실패했습니다.');
        }

        $user = User::firstOrCreate(
            ['provider' => $provider, 'provider_id' => (string) $social->getId()],
            [
                'name' => $social->getName() ?: $social->getNickname() ?: '핀픽러',
                'email' => $social->getEmail(),
                'profile_image' => $social->getAvatar(),
            ]
        );

        Auth::login($user, true);

        return redirect('/');
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    }

    private function validateProvider(string $provider): void
    {
        abort_unless(in_array($provider, ['kakao', 'google'], true), 404);
    }
}
