<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewLoginController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $reviewEmail = config('services.review.email');
        $reviewPassword = config('services.review.password');

        if (!$reviewEmail || !$reviewPassword) {
            return response()->json(['error' => '심사 로그인이 비활성화되어 있습니다.'], 403);
        }

        if ($data['email'] !== $reviewEmail || $data['password'] !== $reviewPassword) {
            return response()->json(['error' => '계정 정보가 일치하지 않습니다.'], 401);
        }

        $user = User::where('email', $reviewEmail)
            ->where('is_review_account', true)
            ->first();

        if (!$user) {
            return response()->json(['error' => '심사 계정이 준비되지 않았습니다.'], 404);
        }

        Auth::login($user, true);

        return response()->json(['success' => true, 'redirect' => '/']);
    }
}
