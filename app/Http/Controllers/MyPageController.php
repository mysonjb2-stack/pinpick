<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Trip;
use Illuminate\Http\Request;

class MyPageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $placeCount = 0;
        $trips = collect();
        if ($user) {
            $placeCount = Place::where('user_id', $user->id)->count();
            $trips = Trip::where('user_id', $user->id)->latest()->get();
        }
        return view('mypage.index', compact('user', 'placeCount', 'trips'));
    }
}
