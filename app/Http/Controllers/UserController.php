<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /** GET /users/{username} */
    public function show(Request $request, string $username): UserResource
    {
        $user = User::active()
            ->withProfileStats($request->user())
            ->where('username', $username)
            ->firstOrFail();

        return new UserResource($user);
    }

    /** GET /users/{username}/followers */
    public function followers(Request $request, string $username): AnonymousResourceCollection
    {
        $user = User::active()->where('username', $username)->firstOrFail();

        return $this->list($request, $user->followers());
    }

    /** GET /users/{username}/following */
    public function following(Request $request, string $username): AnonymousResourceCollection
    {
        $user = User::active()->where('username', $username)->firstOrFail();

        return $this->list($request, $user->following());
    }

    /** PUT /profile (name, bio, avatar) — send as multipart/form-data when uploading an avatar. */
    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'bio']);

        if ($request->hasFile('avatar')) {
            $old = $user->avatar_path;
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
        }

        $user->update($data);

        return new UserResource(User::withProfileStats()->find($user->id));
    }

    private function list(Request $request, $relation): AnonymousResourceCollection
    {
        $users = $relation->active()
            ->with('organization')
            ->withExists(['followers as is_following' => fn ($q) => $q->where('follows.follower_id', $request->user()->id)])
            ->orderByPivot('created_at', 'desc')
            ->paginate(15);

        return UserResource::collection($users);
    }
}
