<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /** GET /admin/users?search=&role=&status= */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::withProfileStats()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when(trim((string) $request->input('search')), function ($q, $search) {
                $like = '%'.mb_strtolower($search).'%';
                $q->where(fn ($w) => $w
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like]));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return UserResource::collection($users);
    }

    /** POST /admin/users/{user}/block — logs them out everywhere. */
    public function block(Request $request, User $user): UserResource
    {
        abort_if($user->is($request->user()), 422, 'You cannot block yourself.');
        abort_if($user->isAdmin(), 422, 'Admins cannot be blocked.');

        $user->update(['status' => UserStatus::Blocked]);
        $user->tokens()->delete();

        return new UserResource(User::withProfileStats()->find($user->id));
    }

    /** POST /admin/users/{user}/unblock */
    public function unblock(User $user): UserResource
    {
        $user->update(['status' => UserStatus::Active]);

        return new UserResource(User::withProfileStats()->find($user->id));
    }
}
