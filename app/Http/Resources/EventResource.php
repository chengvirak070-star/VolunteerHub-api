<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Load with Event::forViewer($user) so counts and "me" flags are present.
 *
 * @mixin \App\Models\Event
 */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $org = $this->organization;
        $joined = $this->joinedCount();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'hours' => (float) $this->hours,
            'capacity' => $this->capacity,
            'joined_count' => $joined,
            'spots_left' => max(0, $this->capacity - $joined),
            'is_joined_by_me' => (bool) ($this->is_joined ?? false),
            'is_saved_by_me' => (bool) ($this->is_saved ?? false),
            'status' => $this->status,
            'cover_url' => $this->coverUrl(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'organization' => $this->when($org !== null, fn () => [
                'id' => $org->id,
                'name' => $org->name,
                'verified' => $org->verified,
                'logo_url' => $org->logoUrl(),
                'events_count' => $org->events_count ?? null,
                'user' => $org->relationLoaded('user') && $org->user ? [
                    'id' => $org->user->id,
                    'name' => $org->user->name,
                    'username' => $org->user->username,
                    'avatar_url' => $org->user->avatarUrl(),
                    'followers_count' => $org->user->followers_count ?? null,
                    'is_following' => isset($org->user->is_following) ? (bool) $org->user->is_following : null,
                ] : null,
            ]),

            // Only on GET /events/{id}
            'attendees' => $this->when(isset($this->attendees_preview), fn () => AuthorResource::collection($this->attendees_preview)),
            'friends_going_count' => $this->when(isset($this->friends_going_count), fn () => $this->friends_going_count),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
