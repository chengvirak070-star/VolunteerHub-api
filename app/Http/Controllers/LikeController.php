<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Notifications\PostLiked;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class LikeController extends Controller
{
    /** POST /posts/{post}/like */
    public function store(Request $request, Post $post): JsonResponse
    {
        Gate::authorize('view', $post);
        $user = $request->user();

        $changes = $post->likers()->syncWithoutDetaching([$user->id]);

        // Notify the author only for a new like, never for liking your own post,
        // and at most once a day per person per post (like → unlike → like again stays quiet).
        if (! empty($changes['attached'])
            && $post->user_id !== $user->id
            && Cache::add("like-notified:{$post->id}:{$user->id}", true, now()->addDay())) {
            $post->user->notify(new PostLiked($post, $user));
        }

        return $this->state($post, true);
    }

    /** DELETE /posts/{post}/like */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        $post->likers()->detach($request->user()->id);

        return $this->state($post, false);
    }

    private function state(Post $post, bool $liked): JsonResponse
    {
        return response()->json([
            'data' => [
                'post_id' => $post->id,
                'is_liked_by_me' => $liked,
                'likes_count' => $post->likers()->count(),
            ],
        ]);
    }
}
