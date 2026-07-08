<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PlaceController;
use App\Http\Controllers\Admin\AdminUserController;
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

    Route::get('admins', [AdminUserController::class, 'index'])->name('admin.admins.index');
    Route::post('admins', [AdminUserController::class, 'store'])->name('admin.admins.store');
    Route::patch('admins/{admin}/password', [AdminUserController::class, 'updatePassword'])->name('admin.admins.password');
    Route::delete('admins/{admin}', [AdminUserController::class, 'destroy'])->name('admin.admins.destroy');
});
