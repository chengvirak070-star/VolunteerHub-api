<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Load with Post::forViewer($user) so counts and is_liked are present.
 *
 * @mixin \App\Models\Post
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $event = $this->relationLoaded('event') ? $this->event : null;
        $joined = $event?->joined_count ?? 0;

        return [
            'id' => $this->id,
            'author' => new AuthorResource($this->whenLoaded('user')),
            'body' => $this->body,
            'image_url' => $this->imageUrl(),
            'event' => $event ? [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'location' => $event->location,
                'status' => $event->status,
                'spots_left' => max(0, $event->capacity - $joined),
                'is_joined_by_me' => (bool) ($event->is_joined ?? false),
            ] : null,
            'hashtags' => $this->whenLoaded('hashtags', fn () => $this->hashtags->pluck('name')),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'is_liked_by_me' => (bool) ($this->is_liked ?? false),
            'status' => $this->when($request->user()?->isAdmin() || $request->user()?->id === $this->user_id, $this->status),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
