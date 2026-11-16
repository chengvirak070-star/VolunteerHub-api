<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * GET /feed?tab=for_you|following|events
     * for_you: all visible posts · following: people I follow + my own · events: posts linked to an event
     */
    public function feed(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Post::forViewer($user)->visible();

        match ($request->input('tab', 'for_you')) {
            'following' => $query->where(fn ($q) => $q
                ->whereIn('user_id', $user->following()->pluck('users.id'))
                ->orWhere('user_id', $user->id)),
            'events' => $query->whereNotNull('event_id'),
            default => null,
        };

        return PostResource::collection($query->latest()->latest('id')->paginate(15)->withQueryString());
    }

    /** GET /users/{username}/posts */
    public function forUser(Request $request, string $username): AnonymousResourceCollection
    {
        $author = User::active()->where('username', $username)->firstOrFail();

        $posts = Post::forViewer($request->user())
            ->visible()
            ->where('user_id', $author->id)
            ->latest()->latest('id')
            ->paginate(15);

        return PostResource::collection($posts);
    }

    /** POST /posts (body, image, event_id) — multipart/form-data when sending an image. */
    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->safe()->only(['body', 'event_id']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('posts', 'public');
        }

        $post = $request->user()->posts()->create($data);
        $post->syncHashtags();

        return (new PostResource(Post::forViewer($request->user())->find($post->id)))
            ->response()->setStatusCode(201);
    }

    /** GET /posts/{post} */
    public function show(Request $request, Post $post): PostResource
    {
        Gate::authorize('view', $post);

        return new PostResource(Post::forViewer($request->user())->find($post->id));
    }

    /** PUT /posts/{post} — owner only. */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        Gate::authorize('update', $post);
        $data = $request->safe()->only(['body', 'event_id']);

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $data['image_path'] = $request->hasFile('image')
                ? $request->file('image')->store('posts', 'public')
                : null;
        }

        $post->update($data);
        $post->syncHashtags();

        return new PostResource(Post::forViewer($request->user())->find($post->id));
    }

    /** DELETE /posts/{post} — owner only (admins too). */
    public function destroy(Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }
        $post->delete();

        return response()->json(['data' => ['message' => 'Post deleted.']]);
    }
}
