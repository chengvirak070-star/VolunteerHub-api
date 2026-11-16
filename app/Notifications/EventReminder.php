<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;

class EventReminder extends AppNotification
{
    public function __construct(public Event $event) {}

    protected function type(): string { return 'event_reminder'; }

    protected function message(): string
    {
        return 'Reminder: "'.$this->event->title.'" is tomorrow at '.$this->event->starts_at->format('g:i A').', '.$this->event->location.'.';
    }

    protected function link(): ?string { return '/events/'.$this->event->id; }

    protected function actor(): ?User { return $this->event->organization?->user; }

    protected function extra(): array { return ['event_id' => $this->event->id]; }
}
