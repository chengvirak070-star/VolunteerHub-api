<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Notifications\PostCommented;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    /** GET /posts/{post}/comments — oldest first, 15 per page, hidden comments left out. */
    public function index(Post $post): AnonymousResourceCollection
    {
        Gate::authorize('view', $post);

        $comments = $post->comments()
            ->visible()
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->with('user.organization')
            ->oldest()->oldest('id')
            ->paginate(15);

        return CommentResource::collection($comments);
    }

    /** POST /posts/{post}/comments */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        Gate::authorize('view', $post);
        $user = $request->user();

        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'body' => $request->validated('body'),
        ]);

        if ($post->user_id !== $user->id) {
            $post->user->notify(new PostCommented($comment, $user));
        }

        return (new CommentResource($comment->load('user.organization')))
            ->response()->setStatusCode(201);
    }

    /** DELETE /comments/{comment} */
    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);
        $comment->delete();

        return response()->json(['data' => ['message' => 'Comment deleted.']]);
    }
}
