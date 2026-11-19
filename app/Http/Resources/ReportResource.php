<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin \App\Models\Report */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $target = $this->reportable;

        return [
            'id' => $this->id,
            'type' => $this->reportable_type, // post | comment | user
            'reason' => $this->reason,
            'status' => $this->status,
            'reporter' => new AuthorResource($this->whenLoaded('reporter')),
            'target' => match (true) {
                $target instanceof Post => [
                    'id' => $target->id,
                    'text' => Str::limit($target->body, 200),
                    'status' => $target->status,
                    'author' => new AuthorResource($target->user),
                ],
                $target instanceof Comment => [
                    'id' => $target->id,
                    'text' => Str::limit($target->body, 200),
                    'status' => $target->status,
                    'post_id' => $target->post_id,
                    'author' => new AuthorResource($target->user),
                ],
                $target instanceof User => [
                    'id' => $target->id,
                    'text' => $target->bio,
                    'status' => $target->status,
                    'author' => new AuthorResource($target),
                ],
                default => null, // content was deleted
            },
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
