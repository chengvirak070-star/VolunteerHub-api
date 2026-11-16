<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Notifications\RegistrationConfirmed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EventRegistrationController extends Controller
{
    /**
     * POST /events/{event}/register
     * Rules: event must be "open", one registration per person, and the event
     * becomes "full" when the last spot is taken.
     */
    public function store(Request $request, Event $event): EventResource
    {
        Gate::authorize('view', $event);
        Gate::authorize('register', $event);
        $user = $request->user();

        DB::transaction(function () use ($event, $user) {
            // Lock the event row so two people can't take the last spot at the same time.
            $locked = Event::whereKey($event->id)->lockForUpdate()->first();

            if ($locked->status !== EventStatus::Open) {
                abort(422, $locked->status === EventStatus::Full
                    ? 'This event is full.'
                    : 'This event is not open for registration.');
            }

            $registration = EventRegistration::firstOrNew(['event_id' => $locked->id, 'user_id' => $user->id]);

            if ($registration->exists && $registration->status !== RegistrationStatus::Cancelled) {
                abort(422, 'You have already joined this event.');
            }

            if ($locked->activeRegistrations()->count() >= $locked->capacity) {
                $locked->refreshCapacityStatus();
                abort(422, 'This event is full.');
            }

            $registration->status = RegistrationStatus::Registered;
            $registration->save();

            $locked->refreshCapacityStatus();
        });

        $user->notify(new RegistrationConfirmed($event));

        return new EventResource(Event::forViewer($user)->findOrFail($event->id));
    }

    /** DELETE /events/{event}/register — cancel; a "full" event becomes "open" again. */
    public function destroy(Request $request, Event $event): EventResource
    {
        $user = $request->user();

        DB::transaction(function () use ($event, $user) {
            $locked = Event::whereKey($event->id)->lockForUpdate()->first();

            $registration = EventRegistration::where('event_id', $locked->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $registration || $registration->status === RegistrationStatus::Cancelled) {
                abort(422, 'You have not joined this event.');
            }

            if ($registration->status !== RegistrationStatus::Registered) {
                abort(422, 'Attendance is already marked, so you can no longer cancel.');
            }

            $registration->update(['status' => RegistrationStatus::Cancelled]);
            $locked->refreshCapacityStatus();
        });

        return new EventResource(Event::forViewer($user)->findOrFail($event->id));
    }
}
