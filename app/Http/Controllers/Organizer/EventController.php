<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreEventRequest;
use App\Http\Requests\Organizer\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /** GET /organizer/events — my organization's events, every status. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $events = Event::forViewer($user)
            ->where('organization_id', $user->organization?->id ?? 0)
            ->latest('starts_at')
            ->paginate(15);

        return EventResource::collection($events);
    }

    /** POST /organizer/events — starts as "pending" until an admin approves it. */
    public function store(StoreEventRequest $request): JsonResponse
    {
        Gate::authorize('create', Event::class);
        $data = $request->safe()->except('cover');

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('events', 'public');
        }

        $event = $request->user()->organization->events()->create($data + ['status' => EventStatus::Pending]);

        return (new EventResource(Event::forViewer($request->user())->find($event->id)))
            ->response()->setStatusCode(201);
    }

    /** PUT /organizer/events/{event} */
    public function update(UpdateEventRequest $request, Event $event): EventResource
    {
        Gate::authorize('manage', $event);
        $data = $request->safe()->except('cover');

        if ($request->hasFile('cover')) {
            $old = $event->cover_path;
            $data['cover_path'] = $request->file('cover')->store('events', 'public');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
        }

        $event->update($data);
        $event->refreshCapacityStatus(); // capacity may have changed

        return new EventResource(Event::forViewer($request->user())->find($event->id));
    }

    /** DELETE /organizer/events/{event} */
    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('manage', $event);

        if ($event->cover_path) {
            Storage::disk('public')->delete($event->cover_path);
        }
        $event->delete();

        return response()->json(['data' => ['message' => 'Event deleted.']]);
    }

    /** GET /organizer/events/{event}/registrations */
    public function registrations(Event $event): AnonymousResourceCollection
    {
        Gate::authorize('manage', $event);

        $registrations = $event->registrations()
            ->with(['user.organization', 'hourLog'])
            ->latest()
            ->paginate(50);

        return RegistrationResource::collection($registrations);
    }
}
