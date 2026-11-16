<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    /** Registration statuses that take a spot (everything except "cancelled"). */
    public const SPOT_STATUSES = ['registered', 'attended', 'absent'];

    protected $fillable = [
        'organization_id',
        'category_id',
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'hours',
        'capacity',
        'cover_path',
        'status',
        'reminder_sent_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'hours' => 'decimal:2',
            'capacity' => 'integer',
            'status' => EventStatus::class,
        ];
    }

    // ---------- Relationships ----------

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /** Registrations that hold a spot (not cancelled). */
    public function activeRegistrations(): HasMany
    {
        return $this->registrations()->whereIn('status', self::SPOT_STATUSES);
    }

    /** Users holding a spot. */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_registrations')
            ->withPivot('id', 'status')
            ->wherePivotIn('status', self::SPOT_STATUSES)
            ->withTimestamps();
    }

    public function hourLogs(): HasMany
    {
        return $this->hasMany(HourLog::class);
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_events')->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // ---------- Scopes ----------

    /** Events normal users can see. */
    public function scopePublished(Builder $query): void
    {
        $query->whereIn('status', [EventStatus::Open, EventStatus::Full, EventStatus::Closed]);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now());
    }

    /** Adds joined_count to each event. */
    public function scopeWithJoinedCount(Builder $query): void
    {
        $query->withCount(['registrations as joined_count' => fn ($q) => $q->whereIn('status', self::SPOT_STATUSES)]);
    }

    // ---------- Helpers ----------

    public function joinedCount(): int
    {
        return $this->joined_count ?? $this->activeRegistrations()->count();
    }

    public function spotsLeft(): int
    {
        return max(0, $this->capacity - $this->joinedCount());
    }

    /**
     * Keep status in sync with capacity: open <-> full.
     * Only touches events that are currently open or full.
     */
    public function refreshCapacityStatus(): void
    {
        if (! in_array($this->status, [EventStatus::Open, EventStatus::Full], true)) {
            return;
        }

        $joined = $this->activeRegistrations()->count();
        $status = $joined >= $this->capacity ? EventStatus::Full : EventStatus::Open;

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function organizerUser(): ?User
    {
        return $this->organization?->user;
    }
}
