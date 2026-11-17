<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\OrganizationStatus;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Temporary helper until the admin panel exists (Phase 6):
 *   php artisan volunteerhub:approve rak
 * Approves the user's organization and all of its pending events.
 */
class ApproveOrganizer extends Command
{
    protected $signature = 'volunteerhub:approve {username : The organizer\'s username}';

    protected $description = 'Approve an organizer\'s organization and their pending events';

    public function handle(): int
    {
        $user = User::with('organization')->where('username', $this->argument('username'))->first();

        if (! $user) {
            $this->error('No user with that username.');

            return self::FAILURE;
        }
        if (! $user->isOrganizer() || ! $user->organization) {
            $this->error("{$user->name} is not an organizer (role: {$user->role->value}).");

            return self::FAILURE;
        }

        $org = $user->organization;
        $org->update(['status' => OrganizationStatus::Approved, 'verified' => true]);
        $this->info("Organization \"{$org->name}\" approved.");

        $events = $org->events()->where('status', EventStatus::Pending)->get();
        foreach ($events as $event) {
            $event->update(['status' => EventStatus::Open]);
            $event->refreshCapacityStatus();
            $this->line("  Event approved: {$event->title}");
        }
        $this->info($events->count().' pending event(s) approved.');

        return self::SUCCESS;
    }
}
