<?php

namespace App\Models;

use App\Enums\HourLogStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HourLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_registration_id',
        'user_id',
        'event_id',
        'hours',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'status' => HourLogStatus::class,
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
