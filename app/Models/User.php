<?php

namespace App\Models;

use App\Enums\HourLogStatus;
use App\Enums\OrganizationStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'bio',
        'city',
        'school',
        'skills',
        'telegram',
        'phone',
        'avatar_path',
        'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'role' => 'volunteer',
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'skills' => 'array',
            'status' => UserStatus::class,
        ];
    }

    // ---------- Relationships ----------

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** Posts this user liked. */
    public function likedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_likes')->withTimestamps();
    }

    /** People who follow this user. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    /** People this user follows. */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /** Events the user registered for (any registration status — filter with wherePivot). */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_registrations')
            ->withPivot('id', 'status')
            ->withTimestamps();
    }

    public function hourLogs(): HasMany
    {
        return $this->hasMany(HourLog::class);
    }

    public function savedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'saved_events')->withTimestamps();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    // ---------- Scopes ----------

    public function scopeActive(Builder $query): void
    {
        $query->where('status', UserStatus::Active);
    }

    /**
     * Adds total_hours, events_count, followers_count, following_count
     * and (when a viewer is given) is_following.
     */
    public function scopeWithProfileStats(Builder $query, ?User $viewer = null): void
    {
        $query->with('organization')
            ->withCount(['followers', 'following'])
            ->withCount(['registrations as events_count' => fn ($q) => $q->where('status', RegistrationStatus::Attended)])
            ->withSum(['hourLogs as total_hours' => fn ($q) => $q->where('status', HourLogStatus::Approved)], 'hours');

        if ($viewer) {
            $query->withExists(['followers as is_following' => fn ($q) => $q->where('follows.follower_id', $viewer->id)]);
        }
    }

    // ---------- Helpers ----------

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isOrganizer(): bool
    {
        return $this->role === Role::Organizer;
    }

    public function isBlocked(): bool
    {
        return $this->status === UserStatus::Blocked;
    }

    /** Organizer with an approved organization (allowed to post events). */
    public function hasApprovedOrganization(): bool
    {
        return $this->isOrganizer()
            && $this->organization?->status === OrganizationStatus::Approved;
    }

    /** Total hours = sum of approved hour logs. */
    public function totalHours(): float
    {
        return (float) $this->hourLogs()->where('status', HourLogStatus::Approved)->sum('hours');
    }

    public function isFollowing(User $other): bool
    {
        return $this->following()->whereKey($other->id)->exists();
    }

    public function isRegisteredFor(Event $event): bool
    {
        return $this->registrations()
            ->where('event_id', $event->id)
            ->where('status', '!=', RegistrationStatus::Cancelled)
            ->exists();
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }
}
