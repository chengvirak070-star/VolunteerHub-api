<?php

namespace App\Notifications;

use App\Enums\HourLogStatus;
use App\Models\HourLog;

class HoursReviewed extends AppNotification
{
    public function __construct(public HourLog $log) {}

    protected function type(): string
    {
        return $this->log->status === HourLogStatus::Approved ? 'hours_approved' : 'hours_declined';
    }

    protected function message(): string
    {
        $hours = rtrim(rtrim(number_format((float) $this->log->hours, 2), '0'), '.');
        $title = $this->log->event?->title;

        return $this->log->status === HourLogStatus::Approved
            ? "Your {$hours} hours for \"{$title}\" were approved."
            : "Your {$hours} hours for \"{$title}\" were declined.";
    }

    protected function link(): ?string { return '/my-hours'; }

    protected function extra(): array { return ['hour_log_id' => $this->log->id]; }
}
