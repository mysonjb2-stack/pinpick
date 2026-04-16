<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Place;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * 사용자 카테고리 목록 (편집 모달용)
     */
    public function index(Request $request)
    {
        $this->ensureUserCategories($request->user());

        $items = Category::where('user_id', $request->user()->id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'icon', 'sort_order']);

        return response()->json(['items' => $items]);
    }

    /**
     * 새 카테고리 추가
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:30',
        ]);

        $maxOrder = Category::where('user_id', $request->user()->id)->max('sort_order') ?? 0;

        $cat = Category::create([
            'user_id'    => $request->user()->id,
            'name'       => $data['name'],
            'icon'       => '📌',
            'sort_order' => $maxOrder + 1,
            'is_default' => false,
        ]);

        return response()->json(['ok' => true, 'item' => [
            'id' => $cat->id,
            'name' => $cat->name,
            'is_default' => false,
        ]]);
    }

    /**
     * 카테고리 이름 변경
     */
    public function update(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:30',
        ]);

        $category->update(['name' => $data['name']]);

        return response()->json(['ok' => true, 'item' => $category]);
    }

    /**
     * 카테고리 순서 일괄 변경
     */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:categories,id',
        ]);

        $userId = $request->user()->id;
        foreach ($data['order'] as $i => $id) {
            Category::where('id', $id)->where('user_id', $userId)->update(['sort_order' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * 카테고리 삭제 — 저장된 장소가 있으면 차단
     */
    public function destroy(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        if ($category->is_default) {
            return response()->json([
                'ok' => false,
                'error' => '기본 카테고리는 삭제할 수 없어요.',
            ], 422);
        }

        $placeCount = Place::where('category_id', $category->id)->count();
        if ($placeCount > 0) {
            return response()->json([
                'ok'    => false,
                'error' => "해당 카테고리에 저장된 장소가 {$placeCount}개 있어요. 먼저 장소를 다른 카테고리로 옮겨주세요.",
            ], 422);
        }

        $category->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * 카테고리 전체보기 (목록/지도)
     */
    public function show(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        $view = in_array($request->input('view'), ['list', 'map']) ? $request->input('view') : 'list';
        $status = in_array($request->input('status'), ['all', 'planned', 'visited']) ? $request->input('status') : 'all';
        $sort = in_array($request->input('sort'), ['recent', 'name', 'visit']) ? $request->input('sort') : 'recent';

        $q = Place::with(['images', 'themes'])
            ->where('user_id', $request->user()->id)
            ->where('category_id', $category->id)
            ->where('is_visible', true);

        if ($status === 'planned') $q->where('status', 'planned');
        if ($status === 'visited') $q->where('status', 'visited');

        match ($sort) {
            'name' => $q->orderBy('name'),
            'visit' => $q->orderByDesc('visited_at')->orderByDesc('created_at'),
            default => $q->orderByDesc('created_at'),
        };

        $places = $q->get();
        $totalCount = Place::where('user_id', $request->user()->id)
            ->where('category_id', $category->id)
            ->where('is_visible', true)->count();

        $naverClientId = config('services.naver_map.client_id');

        return view('categories.show', compact('category', 'places', 'view', 'status', 'sort', 'totalCount', 'naverClientId'));
    }

    /**
     * 사용자가 자기 카테고리가 없으면 시스템 기본을 복제
     */
    public static function ensureUserCategories($user): void
    {
        if (!$user) return;

        $exists = Category::where('user_id', $user->id)->exists();
        if ($exists) return;

        $defaults = Category::whereNull('user_id')->orderBy('sort_order')->get();
        foreach ($defaults as $d) {
            Category::create([
                'user_id'    => $user->id,
                'name'       => $d->name,
                'icon'       => $d->icon,
                'sort_order' => $d->sort_order,
                'is_default' => true,
            ]);
        }
    }
}
