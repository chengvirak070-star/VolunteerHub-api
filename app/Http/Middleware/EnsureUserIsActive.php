<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocked users are rejected even if they still hold an old token.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBlocked()) {
            $user->currentAccessToken()?->delete();

            return response()->json(['message' => 'Your account has been blocked.'], 403);
        }

        return $next($request);
    }
}
