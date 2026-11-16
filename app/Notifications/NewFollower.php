<?php

namespace App\Notifications;

use App\Models\User;

class NewFollower extends AppNotification
{
    public function __construct(public User $follower) {}

    protected function type(): string { return 'follow'; }

    protected function message(): string { return 'started following you.'; }

    protected function link(): ?string { return '/u/'.$this->follower->username; }

    protected function actor(): ?User { return $this->follower; }
}
