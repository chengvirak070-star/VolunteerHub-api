<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /** The comment's author, the post's author, or an admin. */
    public function delete(User $user, Comment $comment): Response
    {
        return $user->id === $comment->user_id || $user->id === $comment->post?->user_id || $user->isAdmin()
            ? Response::allow()
            : Response::deny('You can only delete your own comments.');
    }
}
