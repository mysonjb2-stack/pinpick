<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Place;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::realUsers()->withCount('places');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->input('sort') === 'places') {
            $query->orderByDesc('places_count');
        } else {
            $query->latest();
        }

        $users = $query->paginate(20)->appends($request->query());

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->loadCount('places');
        $places = Place::where('user_id', $user->id)
            ->with('category')
            ->latest()
            ->paginate(20);

        return view('admin.users.show', compact('user', 'places'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', '회원이 삭제되었습니다.');
    }

    public function toggleReview(User $user)
    {
        $user->update(['is_review_account' => !$user->is_review_account]);
        $status = $user->is_review_account ? '활성화' : '비활성화';
        return back()->with('success', "심사 계정 {$status}: {$user->email}");
    }
}
