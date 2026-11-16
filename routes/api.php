<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
});

/*
|--------------------------------------------------------------------------
| Logged in (Bearer token) and not blocked
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Profiles
    Route::put('profile', [UserController::class, 'updateProfile']);
    Route::get('users/{username}', [UserController::class, 'show']);
    Route::get('users/{username}/followers', [UserController::class, 'followers']);
    Route::get('users/{username}/following', [UserController::class, 'following']);

    // Follow (by numeric id)
    Route::post('users/{user}/follow', [FollowController::class, 'store'])->whereNumber('user');
    Route::delete('users/{user}/follow', [FollowController::class, 'destroy'])->whereNumber('user');
});
