<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\HourLog */
class HourLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hours' => (float) $this->hours,
            'status' => $this->status,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'starts_at' => $this->event->starts_at?->toIso8601String(),
                'location' => $this->event->location,
                'organization' => $this->event->relationLoaded('organization') ? $this->event->organization?->name : null,
            ]),
            'user' => new AuthorResource($this->whenLoaded('user')),
        ];
    }
}
