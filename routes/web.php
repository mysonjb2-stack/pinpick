<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CategoryController;
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
    Route::get('/places/{place}/edit', [PlaceController::class, 'edit'])->name('places.edit');
    Route::put('/places/{place}', [PlaceController::class, 'update'])->name('places.update');
    Route::delete('/places/{place}', [PlaceController::class, 'destroy'])->name('places.destroy');
    Route::delete('/api/place-images/{placeImage}', [PlaceController::class, 'destroyImage'])->name('api.place-images.destroy');
    Route::patch('/api/places/{place}/reorder', [PlaceController::class, 'reorder'])->name('api.places.reorder');

    // 카테고리 전체보기
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    // 사용자 카테고리 관리
    Route::get('/api/categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::post('/api/categories', [CategoryController::class, 'store'])->name('api.categories.store');
    Route::patch('/api/categories/reorder', [CategoryController::class, 'reorder'])->name('api.categories.reorder');
    Route::patch('/api/categories/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
    Route::delete('/api/categories/{category}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');
});

// 검색 API 프록시 (비로그인도 가능)
Route::get('/api/search/overseas/autocomplete', [PlaceController::class, 'autocompleteOverseas'])->name('api.search.overseas.autocomplete');
Route::get('/api/search/overseas', [PlaceController::class, 'searchOverseas'])->name('api.search.overseas');
Route::get('/api/search', [PlaceController::class, 'search'])->name('api.search');
Route::get('/api/place/detail', [PlaceController::class, 'placeDetail'])->name('api.place.detail');
Route::get('/api/geocode/reverse', [PlaceController::class, 'reverseGeocode'])->name('api.geocode.reverse');
Route::get('/api/geocode/forward', [PlaceController::class, 'forwardGeocodeApi'])->name('api.geocode.forward');
Route::get('/api/phone/fallback', [PlaceController::class, 'phoneFallback'])->name('api.phone.fallback');
