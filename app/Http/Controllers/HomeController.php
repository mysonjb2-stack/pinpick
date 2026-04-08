<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Place;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::whereNull('user_id')->orderBy('sort_order')->get();

        $selectedCategory = $request->integer('category') ?: null;
        $q = trim((string) $request->input('q', ''));

        $myPlaces = collect();
        if ($request->user()) {
            $query = Place::where('user_id', $request->user()->id)
                ->where('is_visible', true)
                ->with('category')
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
        if ($request->user()) {
            $categoryLatest = Place::where('user_id', $request->user()->id)
                ->where('is_visible', true)
                ->with('category')
                ->latest()
                ->get()
                ->groupBy('category_id')
                ->map(fn($g) => $g->first());
        }

        $curation = $this->curation();

        return view('home.index', compact(
            'categories', 'myPlaces', 'selectedCategory', 'q', 'curation', 'categoryLatest'
        ));
    }

    /**
     * MVP 더미 큐레이션 데이터
     * TODO: 향후 실제 집계/에디터스픽으로 교체
     */
    private function curation(): array
    {
        return [
            'weekly' => [
                ['icon' => '☕', 'name' => '블루보틀 삼청한옥점', 'category' => '카페', 'area' => '종로', 'saves' => 1243],
                ['icon' => '🍽', 'name' => '금돼지식당', 'category' => '맛집', 'area' => '신당동', 'saves' => 987],
                ['icon' => '🍜', 'name' => '옥동식', 'category' => '맛집', 'area' => '서교동', 'saves' => 876],
                ['icon' => '🍰', 'name' => '레이어드 안국', 'category' => '카페', 'area' => '안국', 'saves' => 812],
                ['icon' => '🥩', 'name' => '몽탄', 'category' => '맛집', 'area' => '용산', 'saves' => 745],
            ],
            'seoul_trip' => [
                ['icon' => '🏯', 'name' => '경복궁', 'category' => '여행', 'area' => '종로'],
                ['icon' => '🌸', 'name' => '남산서울타워', 'category' => '여행', 'area' => '용산'],
                ['icon' => '🛍', 'name' => '더현대 서울', 'category' => '쇼핑', 'area' => '여의도'],
                ['icon' => '🎨', 'name' => '국립현대미술관 서울', 'category' => '여행', 'area' => '삼청동'],
                ['icon' => '🌃', 'name' => '한강 뚝섬유원지', 'category' => '여행', 'area' => '성수'],
            ],
            'trending_food' => [
                ['icon' => '🍣', 'name' => '스시 오마카세 하루', 'category' => '맛집', 'area' => '청담'],
                ['icon' => '🍝', 'name' => '트라토리아 체사레', 'category' => '맛집', 'area' => '이태원'],
                ['icon' => '🍲', 'name' => '을지로 노가리 골목', 'category' => '맛집', 'area' => '을지로'],
                ['icon' => '🥟', 'name' => '명동교자', 'category' => '맛집', 'area' => '명동'],
                ['icon' => '🍛', 'name' => '르블란서', 'category' => '맛집', 'area' => '한남동'],
            ],
        ];
    }
}
