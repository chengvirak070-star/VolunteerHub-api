<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EventRegistration */
class RegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'user' => new AuthorResource($this->whenLoaded('user')),
            'hour_log' => $this->whenLoaded('hourLog', fn () => $this->hourLog ? [
                'id' => $this->hourLog->id,
                'hours' => (float) $this->hourLog->hours,
                'status' => $this->hourLog->status,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
