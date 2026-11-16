<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FollowController extends Controller
{
    /** POST /users/{user}/follow */
    public function store(Request $request, User $user): JsonResponse
    {
        Gate::authorize('follow', $user);

        $changes = $request->user()->following()->syncWithoutDetaching([$user->id]);

        if (! empty($changes['attached'])) {
            $user->notify(new NewFollower($request->user()));
        }

        return $this->state($user, true);
    }

    /** DELETE /users/{user}/follow */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $request->user()->following()->detach($user->id);

        return $this->state($user, false);
    }

    private function state(User $user, bool $following): JsonResponse
    {
        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'is_following' => $following,
                'followers_count' => $user->followers()->count(),
            ],
        ]);
    }
}
