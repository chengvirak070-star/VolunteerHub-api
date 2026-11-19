<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isMe = $viewer?->id === $this->id;
        $canSeePrivate = $isMe || $viewer?->isAdmin();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'role' => $this->role,
            'bio' => $this->bio,
            'city' => $this->city,
            'school' => $this->school,
            'skills' => $this->skills ?? [],
            'telegram' => $this->telegram,
            'phone' => $this->when($canSeePrivate, $this->phone), // private
            'avatar_url' => $this->avatarUrl(),
            'verified' => (bool) $this->organization?->verified,
            'email' => $this->when($canSeePrivate, $this->email),
            'status' => $this->when($canSeePrivate, $this->status),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),

            // Stats (present when loaded with User::withProfileStats())
            'total_hours' => $this->whenHas('total_hours', fn ($v) => (float) $v),
            'events_count' => $this->whenHas('events_count'),
            'followers_count' => $this->whenCounted('followers'),
            'following_count' => $this->whenCounted('following'),
            'is_following' => $this->whenHas('is_following', fn ($v) => (bool) $v),
            'is_me' => $isMe,
            'created_at' => $this->created_at,
        ];
    }
}
