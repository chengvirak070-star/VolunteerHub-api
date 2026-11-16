<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Base for all VolunteerHub notifications. Stored in the database "notifications" table.
 * Every notification saves: type, message, link (web app path), and optionally the actor.
 */
abstract class AppNotification extends Notification
{
    abstract protected function type(): string;

    abstract protected function message(): string;

    abstract protected function link(): ?string;

    protected function actor(): ?User
    {
        return null;
    }

    /** Extra ids for the frontend. */
    protected function extra(): array
    {
        return [];
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->type();
    }

    public function toArray(object $notifiable): array
    {
        $actor = $this->actor();

        return [
            'type' => $this->type(),
            'message' => $this->message(),
            'link' => $this->link(),
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
                'username' => $actor->username,
                'avatar_url' => $actor->avatarUrl(),
            ] : null,
        ] + $this->extra();
    }
}
