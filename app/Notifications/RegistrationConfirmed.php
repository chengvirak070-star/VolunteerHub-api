<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;

class RegistrationConfirmed extends AppNotification
{
    public function __construct(public Event $event) {}

    protected function type(): string { return 'registration_confirmed'; }

    protected function message(): string
    {
        return 'You joined "'.$this->event->title.'" on '.$this->event->starts_at->format('D, j M \a\t g:i A').'.';
    }

    protected function link(): ?string { return '/events/'.$this->event->id; }

    protected function actor(): ?User { return $this->event->organization?->user; }

    protected function extra(): array { return ['event_id' => $this->event->id]; }
}
