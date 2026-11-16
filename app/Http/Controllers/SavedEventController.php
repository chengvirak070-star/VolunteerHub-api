<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SavedEventController extends Controller
{
    /** GET /me/saved-events */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $events = Event::forViewer($user)
            ->published()
            ->whereHas('savedBy', fn ($q) => $q->where('users.id', $user->id))
            ->orderBy('starts_at')
            ->paginate(15);

        return EventResource::collection($events);
    }

    /** POST /events/{event}/save */
    public function store(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('view', $event);
        $request->user()->savedEvents()->syncWithoutDetaching([$event->id]);

        return response()->json(['data' => ['event_id' => $event->id, 'is_saved_by_me' => true]]);
    }

    /** DELETE /events/{event}/save */
    public function destroy(Request $request, Event $event): JsonResponse
    {
        $request->user()->savedEvents()->detach($event->id);

        return response()->json(['data' => ['event_id' => $event->id, 'is_saved_by_me' => false]]);
    }
}
