<?php

namespace App\Http\Controllers;

use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'blocked_user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = Auth::id();
        $blockedId = $data['blocked_user_id'];

        if ($userId === $blockedId) {
            return response()->json(['error' => '자기 자신을 차단할 수 없습니다.'], 422);
        }

        BlockedUser::firstOrCreate([
            'user_id' => $userId,
            'blocked_user_id' => $blockedId,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(int $blockedUserId)
    {
        BlockedUser::where('user_id', Auth::id())
            ->where('blocked_user_id', $blockedUserId)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function index()
    {
        $blockedUsers = BlockedUser::where('user_id', Auth::id())
            ->with('blockedUser:id,name,profile_image')
            ->orderByDesc('created_at')
            ->get();

        return view('mypage.blocked-users', compact('blockedUsers'));
    }
}
