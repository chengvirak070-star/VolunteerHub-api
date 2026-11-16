<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;

class PostLiked extends AppNotification
{
    public function __construct(public Post $post, public User $liker) {}

    protected function type(): string { return 'like'; }

    protected function message(): string { return 'liked your post.'; }

    protected function link(): ?string { return '/posts/'.$this->post->id; }

    protected function actor(): ?User { return $this->liker; }

    protected function extra(): array { return ['post_id' => $this->post->id]; }
}
