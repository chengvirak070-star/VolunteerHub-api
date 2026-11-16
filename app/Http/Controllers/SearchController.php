<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Event;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * GET /search?q=&type=all|people|events|posts|hashtags
     * "all" returns the top 6 of each kind; a single type returns up to 15.
     * A query starting with # searches posts by hashtag.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:all,people,events,posts,hashtags'],
        ]);

        $q = trim((string) $request->input('q'));
        $type = $request->input('type', 'all');
        $limit = $type === 'all' ? 6 : 15;
        $user = $request->user();
        $like = '%'.mb_strtolower(ltrim($q, '#')).'%';

        $sections = [
            'people' => fn () => UserResource::collection(
                User::active()
                    ->withProfileStats($user)
                    ->where('id', '!=', $user->id)
                    ->where(fn ($w) => $w
                        ->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(bio, \'\')) LIKE ?', [$like]))
                    ->orderByDesc('followers_count')
                    ->limit($limit)->get()
            ),
            'events' => fn () => EventResource::collection(
                Event::forViewer($user)->published()
                    ->where('starts_at', '>=', now()->startOfDay())
                    ->where(fn ($w) => $w
                        ->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(location) LIKE ?', [$like]))
                    ->orderBy('starts_at')
                    ->limit($limit)->get()
            ),
            'posts' => fn () => PostResource::collection(
                Post::forViewer($user)->visible()
                    ->when(
                        str_starts_with($q, '#'),
                        fn ($w) => $w->whereHas('hashtags', fn ($h) => $h->where('name', mb_strtolower(ltrim($q, '#')))),
                        fn ($w) => $w->whereRaw('LOWER(body) LIKE ?', [$like]),
                    )
                    ->latest()->limit($limit)->get()
            ),
            'hashtags' => fn () => Hashtag::query()
                ->whereRaw('LOWER(name) LIKE ?', [$like])
                ->withCount('posts')
                ->orderByDesc('posts_count')
                ->limit($limit)->get()
                ->map(fn ($h) => ['name' => $h->name, 'posts_count' => $h->posts_count]),
        ];

        $wanted = $type === 'all' ? array_keys($sections) : [$type];
        $data = [];
        foreach ($wanted as $key) {
            $data[$key] = $sections[$key]();
        }

        return response()->json(['data' => $data, 'meta' => ['q' => $q, 'type' => $type]]);
    }

    /** GET /hashtags/trending — most used in visible posts from the last 30 days. */
    public function trending(): JsonResponse
    {
        $tags = Hashtag::query()
            ->select('hashtags.id', 'hashtags.name', DB::raw('COUNT(posts.id) as posts_count'))
            ->join('hashtag_post', 'hashtag_post.hashtag_id', '=', 'hashtags.id')
            ->join('posts', 'posts.id', '=', 'hashtag_post.post_id')
            ->where('posts.status', 'visible')
            ->where('posts.created_at', '>=', now()->subDays(30))
            ->groupBy('hashtags.id', 'hashtags.name')
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get()
            ->map(fn ($h) => ['name' => $h->name, 'posts_count' => (int) $h->posts_count]);

        return response()->json(['data' => $tags]);
    }
}
