<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    /** Normal users only see published events; organizers see their own; admins see all. */
    public function view(?User $user, Event $event): Response
    {
        if (in_array($event->status, [EventStatus::Open, EventStatus::Full, EventStatus::Closed], true)) {
            return Response::allow();
        }

        return $user && ($user->isAdmin() || $this->owns($user, $event))
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Organizer needs an approved organization to create events. */
    public function create(User $user): Response
    {
        return $user->hasApprovedOrganization()
            ? Response::allow()
            : Response::deny('Your organization must be approved by an admin before you can post events.');
    }

    /** Edit, delete, see registrations, mark attendance: the event's own organizer only. */
    public function manage(User $user, Event $event): Response
    {
        return $this->owns($user, $event)
            ? Response::allow()
            : Response::deny('Only this event\'s organizer can do this.');
    }

    public function register(User $user, Event $event): Response
    {
        if ($this->owns($user, $event)) {
            return Response::deny('You cannot join your own event.');
        }

        return Response::allow();
    }

    private function owns(User $user, Event $event): bool
    {
        return $user->isOrganizer() && $event->organization?->user_id === $user->id;
    }
}
