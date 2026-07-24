<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PlaceController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\CurationController;
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
    Route::post('curations/{curation}/toggle-publish', [CurationController::class, 'togglePublish'])->name('admin.curations.toggle-publish');
    Route::post('curations/{curation}/places', [CurationController::class, 'addPlace'])->name('admin.curations.add-place');
    Route::put('curations/places/{place}', [CurationController::class, 'updatePlace'])->name('admin.curations.update-place');
    Route::delete('curations/places/{place}', [CurationController::class, 'removePlace'])->name('admin.curations.remove-place');
    Route::post('curations/{curation}/reorder', [CurationController::class, 'reorderPlaces'])->name('admin.curations.reorder');
    Route::post('curations/places/{place}/photos', [CurationController::class, 'uploadPlacePhotos'])->name('admin.curations.upload-photos');
    Route::post('curations/places/{place}/photos/url', [CurationController::class, 'uploadPlacePhotoFromUrl'])->name('admin.curations.upload-photo-url');
    Route::put('curations/places/{place}/photos/reorder', [CurationController::class, 'reorderPlacePhotos'])->name('admin.curations.reorder-photos');
    Route::delete('curations/places/{place}/photos', [CurationController::class, 'deletePlacePhoto'])->name('admin.curations.delete-photo');
    Route::post('curations/places/{place}/enrich-naver', [CurationController::class, 'enrichNaver'])->name('admin.curations.enrich-naver');
    Route::post('curations/{curation}/approve', [CurationController::class, 'approve'])->name('admin.curations.approve');
    Route::post('curations/{curation}/reject', [CurationController::class, 'reject'])->name('admin.curations.reject');
    Route::post('curations/{curation}/suspend', [CurationController::class, 'suspend'])->name('admin.curations.suspend');
    Route::get('curations/places/{place}/tour-images', [CurationController::class, 'searchTourImages'])->name('admin.curations.tour-images');

    Route::get('admins', [AdminUserController::class, 'index'])->name('admin.admins.index');
    Route::post('admins', [AdminUserController::class, 'store'])->name('admin.admins.store');
    Route::patch('admins/{admin}/password', [AdminUserController::class, 'updatePassword'])->name('admin.admins.password');
    Route::delete('admins/{admin}', [AdminUserController::class, 'destroy'])->name('admin.admins.destroy');
});
