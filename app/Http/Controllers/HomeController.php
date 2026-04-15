<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Place;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // 로그인 사용자: 본인 카테고리 사용 (없으면 시스템 기본 복제)
        // 비로그인: 시스템 기본 그대로
        if ($request->user()) {
            CategoryController::ensureUserCategories($request->user());
            $categories = Category::where('user_id', $request->user()->id)
                ->orderBy('sort_order')->get();
        } else {
            $categories = Category::whereNull('user_id')->orderBy('sort_order')->get();
        }

        $selectedCategory = $request->integer('category') ?: null;
        $q = trim((string) $request->input('q', ''));

        $myPlaces = collect();
        if ($request->user()) {
            $query = Place::where('user_id', $request->user()->id)
                ->where('is_visible', true)
                ->with(['category', 'images', 'themes'])
                ->orderBy('sort_order')
                ->latest();

            if ($selectedCategory) {
                $query->where('category_id', $selectedCategory);
            }
            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('memo', 'like', "%{$q}%")
                      ->orWhere('address', 'like', "%{$q}%");
                });
            }
            $myPlaces = $query->limit(100)->get();
        }

        // 카테고리별 가장 최근 저장 장소 (홈 슬라이더용 — 필터 무관)
        $categoryLatest = collect();
        $recentPlaces = collect();
        $savedCount = 0;
        $weekNewCount = 0;
        if ($request->user()) {
            $uid = $request->user()->id;

            // 사용자 전체 visible 장소를 한 번에 로드 후 파생값 계산 (쿼리 3개 → 1개)
            $allPlaces = Place::where('user_id', $uid)
                ->where('is_visible', true)
                ->with(['category', 'images', 'themes'])
                ->latest()
                ->get();

            $savedCount = $allPlaces->count();
            $recentPlaces = $allPlaces->take(20);
            $categoryLatest = $allPlaces->groupBy('category_id')->map(fn($g) => $g->first());
            $weekAgo = now()->subDays(7);
            $weekNewCount = $allPlaces->filter(fn($p) => $p->created_at >= $weekAgo)->count();
        }

        $curation = $this->curation();

        return view('home.index', compact(
            'categories', 'myPlaces', 'selectedCategory', 'q', 'curation', 'categoryLatest',
            'recentPlaces', 'savedCount', 'weekNewCount'
        ));
    }

    /**
     * MVP 더미 큐레이션 데이터
     * TODO: 향후 실제 집계/에디터스픽으로 교체
     */
    private function curation(): array
    {
        $thumbs = [
            '/images/sample/sample1.jpg',
            '/images/sample/sample2.jpg',
            '/images/sample/sample3.jpg',
        ];
        shuffle($thumbs);

        $pick = function () use ($thumbs) {
            static $i = 0;
            return $thumbs[$i++ % count($thumbs)];
        };

        return [
            'weekly' => [
                ['icon' => '☕', 'name' => '블루보틀 삼청한옥점', 'category' => '카페', 'area' => '종로', 'saves' => 1243, 'thumb' => $pick()],
                ['icon' => '🍽', 'name' => '금돼지식당', 'category' => '맛집', 'area' => '신당동', 'saves' => 987, 'thumb' => $pick()],
                ['icon' => '🍜', 'name' => '옥동식', 'category' => '맛집', 'area' => '서교동', 'saves' => 876, 'thumb' => $pick()],
                ['icon' => '🍰', 'name' => '레이어드 안국', 'category' => '카페', 'area' => '안국', 'saves' => 812, 'thumb' => $pick()],
                ['icon' => '🥩', 'name' => '몽탄', 'category' => '맛집', 'area' => '용산', 'saves' => 745, 'thumb' => $pick()],
            ],
            'seoul_trip' => [
                ['icon' => '🏯', 'name' => '경복궁', 'category' => '여행', 'area' => '종로', 'thumb' => $pick()],
                ['icon' => '🌸', 'name' => '남산서울타워', 'category' => '여행', 'area' => '용산', 'thumb' => $pick()],
                ['icon' => '🛍', 'name' => '더현대 서울', 'category' => '쇼핑', 'area' => '여의도', 'thumb' => $pick()],
                ['icon' => '🎨', 'name' => '국립현대미술관 서울', 'category' => '여행', 'area' => '삼청동', 'thumb' => $pick()],
                ['icon' => '🌃', 'name' => '한강 뚝섬유원지', 'category' => '여행', 'area' => '성수', 'thumb' => $pick()],
            ],
            'trending_food' => [
                ['icon' => '🍣', 'name' => '스시 오마카세 하루', 'category' => '맛집', 'area' => '청담', 'thumb' => $pick()],
                ['icon' => '🍝', 'name' => '트라토리아 체사레', 'category' => '맛집', 'area' => '이태원', 'thumb' => $pick()],
                ['icon' => '🍲', 'name' => '을지로 노가리 골목', 'category' => '맛집', 'area' => '을지로', 'thumb' => $pick()],
                ['icon' => '🥟', 'name' => '명동교자', 'category' => '맛집', 'area' => '명동', 'thumb' => $pick()],
                ['icon' => '🍛', 'name' => '르블란서', 'category' => '맛집', 'area' => '한남동', 'thumb' => $pick()],
            ],
        ];
    }
}
