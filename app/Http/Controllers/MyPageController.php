<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MyPageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }
        $placeCount = Place::where('user_id', $user->id)->count();
        $trips = Trip::where('user_id', $user->id)->latest()->get();
        return view('mypage.index', compact('user', 'placeCount', 'trips'));
    }

    public function editProfile(Request $request)
    {
        return view('mypage.edit', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:30',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:3072',
        ]);

        $user->name = $data['name'];

        if ($request->hasFile('avatar')) {
            $old = $user->profile_image;
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->profile_image = '/storage/' . $path;

            if ($old && str_starts_with($old, '/storage/')) {
                $oldRel = substr($old, strlen('/storage/'));
                Storage::disk('public')->delete($oldRel);
            }
        }

        $user->save();

        return redirect()->route('mypage')->with('success', '프로필이 저장되었어요');
    }

    public function categories(Request $request)
    {
        CategoryController::ensureUserCategories($request->user());
        $categories = \App\Models\Category::where('user_id', $request->user()->id)
            ->orderBy('sort_order')->get(['id', 'name', 'icon', 'sort_order', 'is_default']);
        return view('mypage.categories', compact('categories'));
    }

    public function notices()
    {
        $notices = [];
        return view('mypage.notices', compact('notices'));
    }

    public function faq()
    {
        $faqs = [
            ['q' => '핀픽은 어떤 서비스인가요?', 'a' => '내가 가고 싶은 장소, 자주 가는 장소를 카테고리별로 저장하고 나만의 지도에서 꺼내볼 수 있는 개인 장소 관리 서비스입니다.'],
            ['q' => '로그인 없이도 이용할 수 있나요?', 'a' => '네, 비로그인 상태에서도 장소를 저장할 수 있습니다. 로그인하면 저장한 장소가 자동으로 내 계정으로 이관돼요.'],
            ['q' => '저장한 장소는 다른 사람에게 보이나요?', 'a' => '기본적으로 모든 장소는 비공개입니다. 본인만 열람할 수 있어요.'],
            ['q' => '카테고리를 추가하거나 순서를 바꿀 수 있나요?', 'a' => 'MY > 카테고리 관리에서 카테고리를 자유롭게 추가/수정/삭제하고 순서도 변경할 수 있어요.'],
            ['q' => '계정을 삭제하면 저장한 장소는 어떻게 되나요?', 'a' => '탈퇴 시 저장한 모든 장소와 카테고리가 함께 삭제되며 복구할 수 없습니다.'],
            ['q' => '문의는 어디로 하면 되나요?', 'a' => 'help.mapcube@gmail.com 으로 메일 주시면 확인 후 답변드려요.'],
        ];
        return view('mypage.faq', compact('faqs'));
    }

    public function terms(Request $request)
    {
        $tab = $request->input('tab') === 'privacy' ? 'privacy' : 'terms';
        return view('mypage.terms', compact('tab'));
    }

    public function destroyAccount(Request $request)
    {
        $user = $request->user();
        if (!$user) return redirect()->route('home');

        if ($user->profile_image && str_starts_with($user->profile_image, '/storage/')) {
            Storage::disk('public')->delete(substr($user->profile_image, strlen('/storage/')));
        }

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', '계정이 삭제되었어요');
    }
}
