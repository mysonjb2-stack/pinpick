<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index(Request $request)
    {
        $places = collect();
        $categories = collect();
        if ($request->user()) {
            CategoryController::ensureUserCategories($request->user());
            $categories = \App\Models\Category::where('user_id', $request->user()->id)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'icon', 'color']);
            $places = Place::where('user_id', $request->user()->id)
                ->where('is_visible', true)
                ->whereNotNull('lat')->whereNotNull('lng')
                ->select(['id', 'name', 'category_id', 'lat', 'lng', 'is_overseas', 'status', 'thumbnail', 'user_id'])
                ->with(['category:id,name,icon,color', 'images' => fn ($q) => $q->select(['id', 'place_id', 'path', 'sort_order'])->orderBy('sort_order')])
                ->get();
        }

        $country = $request->header('CF-IPCountry') ?: $request->header('X-IPCountry') ?: '';
        if (! $country) {
            $locale = strtolower((string) $request->header('Accept-Language', ''));
            $country = str_contains($locale, 'ko') ? 'KR' : 'KR';
        }
        $defaultScope = strtoupper($country) === 'KR' ? 'domestic' : 'overseas';

        return view('map.index', [
            'places' => $places,
            'categories' => $categories,
            'naverClientId' => config('services.naver_map.client_id'),
            'googleMapsKey' => config('services.google_maps.api_key'),
            'defaultScope' => $defaultScope,
        ]);
    }
}
