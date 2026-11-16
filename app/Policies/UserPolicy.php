<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /** A user cannot follow themselves or a blocked account. */
    public function follow(User $user, User $target): Response
    {
        if ($user->is($target)) {
            return Response::deny('You cannot follow yourself.');
        }

        if ($target->isBlocked()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
