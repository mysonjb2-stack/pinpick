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

        $user = $this->findOrCreateUser($provider, $providerId, $email, [
            'name' => $social->getName() ?: $social->getNickname() ?: '핀픽러',
            'avatar' => $social->getAvatar(),
        ]);

        Auth::login($user, true);
        Cookie::queue('pp_last_login', $provider, 60 * 24 * 365, '/', null, false, false);

        return redirect('/');
    }

    public function nativeLogin(Request $request, string $provider)
    {
        $this->validateProvider($provider);

        $token = $request->input('access_token');
        if (!$token) {
            return response()->json(['error' => 'access_token 필요'], 422);
        }

        $userInfo = match ($provider) {
            'kakao' => $this->fetchKakaoUser($token),
            'google' => $this->fetchGoogleUser($token),
            'naver' => $this->fetchNaverUser($token),
        };

        if (!$userInfo) {
            return response()->json(['error' => '인증 실패'], 401);
        }

        $user = $this->findOrCreateUser($provider, $userInfo['id'], $userInfo['email'], [
            'name' => $userInfo['name'] ?? '핀픽러',
            'avatar' => $userInfo['avatar'],
        ]);

        Auth::login($user, true);
        Cookie::queue('pp_last_login', $provider, 60 * 24 * 365, '/', null, false, false);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'redirect' => '/']);
        }

        return redirect('/');
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    }

    private function findOrCreateUser(string $provider, string $providerId, ?string $email, array $profile): User
    {
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
                'name' => $profile['name'],
                'email' => $email,
                'profile_image' => $profile['avatar'],
            ]);
        }

        return $user;
    }

    private function fetchKakaoUser(string $token): ?array
    {
        $response = Http::withToken($token)->get('https://kapi.kakao.com/v2/user/me');
        if ($response->failed()) return null;

        $data = $response->json();
        $account = $data['kakao_account'] ?? [];
        $profile = $account['profile'] ?? [];

        return [
            'id' => (string) $data['id'],
            'name' => $profile['nickname'] ?? null,
            'email' => $account['email'] ?? null,
            'avatar' => $profile['profile_image_url'] ?? null,
        ];
    }

    private function fetchGoogleUser(string $token): ?array
    {
        $response = Http::withToken($token)->get('https://www.googleapis.com/oauth2/v2/userinfo');
        if ($response->failed()) return null;

        $data = $response->json();
        return [
            'id' => (string) $data['id'],
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'avatar' => $data['picture'] ?? null,
        ];
    }

    private function fetchNaverUser(string $token): ?array
    {
        $response = Http::withToken($token)->get('https://openapi.naver.com/v1/nid/me');
        if ($response->failed()) return null;

        $data = $response->json()['response'] ?? [];
        return [
            'id' => (string) $data['id'],
            'name' => $data['name'] ?? $data['nickname'] ?? null,
            'email' => $data['email'] ?? null,
            'avatar' => $data['profile_image'] ?? null,
        ];
    }

    private function validateProvider(string $provider): void
    {
        abort_unless(in_array($provider, ['kakao', 'google', 'naver'], true), 404);
    }
}
