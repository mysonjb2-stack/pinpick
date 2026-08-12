<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->orderBy('sort_order')->get(['id', 'name', 'icon', 'color', 'sort_order', 'is_default']);
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
            ['q' => '부적절한 리스트를 발견했어요', 'a' => '탐색 탭이나 리스트 상세 페이지의 [⋮] 메뉴에서 신고할 수 있어요. 스팸/광고, 부적절한 콘텐츠 등 사유를 선택해 접수하면 확인 후 조치됩니다.'],
            ['q' => '특정 사용자의 리스트를 보고 싶지 않아요', 'a' => '탐색 탭이나 리스트 상세의 [⋮] 메뉴에서 해당 사용자를 차단할 수 있어요. 차단하면 해당 사용자의 리스트가 더 이상 노출되지 않으며, MY > 차단한 사용자에서 언제든 해제할 수 있습니다.'],
            ['q' => '위치 정보를 허용하지 않으면 사용할 수 없나요?', 'a' => '위치 권한 없이도 모든 기능을 이용할 수 있어요. 주변 추천 대신 전체 지역 기준의 추천이 표시되며, 장소 검색과 저장, 리스트 열람 등은 동일하게 사용 가능합니다.'],
            ['q' => '리스트를 담으면 원본이 바뀌어도 반영되나요?', 'a' => '담은 시점의 장소 정보가 내 지도에 복사되며, 이후 원본 리스트가 변경되더라도 내 지도에 저장된 장소에는 영향을 주지 않습니다.'],
            ['q' => '해외에서 저장한 장소도 관리되나요?', 'a' => '네, 국내와 해외 장소를 함께 관리할 수 있어요. 저장된 장소에는 국내/해외 구분 표시가 자동으로 붙으며, 내 지도와 카테고리에서 함께 확인할 수 있습니다.'],
        ];
        return view('mypage.faq', compact('faqs'));
    }

    public function terms(Request $request)
    {
        $tab = in_array($request->input('tab'), ['privacy', 'location'], true)
            ? $request->input('tab') : 'terms';
        return view('mypage.terms', compact('tab'));
    }

    public function destroyAccount(Request $request)
    {
        $user = $request->user();
        if (!$user) return redirect()->route('home');

        if ($user->profile_image && str_starts_with($user->profile_image, '/storage/')) {
            Storage::disk('public')->delete(substr($user->profile_image, strlen('/storage/')));
        }

        DB::transaction(function () use ($user) {
            $uid = $user->id;

            $placeIds = DB::table('places')->where('user_id', $uid)->pluck('id');
            if ($placeIds->isNotEmpty()) {
                DB::table('place_images')->whereIn('place_id', $placeIds)->delete();
                DB::table('place_themes')->whereIn('place_id', $placeIds)->delete();
                DB::table('trip_places')->whereIn('place_id', $placeIds)->delete();
            }

            $tripIds = DB::table('trips')->where('user_id', $uid)->pluck('id');
            if ($tripIds->isNotEmpty()) {
                DB::table('trip_places')->whereIn('trip_id', $tripIds)->delete();
            }

            $collectionIds = DB::table('shared_collections')->where('user_id', $uid)->pluck('id');
            if ($collectionIds->isNotEmpty()) {
                DB::table('shared_places')->whereIn('shared_collection_id', $collectionIds)->delete();
            }

            DB::table('curation_reports')->where('reporter_user_id', $uid)->update(['reporter_user_id' => null]);
            DB::table('curations')->where('author_user_id', $uid)->update(['author_user_id' => null]);

            DB::table('places')->where('user_id', $uid)->delete();
            DB::table('trips')->where('user_id', $uid)->delete();
            DB::table('shared_collections')->where('user_id', $uid)->delete();
            DB::table('blocked_users')->where('user_id', $uid)->orWhere('blocked_user_id', $uid)->delete();
            DB::table('categories')->where('user_id', $uid)->delete();

            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', '계정이 삭제되었어요');
    }
}
