<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index(Request $request)
    {
        $places = collect();
        if ($request->user()) {
            $places = Place::where('user_id', $request->user()->id)
                ->where('is_visible', true)
                ->whereNotNull('lat')->whereNotNull('lng')
                ->with('category')
                ->get();
        }

        return view('map.index', [
            'places' => $places,
            'naverClientId' => config('services.naver_map.client_id'),
        ]);
    }
}
