<?php

namespace App\Notifications;

use App\Models\BugReport;
use Illuminate\Support\Str;

/** Sent to the person who reported a bug when an admin marks it fixed. */
class BugReportFixed extends AppNotification
{
    public function __construct(public BugReport $bug) {}

    protected function type(): string { return 'bug_fixed'; }

    protected function message(): string
    {
        return 'The problem you reported was fixed: "'.Str::limit($this->bug->message, 60).'" Thank you for helping improve VolunteerHub!';
    }

    protected function link(): ?string { return null; }

    protected function extra(): array { return ['bug_report_id' => $this->bug->id]; }
}
