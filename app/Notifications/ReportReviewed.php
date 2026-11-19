<?php

namespace App\Notifications;

/**
 * Sent to everyone who reported a post, comment or user when an admin handles it.
 * action: hide_content | block_user | dismiss. The reported person is never named.
 */
class ReportReviewed extends AppNotification
{
    public function __construct(public string $targetType, public string $action, public ?string $link = null) {}

    protected function type(): string { return 'report_reviewed'; }

    protected function message(): string
    {
        $what = match ($this->targetType) {
            'post' => 'a post',
            'comment' => 'a comment',
            default => 'an account',
        };

        return match ($this->action) {
            'hide_content' => "We reviewed your report about {$what} and removed it. Thank you for keeping VolunteerHub safe.",
            'block_user' => "We reviewed your report about {$what} and took action on the account. Thank you for keeping VolunteerHub safe.",
            default => "We reviewed your report about {$what}. It doesn't break our rules, so we left it up. Thank you for letting us know.",
        };
    }

    protected function link(): ?string
    {
        // Removed content can't be opened anymore
        return $this->action === 'dismiss' ? $this->link : null;
    }

    protected function extra(): array { return ['action' => $this->action]; }
}
