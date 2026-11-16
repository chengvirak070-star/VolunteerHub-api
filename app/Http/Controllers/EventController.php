<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    /**
     * GET /events?search=&category=environment,health&from=&to=&location=&has_spots=1&per_page=
     * Normal users: published events from today on. Admin: every status (add ?status=pending).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Event::forViewer($user);

        if ($user?->isAdmin()) {
            $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        } else {
            $query->published();
        }

        if ($search = trim((string) $request->input('search'))) {
            $like = '%'.mb_strtolower($search).'%';
            $query->where(fn ($q) => $q
                ->whereRaw('LOWER(title) LIKE ?', [$like])
                ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                ->orWhereRaw('LOWER(location) LIKE ?', [$like]));
        }

        if ($request->filled('category')) {
            $slugs = array_filter(explode(',', (string) $request->input('category')));
            $query->whereHas('category', fn ($q) => $q->whereIn('slug', $slugs));
        }

        if ($location = trim((string) $request->input('location'))) {
            $query->whereRaw('LOWER(location) LIKE ?', ['%'.mb_strtolower($location).'%']);
        }

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfDay();
        $query->where('starts_at', '>=', $from);
        if ($request->filled('to')) {
            $query->where('starts_at', '<=', Carbon::parse($request->input('to'))->endOfDay());
        }

        if ($request->boolean('has_spots')) {
            $query->where('status', EventStatus::Open);
        }

        $perPage = min(50, max(1, (int) $request->input('per_page', 15)));

        return EventResource::collection($query->orderBy('starts_at')->paginate($perPage)->withQueryString());
    }

    /** GET /events/{event} — includes a few attendees and how many people you follow are going. */
    public function show(Request $request, Event $event): EventResource
    {
        Gate::authorize('view', $event);
        $user = $request->user();

        $event = Event::forViewer($user)
            ->with(['organization' => fn ($q) => $q->withCount('events')])
            ->findOrFail($event->id);

        // Organizer's follower count + whether I follow them
        $event->organization->user?->loadCount('followers');
        if ($user && $event->organization->user) {
            $event->organization->user->setAttribute(
                'is_following',
                $user->following()->whereKey($event->organization->user_id)->exists(),
            );
        }

        $event->setRelation('attendees_preview', $event->participants()->with('organization')->latest('event_registrations.created_at')->limit(5)->get());
        $event->setAttribute('friends_going_count', $user
            ? $event->participants()->whereIn('users.id', $user->following()->pluck('users.id'))->count()
            : 0);

        return new EventResource($event);
    }
}
