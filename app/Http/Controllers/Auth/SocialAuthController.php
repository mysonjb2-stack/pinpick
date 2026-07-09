<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);
        $driver = Socialite::driver($provider);

        if ($provider === 'kakao') {
            $driver->scopes(['profile_nickname', 'profile_image', 'account_email']);
        }

        return $driver->redirect();
    }

    public function callback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            logger('소셜 로그인 에러: ' . $e->getMessage());
            return redirect('/login')->with('error', '소셜 로그인에 실패했습니다.');
        }

        $providerId = (string) $social->getId();
        $email = $social->getEmail();

        $user = User::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            $user = User::create([
                'provider' => $provider,
                'provider_id' => $providerId,
                'name' => $social->getName() ?: $social->getNickname() ?: '핀픽러',
                'email' => $email,
                'profile_image' => $social->getAvatar(),
            ]);
        }

        Auth::login($user, true);

        Cookie::queue('pp_last_login', $provider, 60 * 24 * 365, '/', null, false, false);

        return redirect('/');
    }

    public function nativeKakaoLogin(Request $request)
    {
        $token = $request->input('access_token');
        if (!$token) {
            return response()->json(['error' => 'access_token 필요'], 422);
        }

        $response = Http::withToken($token)->get('https://kapi.kakao.com/v2/user/me');
        if ($response->failed()) {
            return response()->json(['error' => '카카오 인증 실패'], 401);
        }

        $kakaoUser = $response->json();
        $providerId = (string) $kakaoUser['id'];
        $account = $kakaoUser['kakao_account'] ?? [];
        $profile = $account['profile'] ?? [];
        $email = $account['email'] ?? null;

        $user = User::where('provider', 'kakao')
            ->where('provider_id', $providerId)
            ->first();

        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            $user = User::create([
                'provider' => 'kakao',
                'provider_id' => $providerId,
                'name' => $profile['nickname'] ?? '핀픽러',
                'email' => $email,
                'profile_image' => $profile['profile_image_url'] ?? null,
            ]);
        }

        Auth::login($user, true);
        Cookie::queue('pp_last_login', 'kakao', 60 * 24 * 365, '/', null, false, false);

        return response()->json(['success' => true, 'redirect' => '/']);
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
        abort_unless(in_array($provider, ['kakao', 'google', 'naver'], true), 404);
    }
}
