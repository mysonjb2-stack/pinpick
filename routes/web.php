<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\PlaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/map', [MapController::class, 'index'])->name('map');
Route::view('/explore', 'explore.index')->name('explore');
Route::get('/mypage', [MyPageController::class, 'index'])->name('mypage');

Route::get('/login', fn() => view('auth.login'))->name('login');

// 소셜 로그인
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'kakao|google');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'kakao|google');
Route::post('/logout', [SocialAuthController::class, 'logout'])->name('logout');

// 장소 (create 폼은 비로그인도 접근 가능 - 게스트는 localStorage 저장)
Route::get('/places/create', [PlaceController::class, 'create'])->name('places.create');
Route::middleware('auth')->group(function () {
    Route::post('/places', [PlaceController::class, 'store'])->name('places.store');
    Route::get('/places/{place}', [PlaceController::class, 'show'])->name('places.show');
    Route::delete('/places/{place}', [PlaceController::class, 'destroy'])->name('places.destroy');
});

// 카카오 로컬 API 검색 프록시 (비로그인도 검색만 가능)
Route::get('/api/search', [PlaceController::class, 'search'])->name('api.search');
