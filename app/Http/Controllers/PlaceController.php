<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Place;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function create(Request $request)
    {
        $categories = Category::whereNull('user_id')->orderBy('sort_order')->get();
        return view('places.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'road_address' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'memo' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:planned,visited'],
            'visited_at' => ['nullable', 'date'],
            'kakao_place_id' => ['nullable', 'string'],
        ]);

        $data['user_id'] = $request->user()->id;
        Place::create($data);

        return redirect('/')->with('success', '장소가 저장되었어요.');
    }

    public function show(Place $place)
    {
        abort_unless($place->user_id === request()->user()?->id, 403);
        return view('places.show', compact('place'));
    }

    public function destroy(Place $place, Request $request)
    {
        abort_unless($place->user_id === $request->user()?->id, 403);
        $place->delete();
        return redirect('/')->with('success', '삭제되었어요.');
    }

    // 카카오 로컬 API 프록시 (검색)
    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') return response()->json(['documents' => []]);

        $key = config('services.kakao_local.rest_api_key');
        if (!$key) return response()->json(['documents' => [], 'error' => 'no_key']);

        $res = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'KakaoAK ' . $key,
        ])->get('https://dapi.kakao.com/v2/local/search/keyword.json', [
            'query' => $q,
            'size' => 15,
        ]);

        return response()->json($res->json());
    }
}
