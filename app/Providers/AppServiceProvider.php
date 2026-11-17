<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Event;
use App\Models\HourLog;
use App\Models\Organization;
use App\Models\PersonalAccessToken;
use App\Models\Post;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Login tokens: don't write "last used" to the database on every single request.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Short names in polymorphic columns (reports.reportable_type, notifications.notifiable_type, tokens).
        Relation::enforceMorphMap([
            'user' => User::class,
            'post' => Post::class,
            'comment' => Comment::class,
            'event' => Event::class,
            'organization' => Organization::class,
            'hour_log' => HourLog::class,
        ]);

        // 5 login attempts per minute per email + IP.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        // Post creation: 10 per minute per user.
        RateLimiter::for('posts', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
