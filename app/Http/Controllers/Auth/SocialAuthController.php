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
        if ($provider === 'apple') {
            $driver->scopes(['name', 'email']);
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
            if ($this->isAppWebview()) {
                return $this->appCallbackResponse(false);
            }
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

        if ($this->isAppWebview()) {
            return $this->appCallbackResponse(true, $user);
        }

        return redirect('/');
    }

    public function nativeLogin(Request $request, string $provider)
    {
        $this->validateProvider($provider);

        $token = $request->input('access_token') ?: $request->input('atk');
        if (!$token) {
            return response()->json(['error' => 'access_token 필요'], 422);
        }

        $userInfo = match ($provider) {
            'kakao' => $this->fetchKakaoUser($token),
            'google' => $this->fetchGoogleUser($token),
            'naver' => $this->fetchNaverUser($token),
            'apple' => $this->fetchAppleUser($token, $request->input('name')),
        };

        if (!$userInfo) {
            logger('nativeLogin 인증 실패', ['provider' => $provider, 'token_length' => strlen($token)]);
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
                'email' => $email ?: $provider . '_' . $providerId . '@noemail.pinpick',
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
        // 1) JWT(id_token) 형식이면 직접 디코딩 (마맵 앱 호환)
        if (substr_count($token, '.') === 2) {
            $payload = $this->decodeJwt($token);
            if ($payload && !empty($payload['sub'])) {
                logger('Google JWT 직접 디코딩 성공', ['sub' => $payload['sub'], 'email' => $payload['email'] ?? null]);
                return [
                    'id' => (string) $payload['sub'],
                    'name' => $payload['name'] ?? ($payload['given_name'] ?? null),
                    'email' => $payload['email'] ?? null,
                    'avatar' => $payload['picture'] ?? null,
                ];
            }
        }

        // 2) access_token으로 userinfo 시도
        $response = Http::withToken($token)->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if ($response->successful()) {
            $data = $response->json();
            return [
                'id' => (string) $data['id'],
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'avatar' => $data['picture'] ?? null,
            ];
        }

        logger('Google 로그인 실패', [
            'status' => $response->status(),
            'token_prefix' => substr($token, 0, 20) . '...',
        ]);
        return null;
    }

    private function decodeJwt(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        $payload = $parts[1];
        $remainder = strlen($payload) % 4;
        if ($remainder) {
            $payload .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'));
        if ($decoded === false) return null;

        return json_decode($decoded, true);
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

    private function fetchAppleUser(string $token, ?string $name = null): ?array
    {
        // JWT id_token 형식
        if (substr_count($token, '.') === 2) {
            $payload = $this->decodeJwt($token);
            if ($payload && !empty($payload['sub'])) {
                if (($payload['iss'] ?? '') === 'https://appleid.apple.com') {
                    return [
                        'id' => (string) $payload['sub'],
                        'name' => $name ?: ($payload['name'] ?? null),
                        'email' => $payload['email'] ?? null,
                        'avatar' => null,
                    ];
                }
            }
        }

        // 앱에서 보내는 plain userIdentifier (Apple sub ID)
        if (strlen($token) > 10 && !str_contains($token, ' ')) {
            return [
                'id' => $token,
                'name' => $name,
                'email' => null,
                'avatar' => null,
            ];
        }

        return null;
    }

    private function isAppWebview(): bool
    {
        return str_contains(request()->header('User-Agent', ''), 'MYPINPICK');
    }

    private function appCallbackResponse(bool $success, ?User $user = null)
    {
        $data = json_encode([
            'success' => $success,
            'redirect' => '/',
            'user' => $success && $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
        ]);

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'
            . '<script>'
            . 'var result = ' . $data . ';'
            . 'try {'
            . '  if (window.PinpickApp && window.PinpickApp.onLoginResult) {'
            . '    window.PinpickApp.onLoginResult(JSON.stringify(result));'
            . '  } else if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.pinpickApp) {'
            . '    window.webkit.messageHandlers.pinpickApp.postMessage(JSON.stringify(result));'
            . '  } else {'
            . '    location.href = result.redirect || "/";'
            . '  }'
            . '} catch(e) { location.href = "/"; }'
            . '</script>'
            . '</body></html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    private function validateProvider(string $provider): void
    {
        abort_unless(in_array($provider, ['kakao', 'google', 'naver', 'apple'], true), 404);
    }
}
