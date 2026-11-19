<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\BugReport */
class BugReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'page_url' => $this->page_url,
            'screenshot_url' => $this->screenshot_path ? Storage::disk('public')->url($this->screenshot_path) : null,
            'status' => $this->status,
            'user' => new AuthorResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
