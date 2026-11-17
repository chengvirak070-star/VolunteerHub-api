<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HoursController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Organizer;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SavedEventController;
use App\Http\Controllers\SearchController;
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

    // Feed and posts
    Route::get('feed', [PostController::class, 'feed']);
    Route::post('posts', [PostController::class, 'store'])->middleware('throttle:posts');
    Route::get('posts/{post}', [PostController::class, 'show']);
    Route::put('posts/{post}', [PostController::class, 'update']);
    Route::delete('posts/{post}', [PostController::class, 'destroy']);
    Route::post('posts/{post}/like', [LikeController::class, 'store']);
    Route::delete('posts/{post}/like', [LikeController::class, 'destroy']);
    Route::get('posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('posts/{post}/comments', [CommentController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store'])->middleware(['role:organizer,admin', 'throttle:20,1']);

    // Events
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/{event}', [EventController::class, 'show']);
    Route::post('events/{event}/register', [EventRegistrationController::class, 'store']);
    Route::delete('events/{event}/register', [EventRegistrationController::class, 'destroy']);
    Route::post('events/{event}/save', [SavedEventController::class, 'store']);
    Route::delete('events/{event}/save', [SavedEventController::class, 'destroy']);

    // Me
    Route::put('profile', [UserController::class, 'updateProfile']);
    Route::get('me/hours', [HoursController::class, 'mine']);
    Route::get('me/certificate', [HoursController::class, 'certificate']);
    Route::get('me/saved-events', [SavedEventController::class, 'index']);

    // Users and profiles
    Route::get('users/{username}', [UserController::class, 'show']);
    Route::get('users/{username}/posts', [PostController::class, 'forUser']);
    Route::get('users/{username}/events', [UserController::class, 'events']);
    Route::get('users/{username}/hours', [HoursController::class, 'forUser']);
    Route::get('users/{username}/followers', [UserController::class, 'followers']);
    Route::get('users/{username}/following', [UserController::class, 'following']);
    Route::post('users/{user}/follow', [FollowController::class, 'store'])->whereNumber('user');
    Route::delete('users/{user}/follow', [FollowController::class, 'destroy'])->whereNumber('user');

    // Search
    Route::get('search', [SearchController::class, 'index']);
    Route::get('hashtags/trending', [SearchController::class, 'trending']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'read']);

    // Organizer: own events only
    Route::middleware('role:organizer')->prefix('organizer')->group(function () {
        Route::get('events', [Organizer\EventController::class, 'index']);
        Route::post('events', [Organizer\EventController::class, 'store']);
        Route::put('events/{event}', [Organizer\EventController::class, 'update']);
        Route::delete('events/{event}', [Organizer\EventController::class, 'destroy']);
        Route::get('events/{event}/registrations', [Organizer\EventController::class, 'registrations']);
        Route::post('registrations/{registration}/attendance', [Organizer\AttendanceController::class, 'store']);
    });
});
