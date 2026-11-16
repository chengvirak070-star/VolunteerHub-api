<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Str;

class PostCommented extends AppNotification
{
    public function __construct(public Comment $comment, public User $commenter) {}

    protected function type(): string { return 'comment'; }

    protected function message(): string
    {
        return 'commented on your post: "'.Str::limit($this->comment->body, 60).'"';
    }

    protected function link(): ?string { return '/posts/'.$this->comment->post_id; }

    protected function actor(): ?User { return $this->commenter; }

    protected function extra(): array
    {
        return ['post_id' => $this->comment->post_id, 'comment_id' => $this->comment->id];
    }
}
