<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    /** Hidden posts are only visible to their author and admins. */
    public function view(?User $user, Post $post): Response
    {
        if ($post->status === ContentStatus::Visible) {
            return Response::allow();
        }

        return $user && ($user->isAdmin() || $user->id === $post->user_id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, Post $post): Response
    {
        return $user->id === $post->user_id
            ? Response::allow()
            : Response::deny('You can only edit your own posts.');
    }

    public function delete(User $user, Post $post): Response
    {
        return $user->id === $post->user_id || $user->isAdmin()
            ? Response::allow()
            : Response::deny('You can only delete your own posts.');
    }
}
