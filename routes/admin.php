<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PlaceController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\CurationController;
use App\Http\Controllers\Admin\CollectorController;
use App\Http\Controllers\Admin\PersonaController;
use Illuminate\Support\Facades\Route;

Route::get('login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('login', [AuthController::class, 'login']);

Route::middleware('admin.auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('users/{user}', [UserController::class, 'show'])->name('admin.users.show');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('places', [PlaceController::class, 'index'])->name('admin.places.index');
    Route::get('places/{place}', [PlaceController::class, 'show'])->name('admin.places.show');
    Route::delete('places/{place}', [PlaceController::class, 'destroy'])->name('admin.places.destroy');

    Route::get('curations', [CurationController::class, 'index'])->name('admin.curations.index');
    Route::get('curations/create', [CurationController::class, 'create'])->name('admin.curations.create');
    Route::post('curations', [CurationController::class, 'store'])->name('admin.curations.store');
    Route::get('curations/{curation}/edit', [CurationController::class, 'edit'])->name('admin.curations.edit');
    Route::put('curations/{curation}', [CurationController::class, 'update'])->name('admin.curations.update');
    Route::delete('curations/{curation}', [CurationController::class, 'destroy'])->name('admin.curations.destroy');
    Route::post('curations/bulk-destroy', [CurationController::class, 'bulkDestroy'])->name('admin.curations.bulk-destroy');
    Route::post('curations/{curation}/toggle-publish', [CurationController::class, 'togglePublish'])->name('admin.curations.toggle-publish');
    Route::post('curations/{curation}/places', [CurationController::class, 'addPlace'])->name('admin.curations.add-place');
    Route::put('curations/places/{place}', [CurationController::class, 'updatePlace'])->name('admin.curations.update-place');
    Route::post('curations/places/{place}/replace', [CurationController::class, 'replacePlace'])->name('admin.curations.replace-place');
    Route::delete('curations/places/{place}', [CurationController::class, 'removePlace'])->name('admin.curations.remove-place');
    Route::post('curations/{curation}/reorder', [CurationController::class, 'reorderPlaces'])->name('admin.curations.reorder');
    Route::post('curations/places/{place}/photos', [CurationController::class, 'uploadPlacePhotos'])->name('admin.curations.upload-photos');
    Route::post('curations/places/{place}/photos/url', [CurationController::class, 'uploadPlacePhotoFromUrl'])->name('admin.curations.upload-photo-url');
    Route::put('curations/places/{place}/photos/reorder', [CurationController::class, 'reorderPlacePhotos'])->name('admin.curations.reorder-photos');
    Route::delete('curations/places/{place}/photos', [CurationController::class, 'deletePlacePhoto'])->name('admin.curations.delete-photo');
    Route::post('curations/places/{place}/enrich-naver', [CurationController::class, 'enrichNaver'])->name('admin.curations.enrich-naver');
    Route::post('curations/places/{place}/match-google', [CurationController::class, 'matchGooglePlace'])->name('admin.curations.match-google');
    Route::delete('curations/places/{place}/clear-google', [CurationController::class, 'clearGooglePlace'])->name('admin.curations.clear-google');
    Route::post('curations/{curation}/approve', [CurationController::class, 'approve'])->name('admin.curations.approve');
    Route::post('curations/{curation}/reject', [CurationController::class, 'reject'])->name('admin.curations.reject');
    Route::post('curations/{curation}/suspend', [CurationController::class, 'suspend'])->name('admin.curations.suspend');
    Route::get('curations/places/{place}/tour-images', [CurationController::class, 'searchTourImages'])->name('admin.curations.tour-images');

    Route::get('collector', [CollectorController::class, 'index'])->name('admin.collector.index');
    Route::post('collector/extract-youtube', [CollectorController::class, 'extractFromYoutube'])->name('admin.collector.extract-youtube');
    Route::post('collector/extract-text', [CollectorController::class, 'extractFromText'])->name('admin.collector.extract-text');
    Route::get('collector/search-kakao', [CollectorController::class, 'searchKakao'])->name('admin.collector.search-kakao');
    Route::post('collector/create-draft', [CollectorController::class, 'createDraft'])->name('admin.collector.create-draft');

    Route::get('personas', [PersonaController::class, 'index'])->name('admin.personas.index');
    Route::post('personas', [PersonaController::class, 'store'])->name('admin.personas.store');
    Route::put('personas/{persona}', [PersonaController::class, 'update'])->name('admin.personas.update');
    Route::delete('personas/{persona}', [PersonaController::class, 'destroy'])->name('admin.personas.destroy');
    Route::post('personas/seed', [PersonaController::class, 'seed'])->name('admin.personas.seed');
    Route::post('personas/{persona}/regenerate-avatar', [PersonaController::class, 'regenerateAvatar'])->name('admin.personas.regenerate-avatar');
    Route::post('personas/regenerate-all-avatars', [PersonaController::class, 'regenerateAllAvatars'])->name('admin.personas.regenerate-all-avatars');

    Route::get('admins', [AdminUserController::class, 'index'])->name('admin.admins.index');
    Route::post('admins', [AdminUserController::class, 'store'])->name('admin.admins.store');
    Route::patch('admins/{admin}/password', [AdminUserController::class, 'updatePassword'])->name('admin.admins.password');
    Route::delete('admins/{admin}', [AdminUserController::class, 'destroy'])->name('admin.admins.destroy');
});
