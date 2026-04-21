<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PublicPlaceController;
use App\Http\Controllers\TrendingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/map', [MapController::class, 'index'])->name('map');
Route::view('/explore', 'explore.index')->name('explore');
Route::get('/mypage', [MyPageController::class, 'index'])->name('mypage');
Route::middleware('auth')->group(function () {
    Route::get('/mypage/profile/edit', [MyPageController::class, 'editProfile'])->name('mypage.profile.edit');
    Route::post('/mypage/profile', [MyPageController::class, 'updateProfile'])->name('mypage.profile.update');
    Route::delete('/mypage/account', [MyPageController::class, 'destroyAccount'])->name('mypage.account.destroy');
    Route::get('/mypage/categories', [MyPageController::class, 'categories'])->name('mypage.categories');
});
Route::get('/notices', [MyPageController::class, 'notices'])->name('notices');
Route::get('/faq', [MyPageController::class, 'faq'])->name('faq');
Route::get('/terms', [MyPageController::class, 'terms'])->name('terms');

Route::get('/login', fn() => view('auth.login'))->name('login');

// 소셜 로그인
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'kakao|google|naver');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'kakao|google|naver');
Route::post('/logout', [SocialAuthController::class, 'logout'])->name('logout');

// 장소 (create 폼은 비로그인도 접근 가능 - 게스트는 localStorage 저장)
Route::get('/places/create', [PlaceController::class, 'create'])->name('places.create');

// 비로그인 전용 장소 상세 (localStorage 데이터를 클라이언트에서 hydrate)
Route::get('/guest/places/{localId}', [PlaceController::class, 'showGuest'])
    ->where('localId', '[A-Za-z0-9_\-]+')
    ->name('guest.places.show');
Route::middleware('auth')->group(function () {
    Route::post('/places', [PlaceController::class, 'store'])->name('places.store');
    Route::get('/places/{place}', [PlaceController::class, 'show'])->name('places.show');
    Route::get('/places/{place}/edit', [PlaceController::class, 'edit'])->name('places.edit');
    Route::put('/places/{place}', [PlaceController::class, 'update'])->name('places.update');
    Route::delete('/places/{place}', [PlaceController::class, 'destroy'])->name('places.destroy');
    Route::delete('/api/place-images/{placeImage}', [PlaceController::class, 'destroyImage'])->name('api.place-images.destroy');
    Route::patch('/api/places/{place}/reorder', [PlaceController::class, 'reorder'])->name('api.places.reorder');
    Route::post('/api/places/reorder-all', [PlaceController::class, 'bulkReorder'])->name('api.places.reorder-all');
    Route::post('/api/places/import-guest', [PlaceController::class, 'importGuest'])->name('api.places.import-guest');

    // 카테고리 전체보기
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    // 사용자 카테고리 관리
    Route::get('/api/categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::post('/api/categories', [CategoryController::class, 'store'])->name('api.categories.store');
    Route::patch('/api/categories/reorder', [CategoryController::class, 'reorder'])->name('api.categories.reorder');
    Route::patch('/api/categories/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
    Route::delete('/api/categories/{category}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');
});

// 테마별 장소 필터
Route::middleware('auth')->get('/api/places', [PlaceController::class, 'placesByTheme'])->name('api.places.by_theme');
Route::get('/api/places/public', [PlaceController::class, 'placesPublicByTheme'])->name('api.places.public');

// 검색 API 프록시 (비로그인도 가능)
Route::get('/api/search/overseas/autocomplete', [PlaceController::class, 'autocompleteOverseas'])->name('api.search.overseas.autocomplete');
Route::get('/api/search/overseas', [PlaceController::class, 'searchOverseas'])->name('api.search.overseas');
Route::get('/api/search', [PlaceController::class, 'search'])->name('api.search');
Route::get('/api/place/detail', [PlaceController::class, 'placeDetail'])->name('api.place.detail');
Route::get('/api/geocode/reverse', [PlaceController::class, 'reverseGeocode'])->name('api.geocode.reverse');
Route::get('/api/geocode/forward', [PlaceController::class, 'forwardGeocodeApi'])->name('api.geocode.forward');
Route::get('/api/phone/fallback', [PlaceController::class, 'phoneFallback'])->name('api.phone.fallback');
Route::get('/api/static-map', [PlaceController::class, 'staticMap'])->name('api.static-map');

// ── personal-only-v1: 사람들/공개 장소 라우트 비활성화 (v1-with-people 태그에서 복원 가능) ──
// // 트렌딩 장소 (비로그인 접근 가능)
// Route::get('/api/places/trending', [TrendingController::class, 'trending'])->name('api.places.trending');
// Route::get('/api/places/trending/list', [TrendingController::class, 'list'])->name('api.places.trending.list');
//
// // 트렌딩 전체보기 페이지
// Route::view('/explore/trending', 'explore.trending')->name('explore.trending');
//
// // 공개 장소 상세 (비로그인 접근 가능)
// Route::get('/place/{place}', [PublicPlaceController::class, 'show'])->name('public.place.show');
// Route::post('/place/{place}/copy', [PublicPlaceController::class, 'copyToMine'])
//     ->middleware('auth')->name('public.place.copy');
