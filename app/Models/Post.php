<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    /**
     * #hashtag = letters (incl. Khmer), combining marks (Khmer vowel signs), digits, underscore.
     */
    public const HASHTAG_REGEX = '/#([\p{L}\p{M}\p{N}_]+)/u';

    protected $fillable = ['user_id', 'event_id', 'body', 'image_path', 'status'];

    protected $attributes = [
        'status' => 'visible',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
        ];
    }

    // ---------- Relationships ----------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Users who liked this post. */
    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_likes')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    // ---------- Scopes ----------

    public function scopeVisible(Builder $query): void
    {
        $query->where('status', ContentStatus::Visible);
    }

    // ---------- Helpers ----------

    /** @return list<string> lowercase tag names without '#', unique */
    public static function extractHashtags(string $body): array
    {
        preg_match_all(self::HASHTAG_REGEX, $body, $matches);

        return array_values(array_unique(array_map(
            fn (string $tag) => mb_strtolower(mb_substr($tag, 0, 100)),
            $matches[1],
        )));
    }

    /** Re-read hashtags from the body and sync the pivot table. */
    public function syncHashtags(): void
    {
        $ids = collect(self::extractHashtags($this->body))
            ->map(fn (string $name) => Hashtag::firstOrCreate(['name' => $name])->id);

        $this->hashtags()->sync($ids);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
